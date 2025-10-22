@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard Website Monitor</h1>
@stop

@section('content')
    {{-- Statistics Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalWebsites }}</h3>
                    <p>Total Website</p>
                </div>
                <div class="icon">
                    <i class="fas fa-globe"></i>
                </div>
                <a href="{{ route('websites.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $onlineWebsites }}</h3>
                    <p>Online</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="{{ route('websites.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $suspiciousWebsites }}</h3>
                    <p>Terdeteksi Backlink</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('websites.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $offlineWebsites }}</h3>
                    <p>Offline</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <a href="{{ route('websites.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Websites --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Website Terbaru</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Status</th>
                                <th>Terakhir Cek</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentWebsites as $website)
                            <tr>
                                <td>
                                    <strong>{{ $website->name }}</strong><br>
                                    <small class="text-muted">{{ $website->url }}</small>
                                </td>
                                <td>{!! $website->status_badge !!}</td>
                                <td>
                                    <small>{{ $website->last_checked_human }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    Belum ada website yang ditambahkan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($totalWebsites > 0)
                <div class="card-footer">
                    <a href="{{ route('websites.index') }}" class="btn btn-sm btn-primary">
                        Lihat Semua Website
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Suspicious Websites --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title">Website dengan Konten Mencurigakan</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Jumlah Post</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suspiciousDetails as $website)
                            <tr>
                                <td>
                                    <strong>{{ $website->name }}</strong><br>
                                    <small class="text-muted">{{ $website->url }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-danger">
                                        {{ $website->suspicious_posts_count }} post
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('websites.show', $website) }}" 
                                       class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-success">
                                    <i class="fas fa-check-circle"></i> 
                                    Tidak ada website dengan konten mencurigakan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Monitoring Logs --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Monitoring Terbaru</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Tipe Cek</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>Konten Mencurigakan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->website->name }}</td>
                                <td>{!! $log->check_type_badge !!}</td>
                                <td>{!! $log->status_badge !!}</td>
                                <td>{{ $log->response_time ?? 'N/A' }} ms</td>
                                <td>
                                    @if($log->has_suspicious_content)
                                        <span class="badge badge-danger">
                                            {{ $log->suspicious_posts_count }} post
                                        </span>
                                    @else
                                        <span class="badge badge-success">Clean</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $log->created_at->diffForHumans() }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada log monitoring
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    @if($totalWebsites == 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Mulai Monitoring</h3>
                </div>
                <div class="card-body text-center">
                    <p>Belum ada website yang ditambahkan. Mulai monitoring dengan menambahkan website pertama!</p>
                    <a href="{{ route('websites.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> Tambah Website
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
    <style>
        .small-box h3 {
            font-size: 2.5rem;
        }
    </style>
@stop