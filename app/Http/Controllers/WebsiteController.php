<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use App\Services\WebsiteCheckService;
use App\Services\ContentScannerService;
use Illuminate\Http\Request;

class WebsiteController extends Controller
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
        $this->performContentScan($website);

        return redirect()->back()
            ->with('success', 'Konten website berhasil discan!');
    }

    /**
     * Perform full check (status + content scan) dan simpan ke database
     */
    protected function performFullCheck(Website $website)
    {
        // Update status ke checking
        $website->update(['status' => 'checking']);

        // Cek status (ping)
        $statusResult = $this->checkService->checkStatus($website->url);
        
        // Scan konten lengkap (posts, header/footer, meta, sitemap)
        $contentResult = $this->scannerService->scanContent($website->url);

        // Hitung total suspicious items
        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

        // Update website
        $website->update([
            'status' => $statusResult['status'],
            'response_time' => $statusResult['response_time'],
            'http_code' => $statusResult['http_code'],
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode([
                'status' => $statusResult,
                'content' => $contentResult,
            ]),
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
            'suspicious_posts' => $contentResult['posts']['suspicious_posts'] ?? [],
            'error_message' => $statusResult['error'] ?? $contentResult['posts']['error'] ?? null,
            'raw_result' => [
                'status' => $statusResult,
                'content' => $contentResult,
            ],
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

        // Hitung total suspicious items
        $totalSuspicious = 
            ($contentResult['posts']['suspicious_count'] ?? 0) +
            ($contentResult['header_footer']['keyword_count'] ?? 0) +
            ($contentResult['meta']['keyword_count'] ?? 0) +
            ($contentResult['sitemap']['keyword_count'] ?? 0);

        // Update website
        $website->update([
            'has_suspicious_content' => $contentResult['has_suspicious_content'],
            'suspicious_posts_count' => $totalSuspicious,
            'last_check_result' => json_encode([
                'content' => $contentResult,
            ]),
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
            'suspicious_posts' => $contentResult['posts']['suspicious_posts'] ?? [],
            'error_message' => $contentResult['posts']['error'] ?? null,
            'raw_result' => [
                'content' => $contentResult,
            ],
        ]);
    }
}
