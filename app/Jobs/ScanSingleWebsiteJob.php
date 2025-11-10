<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\MonitoringLog;
use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use App\Services\RecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanSingleWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $websiteId;
    public $userId;
    public $timeout = 120;

    public function __construct($websiteId, $userId)
    {
        $this->websiteId = $websiteId;
        $this->userId = $userId;
    }

    public function handle()
    {
        $website = Website::find($this->websiteId);
        if (!$website) return;

        Log::info("ScanSingleWebsiteJob START: {$website->url}");

        try {
            // Services
            $checkService = app(WebsiteCheckService::class);
            $scannerService = app(ContentScannerService::class);
            $recommendationService = app(RecommendationService::class);

            // 1. Run status check
            $statusResult = $checkService->checkStatus($website->url);

            // 2. Run content scan
            $contentResult = $scannerService->scanContent($website->url);

            // 🔥 3. CHECK: Apakah REST API berhasil?
            $postsError = $contentResult['posts']['error'] ?? null;
            $pagesError = $contentResult['pages']['error'] ?? null;
            $postsHasData = ($contentResult['posts']['total_posts'] ?? 0) > 0;
            $pagesHasData = ($contentResult['pages']['total_pages'] ?? 0) > 0;

            $restApiWorking = $postsHasData || $pagesHasData;

            // 🔥 4. FALLBACK DETECTION (jika REST API gagal)
            $wpDetection = [
                'is_wordpress' => $restApiWorking,
                'rest_api_available' => $restApiWorking,
                'detection_method' => 'REST API',
                'version' => 'Unknown',
                'single_page_scan' => null,
            ];

            if (!$restApiWorking) {
                // Jalankan fallback detection dengan single page scan
                $wpDetection = $scannerService->detectWordPressFallback($website->url);
                
                Log::info("Fallback Detection [{$website->url}]: Method={$wpDetection['detection_method']}, WP={$wpDetection['is_wordpress']}");
            }

            // 🔥 5. Hitung total suspicious (termasuk dari single page scan jika ada)
            $totalSuspicious = 
                ($contentResult['posts']['suspicious_count'] ?? 0) +
                ($contentResult['pages']['suspicious_count'] ?? 0) +
                ($contentResult['header_footer']['keyword_count'] ?? 0) +
                ($contentResult['meta']['keyword_count'] ?? 0) +
                ($contentResult['sitemap']['keyword_count'] ?? 0);

            // Tambahkan dari single page scan jika ada
            if (isset($wpDetection['single_page_scan']) && $wpDetection['single_page_scan']) {
                $totalSuspicious += ($wpDetection['single_page_scan']['keyword_count'] ?? 0);
            }

            // 6. Hitung has_suspicious_content (include single page scan)
            $hasSuspiciousContent = $contentResult['has_suspicious_content'];
            if (isset($wpDetection['single_page_scan']) && $wpDetection['single_page_scan']['has_suspicious']) {
                $hasSuspiciousContent = true;
            }

            // 7. Full scan result (dengan info WordPress detection)
            $fullScanResult = [
                'status' => $statusResult,
                'posts' => $contentResult['posts'],
                'pages' => $contentResult['pages'],
                'header_footer' => $contentResult['header_footer'],
                'meta' => $contentResult['meta'],
                'sitemap' => $contentResult['sitemap'],
                'has_suspicious_content' => $hasSuspiciousContent,
                'wordpress_detected' => $wpDetection['is_wordpress'],
                'rest_api_available' => $wpDetection['rest_api_available'],
                'detection_method' => $wpDetection['detection_method'],
                'wordpress_version' => $wpDetection['version'] ?? 'Unknown',
                'single_page_scan' => $wpDetection['single_page_scan'] ?? null,
            ];

            // 8. Recommendations (R11 & R12 akan trigger jika perlu)
            $recommendations = $recommendationService->generateRecommendations($fullScanResult);

            // 9. Compressed result
            $compressedResult = [
                'status' => [
                    'status' => $statusResult['status'],
                    'http_code' => $statusResult['http_code'],
                    'response_time' => $statusResult['response_time'],
                    'error' => $statusResult['error'] ?? null,
                ],
                'wordpress_info' => [
                    'is_wordpress' => $wpDetection['is_wordpress'],
                    'detection_method' => $wpDetection['detection_method'],
                    'version' => $wpDetection['version'] ?? 'Unknown',
                    'rest_api_available' => $wpDetection['rest_api_available'],
                ],
                'content' => [
                    'url' => $contentResult['url'],
                    'scanned_at' => $contentResult['scanned_at'],
                    'posts' => [
                        'has_suspicious' => $contentResult['posts']['has_suspicious'] ?? false,
                        'total_posts' => $contentResult['posts']['total_posts'] ?? 0,
                        'suspicious_count' => $contentResult['posts']['suspicious_count'] ?? 0,
                        'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
                        'error' => $postsError,
                    ],
                    'pages' => [
                        'has_suspicious' => $contentResult['pages']['has_suspicious'] ?? false,
                        'total_pages' => $contentResult['pages']['total_pages'] ?? 0,
                        'suspicious_count' => $contentResult['pages']['suspicious_count'] ?? 0,
                        'suspicious_pages' => array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 20),
                        'error' => $pagesError,
                    ],
                    'header_footer' => [
                        'has_suspicious' => $contentResult['header_footer']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['header_footer']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['header_footer']['keywords'] ?? [],
                    ],
                    'meta' => [
                        'has_suspicious' => $contentResult['meta']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['meta']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['meta']['keywords'] ?? [],
                    ],
                    'sitemap' => [
                        'has_suspicious' => $contentResult['sitemap']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['sitemap']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['sitemap']['keywords'] ?? [],
                    ],
                    'has_suspicious_content' => $hasSuspiciousContent,
                ],
                // 🔥 Single page scan (jika ada)
                'single_page_scan' => $wpDetection['single_page_scan'] ?? null,
                'recommendations' => $recommendations,
            ];

            // 10. Update website
            DB::table('websites')->where('id', $website->id)->update([
                'status' => $statusResult['status'],
                'response_time' => $statusResult['response_time'],
                'http_code' => $statusResult['http_code'],
                'has_suspicious_content' => $hasSuspiciousContent,
                'suspicious_posts_count' => $totalSuspicious,
                'last_check_result' => json_encode($compressedResult),
                'last_checked_at' => now(),
                'updated_at' => now(),
            ]);

            // 11. Log
            MonitoringLog::create([
                'website_id' => $website->id,
                'user_id' => $this->userId,
                'check_type' => 'full',
                'status' => $statusResult['status'],
                'response_time' => $statusResult['response_time'],
                'http_code' => $statusResult['http_code'],
                'has_suspicious_content' => $hasSuspiciousContent,
                'suspicious_posts_count' => $totalSuspicious,
                'suspicious_posts' => json_encode(array_merge(
                    array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 10),
                    array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 10)
                )),
                'error_message' => $statusResult['error'] ?? null,
                'raw_result' => json_encode($compressedResult),
            ]);

            Log::info("ScanSingleWebsiteJob SUCCESS: {$website->url} | WP: " . 
                ($wpDetection['is_wordpress'] ? 'Yes' : 'No') . 
                " | Method: {$wpDetection['detection_method']}" .
                " | Suspicious: {$totalSuspicious}");

        } catch (\Exception $e) {
            Log::error("ScanSingleWebsiteJob ERROR [{$website->url}]: " . $e->getMessage());
            DB::table('websites')->where('id', $website->id)->update(['status' => 'error']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ScanSingleWebsiteJob FAILED [ID: {$this->websiteId}]: " . $exception->getMessage());
        DB::table('websites')->where('id', $this->websiteId)->update(['status' => 'error']);
    }
}
