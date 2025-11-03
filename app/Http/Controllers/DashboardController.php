<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        $totalWebsites = Website::where('user_id', $userId)->count();
        $onlineWebsites = Website::where('user_id', $userId)
                                 ->where('status', 'online')
                                 ->count();
        $offlineWebsites = Website::where('user_id', $userId)
                                  ->where('status', 'offline')
                                  ->count();
        $suspiciousWebsites = Website::where('user_id', $userId)
                                     ->where('has_suspicious_content', true)
                                     ->count();
        
        $recentWebsites = Website::where('user_id', $userId)
                                 ->with('latestLog')
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get();
   
        $suspiciousDetails = Website::where('user_id', $userId)
                                    ->where('has_suspicious_content', true)
                                    ->orderBy('suspicious_posts_count', 'desc')
                                    ->limit(5)
                                    ->get();
        
        $recentLogs = MonitoringLog::where('user_id', $userId)
                                   ->with('website')
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
