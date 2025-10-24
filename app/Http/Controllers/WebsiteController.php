<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use App\Models\FileChange;
use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use App\Services\RecommendationService;
use App\Services\FileMonitorService;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    protected $checkService;
    protected $scannerService;
    protected $recommendationService;
    protected $fileMonitorService;
    
    public function __construct(
        WebsiteCheckService $checkService,
        ContentScannerService $scannerService,
        RecommendationService $recommendationService,
        FileMonitorService $fileMonitorService
    ) {
        $this->checkService = $checkService;
        $this->scannerService = $scannerService;
        $this->recommendationService = $recommendationService;
        $this->fileMonitorService = $fileMonitorService;
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
            'server_path' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $website = Website::create([
            'name' => $request->name,
            'url' => $request->url,
            'server_path' => $request->server_path,
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
        
        // Load recent file changes
        $recentFileChanges = FileChange::where('website_id', $website->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('websites.show', compact('website', 'recentFileChanges'));
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
            'server_path' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $website->update($request->only(['name', 'url', 'server_path', 'notes', 'is_active']));

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
        set_time_limit(300);
        
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
     * Create baseline for file monitoring
     */
    public function createFileBaseline(Website $website)
    {
        if (empty($website->server_path)) {
            return redirect()->back()->with('error', 'Server path belum dikonfigurasi untuk website ini.');
        }

        if (!is_dir($website->server_path)) {
            return redirect()->back()->with('error', 'Server path tidak valid atau tidak dapat diakses.');
        }

        try {
            set_time_limit(600);
            $result = $this->fileMonitorService->createBaseline($website->id, $website->server_path);

            return redirect()->back()->with('success', "Baseline created: {$result['files_tracked']} files tracked.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating baseline: ' . $e->getMessage());
        }
    }

    /**
     * Scan files and compare with baseline
     */
    public function scanFiles(Website $website)
    {
        if (empty($website->server_path)) {
            return redirect()->back()->with('error', 'Server path belum dikonfigurasi untuk website ini.');
        }

        if (!is_dir($website->server_path)) {
            return redirect()->back()->with('error', 'Server path tidak valid atau tidak dapat diakses.');
        }

        try {
            set_time_limit(600);
            $result = $this->fileMonitorService->compareWithBaseline($website->id, $website->server_path);

            $message = "File scan completed: {$result['total_changes']} changes detected";
            
            if ($result['total_changes'] > 0) {
                $message .= " ({$result['changes']['new']} new, {$result['changes']['modified']} modified, {$result['changes']['deleted']} deleted)";
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error scanning files: ' . $e->getMessage());
        }
    }

    /**
     * Show file changes
     */
    public function fileChanges(Website $website)
    {
        $changes = FileChange::where('website_id', $website->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $suspiciousCount = FileChange::where('website_id', $website->id)
            ->where('is_suspicious', true)
            ->count();

        return view('websites.file-changes', compact('website', 'changes', 'suspiciousCount'));
    }

    /**
     * Perform full check (status + content scan) dengan recommendations
     */
    protected function performFullCheck(Website $website)
    {
        $website->update(['status' => 'checking']);

        $statusResult = $this->checkService->checkStatus($website->url);
        $contentResult = $this->scannerService->scanContent($website->url);

        $fullScanResult = [
            'status' => $statusResult,
            'posts' => $contentResult['posts'],
            'pages' => $contentResult['pages'], // ✅ ADDED
            'header_footer' => $contentResult['header_footer'],
            'meta' => $contentResult['meta'],
            'sitemap' => $contentResult['sitemap'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
        ];

        $recommendations = $this->recommendationService->generateRecommendations($fullScanResult);

        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['pages']['suspicious_count'] ?? 0) + // ✅ ADDED
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

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
                    'error' => $contentResult['posts']['error'] ?? null,
                ],
                // ✅ ADDED PAGES
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

        $website->update([
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode($compressedResult),
            'last_checked_at' => now(),
        ]);

        MonitoringLog::create([
            'website_id' => $website->id,
            'check_type' => 'full',
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'suspicious_posts' => array_merge(
                array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 10),
                array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 10)
            ),
            'error_message' => $statusResult['error'] ?? $contentResult['posts']['error'] ?? null,
            'raw_result' => $compressedResult,
        ]);
    }

    protected function performStatusCheck(Website $website)
    {
        $statusResult = $this->checkService->checkStatus($website->url);

        $website->update([
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'last_checked_at' => now(),
        ]);

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

    protected function performContentScan(Website $website)
    {
        $contentResult = $this->scannerService->scanContent($website->url);

        $fullScanResult = [
            'status' => ['status' => $website->status ?? 'unknown'],
            'posts' => $contentResult['posts'],
            'pages' => $contentResult['pages'], // ✅ ADDED
            'header_footer' => $contentResult['header_footer'],
            'meta' => $contentResult['meta'],
            'sitemap' => $contentResult['sitemap'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
        ];

        $recommendations = $this->recommendationService->generateRecommendations($fullScanResult);

        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['pages']['suspicious_count'] ?? 0) + // ✅ ADDED
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

        $compressedResult = [
            'content' => [
                'url' => $contentResult['url'],
                'scanned_at' => $contentResult['scanned_at'],
                'posts' => [
                    'has_suspicious' => $contentResult['posts']['has_suspicious'] ?? false,
                    'suspicious_count' => $contentResult['posts']['suspicious_count'] ?? 0,
                    'suspicious_posts' => array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 20),
                ],
                // ✅ ADDED PAGES
                'pages' => [
                    'has_suspicious' => $contentResult['pages']['has_suspicious'] ?? false,
                    'suspicious_count' => $contentResult['pages']['suspicious_count'] ?? 0,
                    'suspicious_pages' => array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 20),
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

        $website->update([
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode($compressedResult),
            'last_checked_at' => now(),
        ]);

        MonitoringLog::create([
            'website_id' => $website->id,
            'check_type' => 'content',
            'status' => $website->status ?? 'unknown',
            'response_time' => 0,
            'http_code' => 0,
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'suspicious_posts' => array_merge(
                array_slice($contentResult['posts']['suspicious_posts'] ?? [], 0, 10),
                array_slice($contentResult['pages']['suspicious_pages'] ?? [], 0, 10)
            ),
            'error_message' => $contentResult['posts']['error'] ?? null,
            'raw_result' => $compressedResult,
        ]);
    }
}
