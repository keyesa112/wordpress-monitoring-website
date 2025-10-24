<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class WebsiteController extends Controller
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
     * Display a listing of websites
     */
    public function index()
    {
        $websites = Website::with('latestLog')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('websites.index', compact('websites'));
    }

    /**
     * Show the form for creating a new website
     */
    public function create()
    {
        return view('websites.create');
    }

    /**
     * Store a newly created website
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:websites,url',
            'notes' => 'nullable|string',
        ]);

        $website = Website::create([
            'name' => $request->name,
            'url' => $request->url,
            'notes' => $request->notes,
            'status' => 'checking',
            'is_active' => true,
        ]);

        // Auto check setelah dibuat
        $this->performFullCheck($website);

        return redirect()->route('websites.index')
            ->with('success', 'Website berhasil ditambahkan dan sedang dicek!');
    }

    /**
     * Display the specified website
     */
    public function show(Website $website)
    {
        $website->load(['monitoringLogs' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(20);
        }]);
        
        return view('websites.show', compact('website'));
    }

    /**
     * Show the form for editing the specified website
     */
    public function edit(Website $website)
    {
        return view('websites.edit', compact('website'));
    }

    /**
     * Update the specified website
     */
    public function update(Request $request, Website $website)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:websites,url,' . $website->id,
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $website->update($request->only(['name', 'url', 'notes', 'is_active']));

        return redirect()->route('websites.index')
            ->with('success', 'Website berhasil diupdate!');
    }

    /**
     * Remove the specified website
     */
    public function destroy(Website $website)
    {
        $website->delete();

        return redirect()->route('websites.index')
            ->with('success', 'Website berhasil dihapus!');
    }

    /**
     * Manual check website (full check)
     */
    public function check(Website $website)
    {
        set_time_limit(300); // 5 menit untuk scan berat
        
        $this->performFullCheck($website);

        return redirect()->back()
            ->with('success', 'Website berhasil dicek ulang!');
    }

    /**
     * Check status only (uptime/downtime)
     */
    public function checkStatus(Website $website)
    {
        $this->performStatusCheck($website);

        return redirect()->back()
            ->with('success', 'Status website berhasil dicek!');
    }

    /**
     * Scan content only (keyword detection)
     */
    public function scanContent(Website $website)
    {
        set_time_limit(300);
        
        $this->performContentScan($website);

        return redirect()->back()
            ->with('success', 'Konten website berhasil discan!');
    }

    /**
     * Perform full check (status + content scan) dengan recommendations
     */
    protected function performFullCheck(Website $website)
    {
        // Update status ke checking
        $website->update(['status' => 'checking']);

        // Cek status (ping)
        $statusResult = $this->checkService->checkStatus($website->url);
        
        // Scan konten lengkap (posts, header/footer, meta, sitemap)
        $contentResult = $this->scannerService->scanContent($website->url);

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

        // Hitung total suspicious items
        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

        // Compress result untuk save ke database (hindari data too long)
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
                    'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20), // Max 20 posts
                    'error' => $contentResult['posts']['error'] ?? null,
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
                    'suspicious_urls' => array_slice($contentResult['sitemap']['suspicious_urls'] ?? [], 0, 10), // Max 10 URLs
                    'error' => $contentResult['sitemap']['error'] ?? null,
                ],
                'has_suspicious_content' => $contentResult['has_suspicious_content'],
            ],
            'recommendations' => $recommendations,
        ];

        // Update website
        $website->update([
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode($compressedResult),
            'last_checked_at' => now(),
        ]);

        // Simpan log
        MonitoringLog::create([
            'website_id' => $website->id,
            'check_type' => 'full',
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
            'error_message' => $statusResult['error'] ?? $contentResult['posts']['error'] ?? null,
            'raw_result' => $compressedResult,
        ]);
    }

    /**
     * Perform status check only
     */
    protected function performStatusCheck(Website $website)
    {
        // Cek status (ping) saja
        $statusResult = $this->checkService->checkStatus($website->url);

        // Update website
        $website->update([
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'last_checked_at' => now(),
        ]);

        // Simpan log
        MonitoringLog::create([
            'website_id' => $website->id,
            'check_type' => 'status',
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => false,
            'suspicious_posts_count' => 0,
            'error_message' => $statusResult['error'] ?? null,
            'raw_result' => [
                'status' => $statusResult,
            ],
        ]);
    }

    /**
     * Perform content scan only
     */
    protected function performContentScan(Website $website)
    {
        // Scan konten saja
        $contentResult = $this->scannerService->scanContent($website->url);

        // Generate recommendations
        $fullScanResult = [
            'status' => ['status' => $website->status ?? 'unknown'],
            'posts' => $contentResult['posts'],
            'header_footer' => $contentResult['header_footer'],
            'meta' => $contentResult['meta'],
            'sitemap' => $contentResult['sitemap'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
        ];

        $recommendations = $this->recommendationService->generateRecommendations($fullScanResult);

        // Hitung total suspicious items
        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

        // Compress result
        $compressedResult = [
            'content' => [
                'url' => $contentResult['url'],
                'scanned_at' => $contentResult['scanned_at'],
                'posts' => [
                    'has_suspicious' => $contentResult['posts']['has_suspicious'] ?? false,
                    'suspicious_count' => $contentResult['posts']['suspicious_count'] ?? 0,
                    'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
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
                    'suspicious_urls' => array_slice($contentResult['sitemap']['suspicious_urls'] ?? [], 0, 10),
                ],
            ],
            'recommendations' => $recommendations,
        ];

        // Update website
        $website->update([
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode($compressedResult),
            'last_checked_at' => now(),
        ]);

        // Simpan log
        MonitoringLog::create([
            'website_id' => $website->id,
            'check_type' => 'content',
            'status' => $website->status ?? 'unknown',
            'response_time' => 0,
            'http_code' => 0,
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
            'error_message' => $contentResult['posts']['error'] ?? null,
            'raw_result' => $compressedResult,
        ]);
    }
}
