<?php

namespace App\Http\Controllers;

use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected $checkService;
    protected $scannerService;
    protected $recommendationService;
    
    public function __construct(
        WebsiteCheckService $checkService,
        ContentScannerService $scannerService,
        RecommendationService $recommendationService
    ) {
        $this->checkService = $checkService;
        $this->scannerService = $scannerService;
        $this->recommendationService = $recommendationService;
    }
    
    /**
     * Test ping/uptime check
     */
    public function testPing(Request $request)
    {
        $url = $request->input('url', 'https://google.com');
        
        $result = $this->checkService->checkStatus($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Test content scanner - Posts only (via WP-JSON)
     */
    public function testPosts(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi'
            ], 400);
        }
        
        $result = $this->scannerService->scanPosts($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Test content scanner - Header & Footer
     */
    public function testHeaderFooter(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi'
            ], 400);
        }
        
        $result = $this->scannerService->scanHeaderFooter($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Test content scanner - Meta tags
     */
    public function testMeta(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi'
            ], 400);
        }
        
        $result = $this->scannerService->scanMeta($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Test content scanner - Sitemap
     */
    public function testSitemap(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi'
            ], 400);
        }
        
        $result = $this->scannerService->scanSitemap($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Test full content scan (all areas)
     */
    public function testFullScan(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi'
            ], 400);
        }
        
        $result = $this->scannerService->scanContent($url);
        
        return response()->json([
            'tested_url' => $url,
            'result' => $result,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Test RecommendationService dengan scan result real
     */
    public function testRecommendations(Request $request)
    {
        $url = $request->input('url');
        
        if (!$url) {
            return response()->json([
                'error' => 'Parameter URL wajib diisi',
                'usage' => 'GET /test/recommendations?url=https://example.com'
            ], 400);
        }

        // Lakukan full scan terlebih dahulu
        $statusResult = $this->checkService->checkStatus($url);
        $contentResult = $this->scannerService->scanContent($url);

        // Prepare data untuk recommendation engine
        $fullScanResult = [
            'status' => $statusResult,
            'posts' => $contentResult['posts'],
            'header_footer' => $contentResult['header_footer'],
            'meta' => $contentResult['meta'],
            'sitemap' => $contentResult['sitemap'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
        ];

        // Generate recommendations
        $recommendations = $this->recommendationService->generateRecommendations($fullScanResult);

        return response()->json([
            'tested_url' => $url,
            'scan_summary' => [
                'status' => $statusResult['status'],
                'has_suspicious_content' => $contentResult['has_suspicious_content'],
                'suspicious_areas' => [
                    'posts' => $contentResult['posts']['has_suspicious'] ?? false,
                    'header_footer' => $contentResult['header_footer']['has_suspicious'] ?? false,
                    'meta' => $contentResult['meta']['has_suspicious'] ?? false,
                    'sitemap' => $contentResult['sitemap']['has_suspicious'] ?? false,
                ],
            ],
            'recommendations' => $recommendations,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Test RecommendationService dengan dummy data
     */
    public function testRecommendationsDummy(Request $request)
    {
        $scenario = $request->input('scenario', 'multiple_infected');

        $scenarios = [
            // Scenario 1: Multiple areas infected (Critical)
            'multiple_infected' => [
                'status' => ['status' => 'online', 'response_time' => 150],
                'posts' => [
                    'has_suspicious' => true,
                    'suspicious_count' => 10,
                ],
                'header_footer' => [
                    'has_suspicious' => true,
                    'keyword_count' => 5,
                ],
                'meta' => [
                    'has_suspicious' => true,
                    'keyword_count' => 3,
                ],
                'sitemap' => [
                    'has_suspicious' => true,
                    'keyword_count' => 2,
                ],
                'has_suspicious_content' => true,
            ],

            // Scenario 2: Only posts infected
            'posts_only' => [
                'status' => ['status' => 'online', 'response_time' => 120],
                'posts' => [
                    'has_suspicious' => true,
                    'suspicious_count' => 3,
                ],
                'header_footer' => ['has_suspicious' => false, 'keyword_count' => 0],
                'meta' => ['has_suspicious' => false, 'keyword_count' => 0],
                'sitemap' => ['has_suspicious' => false, 'keyword_count' => 0],
                'has_suspicious_content' => true,
            ],

            // Scenario 3: Website offline
            'offline' => [
                'status' => ['status' => 'offline', 'response_time' => 5000],
                'posts' => ['has_suspicious' => false, 'suspicious_count' => 0],
                'header_footer' => ['has_suspicious' => false, 'keyword_count' => 0],
                'meta' => ['has_suspicious' => false, 'keyword_count' => 0],
                'sitemap' => ['has_suspicious' => false, 'keyword_count' => 0],
                'has_suspicious_content' => false,
            ],

            // Scenario 4: Clean website
            'clean' => [
                'status' => ['status' => 'online', 'response_time' => 100],
                'posts' => ['has_suspicious' => false, 'suspicious_count' => 0],
                'header_footer' => ['has_suspicious' => false, 'keyword_count' => 0],
                'meta' => ['has_suspicious' => false, 'keyword_count' => 0],
                'sitemap' => ['has_suspicious' => false, 'keyword_count' => 0],
                'has_suspicious_content' => false,
            ],
        ];

        $scanResults = $scenarios[$scenario] ?? $scenarios['clean'];
        $recommendations = $this->recommendationService->generateRecommendations($scanResults);

        return response()->json([
            'scenario' => $scenario,
            'available_scenarios' => array_keys($scenarios),
            'scan_results' => $scanResults,
            'recommendations' => $recommendations,
            'tested_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT);
    }
}
