<?php

namespace App\Http\Controllers;

use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected $checkService;
    protected $scannerService;
    
    public function __construct(
        WebsiteCheckService $checkService,
        ContentScannerService $scannerService
    ) {
        $this->checkService = $checkService;
        $this->scannerService = $scannerService;
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
        ]);
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
        ]);
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
        ]);
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
        ]);
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
        ]);
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
        ]);
    }
}
