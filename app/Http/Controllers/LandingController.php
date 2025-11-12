<?php

namespace App\Http\Controllers;

use App\Models\GuestScan;
use App\Services\ContentScannerService;
use App\Services\WebsiteCheckService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    protected $scannerService;
    protected $checkService;
    protected $recommendationService;

    public function __construct(
        ContentScannerService $scannerService,
        WebsiteCheckService $checkService,
        RecommendationService $recommendationService
    ) {
        $this->scannerService = $scannerService;
        $this->checkService = $checkService;
        $this->recommendationService = $recommendationService;
    }

    /**
     * Landing page
     */
    public function index()
    {
        return view('landing.index');
    }

    /**
     * Guest scan - EXACT SAMA SEPERTI performFullCheck()
     */
    public function guestScan(Request $request)
    {
        $request->validate([
            'url' => 'required|url|max:500',
        ]);

        try {
            set_time_limit(300);
            
            $url = $request->url;
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            // ✅ EXACT SAMA: Run checks
            $statusResult = $this->checkService->checkStatus($url);
            $contentResult = $this->scannerService->scanContent($url);

            // ✅ EXACT SAMA: Build full scan result
            $fullScanResult = [
                'status' => $statusResult,
                'posts' => $contentResult['posts'],
                'pages' => $contentResult['pages'],
                'header_footer' => $contentResult['header_footer'],
                'meta' => $contentResult['meta'],
                'sitemap' => $contentResult['sitemap'],
                'has_suspicious_content' => $contentResult['has_suspicious_content'],
            ];

            // ✅ EXACT SAMA: Generate recommendations
            $recommendations = $this->recommendationService->generateRecommendations($fullScanResult);

            // ✅ EXACT SAMA: Calculate total suspicious
            $totalSuspicious = 
                ($contentResult['posts']['suspicious_count'] ?? 0) +
                ($contentResult['pages']['suspicious_count'] ?? 0) +
                ($contentResult['header_footer']['keyword_count'] ?? 0) +
                ($contentResult['meta']['keyword_count'] ?? 0) +
                ($contentResult['sitemap']['keyword_count'] ?? 0);

            // ✅ EXACT SAMA: Build compressed result
            $compressedResult = [
                'status' => [
                    'status' => $statusResult['status'],
                    'http_code' => $statusResult['http_code'],
                    'response_time' => $statusResult['response_time'],
                    'error' => $statusResult['error'] ?? null,
                ],
                'content' => [
                    'url' => $contentResult['url'],
                    'scanned_at' => $contentResult['scanned_at'],
                    'posts' => [
                        'has_suspicious' => $contentResult['posts']['has_suspicious'] ?? false,
                        'total_posts' => $contentResult['posts']['total_posts'] ?? 0,
                        'suspicious_count' => $contentResult['posts']['suspicious_count'] ?? 0,
                        'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
                        'injected_html' => $contentResult['posts']['injected_html'] ?? null,
                        'error' => $contentResult['posts']['error'] ?? null,
                    ],
                    'pages' => [
                        'has_suspicious' => $contentResult['pages']['has_suspicious'] ?? false,
                        'total_pages' => $contentResult['pages']['total_pages'] ?? 0,
                        'suspicious_count' => $contentResult['pages']['suspicious_count'] ?? 0,
                        'suspicious_pages' => array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 20),
                        'injected_html' => $contentResult['pages']['injected_html'] ?? null,
                        'error' => $contentResult['pages']['error'] ?? null,
                    ],
                    'header_footer' => [
                        'has_suspicious' => $contentResult['header_footer']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['header_footer']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['header_footer']['keywords'] ?? [],
                        'error' => $contentResult['header_footer']['error'] ?? null,
                    ],
                    'meta' => [
                        'has_suspicious' => $contentResult['meta']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['meta']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['meta']['keywords'] ?? [],
                        'meta_title' => $contentResult['meta']['meta_title'] ?? '',
                        'meta_description' => $contentResult['meta']['meta_description'] ?? '',
                        'error' => $contentResult['meta']['error'] ?? null,
                    ],
                    'sitemap' => [
                        'has_suspicious' => $contentResult['sitemap']['has_suspicious'] ?? false,
                        'keyword_count' => $contentResult['sitemap']['keyword_count'] ?? 0,
                        'keywords' => $contentResult['sitemap']['keywords'] ?? [],
                        'suspicious_urls' => array_slice($contentResult['sitemap']['suspicious_urls'] ?? [], 0, 10),
                        'error' => $contentResult['sitemap']['error'] ?? null,
                    ],
                    'has_suspicious_content' => $contentResult['has_suspicious_content'],
                ],
                'recommendations' => $recommendations,
            ];

            // ✅ Save to guest_scans table (tracking)
            GuestScan::create([
                'url' => $url,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'status' => $statusResult['status'],
                'has_suspicious_content' => $contentResult['has_suspicious_content'],
                'suspicious_posts_count' => $totalSuspicious,
                'scan_result' => $compressedResult,
            ]);

            // ✅ Return result untuk modal
            return response()->json([
                'success' => true,
                'data' => $compressedResult,
            ]);

        } catch (\Exception $e) {
            \Log::error('Guest scan error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan scan. Silakan coba lagi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
