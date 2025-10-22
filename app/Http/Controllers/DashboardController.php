<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Total statistics
        $totalWebsites = Website::count();
        $onlineWebsites = Website::where('status', 'online')->count();
        $offlineWebsites = Website::where('status', 'offline')->count();
        $suspiciousWebsites = Website::where('has_suspicious_content', true)->count();
        
        // Recent websites
        $recentWebsites = Website::with('latestLog')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Websites with suspicious content
        $suspiciousDetails = Website::where('has_suspicious_content', true)
            ->orderBy('suspicious_posts_count', 'desc')
            ->limit(5)
            ->get();
        
        // Recent logs
        $recentLogs = MonitoringLog::with('website')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalWebsites',
            'onlineWebsites',
            'offlineWebsites',
            'suspiciousWebsites',
            'recentWebsites',
            'suspiciousDetails',
            'recentLogs'
        ));
    }
}