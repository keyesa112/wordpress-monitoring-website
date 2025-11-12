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

            // 2. Run content scan (ALREADY includes single_page_scan!)
            $contentResult = $scannerService->scanContent($website->url);

            // 🔥 3. Check REST API availability
            $postsHasData = ($contentResult['posts']['total_posts'] ?? 0) > 0;
            $pagesHasData = ($contentResult['pages']['total_pages'] ?? 0) > 0;
            $restApiWorking = $postsHasData || $pagesHasData;

            // 🔥 4. Detect WordPress (OPTIONAL - only for extra info)
            $isWordPress = false;
            $wpVersion = 'Unknown';
            $detectionMethod = 'Unknown';

            if ($restApiWorking) {
                $isWordPress = true;
                $detectionMethod = 'REST API';
            } else {
                // Run fallback detection for WordPress info only
                $wpDetection = $scannerService->detectWordPressFallback($website->url);
                $isWordPress = $wpDetection['is_wordpress'] ?? false;
                $detectionMethod = $wpDetection['method'] ?? 'Unknown';
                $wpVersion = $wpDetection['version'] ?? 'Unknown';
            }

            // 🔥 5. Calculate total suspicious (simple!)
            $totalSuspicious = 
                ($contentResult['posts']['suspicious_count'] ?? 0) +
                ($contentResult['pages']['suspicious_count'] ?? 0) +
                ($contentResult['header_footer']['keyword_count'] ?? 0) +
                ($contentResult['meta']['keyword_count'] ?? 0) +
                ($contentResult['sitemap']['keyword_count'] ?? 0) +
                ($contentResult['single_page_scan']['keyword_count'] ?? 0); // 🔥 From scanContent()

            // 6. Has suspicious (already calculated in scanContent!)
            $hasSuspiciousContent = $contentResult['has_suspicious_content'];

            // 7. Full scan result for recommendations
            $fullScanResult = [
                'status' => $statusResult,
                'posts' => $contentResult['posts'],
                'pages' => $contentResult['pages'],
                'header_footer' => $contentResult['header_footer'],
                'meta' => $contentResult['meta'],
                'sitemap' => $contentResult['sitemap'],
                'single_page_scan' => $contentResult['single_page_scan'] ?? null, // 🔥 From scanContent()
                'has_suspicious_content' => $hasSuspiciousContent,
                'rest_api_available' => $restApiWorking,
            ];

            // 8. Generate recommendations
            $recommendations = $recommendationService->generateRecommendations($fullScanResult);

            // 9. Compressed result for storage
            $compressedResult = [
                'status' => [
                    'status' => $statusResult['status'],
                    'http_code' => $statusResult['http_code'],
                    'response_time' => $statusResult['response_time'],
                    'error' => $statusResult['error'] ?? null,
                ],
                'wordpress_info' => [
                    'is_wordpress' => $isWordPress,
                    'detection_method' => $detectionMethod,
                    'version' => $wpVersion,
                    'rest_api_available' => $restApiWorking,
                ],
                'content' => [
                    'url' => $contentResult['url'],
                    'scanned_at' => $contentResult['scanned_at'],
                    'posts' => [
                        'has_suspicious' => $contentResult['posts']['has_suspicious'] ?? false,
                        'total_posts' => $contentResult['posts']['total_posts'] ?? 0,
                        'suspicious_count' => $contentResult['posts']['suspicious_count'] ?? 0,
                        'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
                        'error' => $contentResult['posts']['error'] ?? null,
                    ],
                    'pages' => [
                        'has_suspicious' => $contentResult['pages']['has_suspicious'] ?? false,
                        'total_pages' => $contentResult['pages']['total_pages'] ?? 0,
                        'suspicious_count' => $contentResult['pages']['suspicious_count'] ?? 0,
                        'suspicious_pages' => array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 20),
                        'error' => $contentResult['pages']['error'] ?? null,
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
                'single_page_scan' => $contentResult['single_page_scan'] ?? null, // 🔥 Include
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
                ($isWordPress ? 'Yes' : 'No') . 
                " | Method: {$detectionMethod}" .
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
