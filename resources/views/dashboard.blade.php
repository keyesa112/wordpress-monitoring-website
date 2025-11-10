@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-chart-line text-primary"></i> Dashboard Website Monitor
        </h1>
        <span class="text-muted" style="font-size: 0.9rem;">
            <i class="far fa-calendar"></i> 
            Last Updated: {{ now()->format('d M Y H:i') }}
        </span>
    </div>
@stop

@section('content')

    {{-- Warning Alert for REST API Requirement --}}
    <div class="alert alert-warning d-flex align-items-center mb-4 shadow-sm" role="alert" style="border-left: 4px solid #f39c12;">
        <div style="font-size: 2rem; margin-right: 1rem;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <strong><i class="fas fa-info-circle"></i> Perhatian:</strong> 
            Tool monitoring ini memerlukan <strong>WordPress REST API</strong> yang aktif. 
            Jika situs Anda menonaktifkan REST API, fitur monitoring akan terbatas atau tidak berfungsi.
            <a href="{{ route('guidelines.index') }}" class="alert-link">Baca panduan lengkap →</a>
        </div>
    </div>
    
    {{-- Statistics from Logs --}}
    <div class="row">
        {{-- Total Scans --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3>{{ $totalScans ?? 0 }}</h3>
                    <p><i class="fas fa-sync"></i> Total Scans</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                
            </div>
        </div>
        
        {{-- Online Status --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3>
                        {{ $onlineScans ?? 0 }}
                        <small style="font-size: 0.4em; font-weight: normal;">/{{ $totalScans ?? 0 }}</small>
                    </h3>
                    <p><i class="fas fa-check-circle"></i> Online ({{ $onlinePercent ?? 0 }}%)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
            </div>
        </div>
        
        {{-- Suspicious Content --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner">
                    <h3>
                        {{ $suspiciousScans ?? 0 }}
                        <small style="font-size: 0.4em; font-weight: normal;">/{{ $totalScans ?? 0 }}</small>
                    </h3>
                    <p><i class="fas fa-exclamation-triangle"></i> Suspicious ({{ $suspiciousPercent ?? 0 }}%)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
            </div>
        </div>
        
        {{-- Offline Status --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger shadow-sm">
                <div class="inner">
                    <h3>
                        {{ $offlineScans ?? 0 }}
                        <small style="font-size: 0.4em; font-weight: normal;">/{{ $totalScans ?? 0 }}</small>
                    </h3>
                    <p><i class="fas fa-times-circle"></i> Offline ({{ $offlinePercent ?? 0 }}%)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                
            </div>
        </div>
    </div>

    {{-- Performance Metrics --}}
    <div class="row mt-3">
        {{-- Average Response Time --}}
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-info">
                    <i class="fas fa-tachometer-alt"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">
                        <i class="fas fa-clock"></i> Avg Response Time
                    </span>
                    <span class="info-box-number">{{ round($avgResponseTime ?? 0) }} ms</span>
                </div>
            </div>
        </div>
        
        {{-- Online Rate --}}
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-success">
                    <i class="fas fa-arrow-up"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">
                        <i class="fas fa-heartbeat"></i> Online Rate
                    </span>
                    <span class="info-box-number">{{ $onlinePercent ?? 0 }}%</span>
                </div>
            </div>
        </div>
        
        {{-- Suspicious Rate --}}
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-warning">
                    <i class="fas fa-exclamation"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">
                        <i class="fas fa-alert"></i> Suspicious Rate
                    </span>
                    <span class="info-box-number">{{ $suspiciousPercent ?? 0 }}%</span>
                </div>
            </div>
        </div>
        
        {{-- Downtime Rate --}}
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-danger">
                    <i class="fas fa-arrow-down"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">
                        <i class="fas fa-power-off"></i> Downtime Rate
                    </span>
                    <span class="info-box-number">{{ $offlinePercent ?? 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Monitoring Logs --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Recent Monitoring Logs
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">
                            <i class="fas fa-eye"></i> Latest 10 Scans
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>Website</th>
                                    <th style="width: 120px;">Check Type</th>
                                    <th style="width: 110px;">Status</th>
                                    <th style="width: 130px;">Response Time</th>
                                    <th style="width: 140px;">Suspicious</th>
                                    <th style="width: 140px;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $key => $log)
                                <tr style="cursor: pointer; transition: all 0.3s ease;" 
                                    onclick="window.location='{{ route('websites.show', $log->website) }}'"
                                    onmouseover="this.style.backgroundColor='#f0f0f0'"
                                    onmouseout="this.style.backgroundColor=''">
                                    <td class="text-center font-weight-bold">{{ $key + 1 }}</td>
                                    <td>
                                        <strong>{{ $log->website->name }}</strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-link"></i> {{ Str::limit($log->website->url, 40) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($log->check_type === 'full')
                                            <span class="badge badge-info">
                                                <i class="fas fa-sync"></i> Full Scan
                                            </span>
                                        @elseif($log->check_type === 'status')
                                            <span class="badge badge-primary">
                                                <i class="fas fa-heartbeat"></i> Status
                                            </span>
                                        @elseif($log->check_type === 'content')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-search"></i> Content
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">{{ $log->check_type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->status === 'online')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Online
                                            </span>
                                        @elseif($log->status === 'offline')
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Offline
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">{{ $log->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if($log->response_time)
                                                <i class="fas fa-clock"></i> {{ $log->response_time }} ms
                                            @else
                                                <span>N/A</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @if($log->has_suspicious_content)
                                            <span class="badge badge-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                {{ $log->suspicious_posts_count ?? 0 }} post
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Clean
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> {{ $log->created_at->diffForHumans() }}
                                        </small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                        <strong>No monitoring logs yet</strong><br>
                                        <small>Start by <a href="{{ route('websites.index') }}">adding a website</a></small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Showing latest 10 scans. 
                        <a href="{{ route('websites.index') }}" class="text-primary font-weight-bold">
                            Go to Websites Dashboard
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* DASHBOARD STYLING */
    .small-box {
        border-radius: 0.5rem;
        position: relative;
        display: block;
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .small-box:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
        transform: translateY(-2px);
    }

    .small-box > .inner {
        padding: 12px 15px;
    }

    .small-box h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        white-space: nowrap;
        padding: 0;
    }

    .small-box p {
        font-size: 0.9rem;
        margin: 0;
        font-weight: 500;
    }

    .small-box .icon {
        border-radius: 2px;
        height: 65px;
        width: 65px;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        right: 15px;
        top: 15px;
        opacity: 0.15;
    }

    .small-box-footer {
        display: block;
        padding: 6px 10px;
        background: rgba(0,0,0,.1);
        text-align: center;
        color: #fff;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.3s ease;
    }

    .small-box-footer:hover {
        background: rgba(0,0,0,.2);
        color: #fff;
        text-decoration: none;
    }

    /* Info Box */
    .info-box {
        display: flex;
        margin-bottom: 20px;
        min-height: 90px;
        background: #fff;
        padding: 15px;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .info-box:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
        transform: translateY(-2px);
    }

    .info-box-icon {
        border-radius: 2px;
        height: 90px;
        width: 90px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 15px;
        font-size: 2rem;
        color: #fff;
    }

    .info-box-content {
        display: flex;
        width: 100%;
        flex-direction: column;
        justify-content: center;
    }

    .info-box-text {
        display: block;
        font-size: 0.9rem;
        white-space: nowrap;
        color: #888;
        font-weight: 500;
    }

    .info-box-number {
        display: block;
        font-weight: 700;
        color: #333;
        font-size: 1.8rem;
    }

    /* Background Colors */
    .bg-info { background-color: #17a2b8 !important; }
    .bg-success { background-color: #28a745 !important; }
    .bg-warning { background-color: #ffc107 !important; }
    .bg-danger { background-color: #dc3545 !important; }

    /* Table */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    .table-sm th,
    .table-sm td {
        font-size: 0.9rem;
        padding: 0.6rem;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.4rem;
    }

    /* Card */
    .card {
        border: none;
        border-radius: 0.5rem;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* Utilities */
    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    .m-0 { margin: 0 !important; }
    .mt-3 { margin-top: 1rem !important; }
    .mt-4 { margin-top: 1.5rem !important; }
    .mb-0 { margin-bottom: 0 !important; }
    .mb-3 { margin-bottom: 1rem !important; }
    .py-5 { padding-top: 3rem !important; padding-bottom: 3rem !important; }
    .text-muted { color: #6c757d; }
    .font-weight-bold { font-weight: 700; }
</style>
@stop

@section('js')
<script>
// Hover effects
document.querySelectorAll('.small-box, .info-box').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.cursor = 'pointer';
    });
});
</script>
@stop
