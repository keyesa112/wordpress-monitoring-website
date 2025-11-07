<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get all logs untuk user ini
        $logs = MonitoringLog::whereHas('website', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();
        
        // Calculate statistics dari logs
        $totalScans = $logs->count();
        $onlineScans = $logs->where('status', 'online')->count();
        $offlineScans = $logs->where('status', 'offline')->count();
        $suspiciousScans = $logs->where('has_suspicious_content', true)->count();
        
        // Persentase
        $onlinePercent = $totalScans > 0 ? round(($onlineScans / $totalScans) * 100, 1) : 0;
        $offlinePercent = $totalScans > 0 ? round(($offlineScans / $totalScans) * 100, 1) : 0;
        $suspiciousPercent = $totalScans > 0 ? round(($suspiciousScans / $totalScans) * 100, 1) : 0;
        
        // Average response time
        $avgResponseTime = $logs->average('response_time') ?? 0;
        
        // Recent logs
        $recentLogs = MonitoringLog::whereHas('website', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // GANTI INI KE DASHBOARD YANG SUDAH ADA
        return view('dashboard', [
            'totalScans' => $totalScans,
            'onlineScans' => $onlineScans,
            'offlineScans' => $offlineScans,
            'suspiciousScans' => $suspiciousScans,
            'onlinePercent' => $onlinePercent,
            'offlinePercent' => $offlinePercent,
            'suspiciousPercent' => $suspiciousPercent,
            'avgResponseTime' => round($avgResponseTime, 2),
            'recentLogs' => $recentLogs,
        ]);
    }
}
