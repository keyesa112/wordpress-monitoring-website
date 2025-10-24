@extends('adminlte::page')

@section('title', 'Detail Website')

@section('content_header')
    <h1>Detail Website</h1>
@stop

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
    @endif

    {{-- File Changes Alert --}}
    @if(isset($recentFileChanges) && $recentFileChanges->where('is_suspicious', true)->count() > 0)
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5><i class="icon fas fa-exclamation-triangle"></i> File Monitoring Alert!</h5>
        Terdeteksi <strong>{{ $recentFileChanges->where('is_suspicious', true)->count() }} file mencurigakan</strong> dalam monitoring terakhir.
        <a href="{{ route('websites.file-changes', $website) }}" class="alert-link">Lihat Detail →</a>
    </div>
    @endif

    {{-- Website Info --}}
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi Website</h3>
                    <div class="card-tools">
                        <form action="{{ route('websites.check', $website) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-sync"></i> Cek Ulang
                            </button>
                        </form>
                        <a href="{{ route('websites.edit', $website) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px">Nama</th>
                            <td>{{ $website->name }}</td>
                        </tr>
                        <tr>
                            <th>URL</th>
                            <td>
                                <a href="{{ $website->url }}" target="_blank">
                                    {{ $website->url }} <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{!! $website->status_badge !!}</td>
                        </tr>
                        <tr>
                            <th>Response Time</th>
                            <td>{{ $website->formatted_response_time }}</td>
                        </tr>
                        <tr>
                            <th>HTTP Code</th>
                            <td>{{ $website->http_code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Konten Mencurigakan</th>
                            <td>{!! $website->suspicious_badge !!}</td>
                        </tr>
                        <tr>
                            <th>Terakhir Dicek</th>
                            <td>{{ $website->last_checked_human }}</td>
                        </tr>
                        <tr>
                            <th>Status Monitoring</th>
                            <td>
                                @if($website->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        @if($website->server_path)
                        <tr>
                            <th>Server Path</th>
                            <td>
                                <code>{{ $website->server_path }}</code>
                                <span class="badge badge-info ml-2">
                                    <i class="fas fa-folder"></i> FIM Enabled
                                </span>
                            </td>
                        </tr>
                        @endif
                        @if($website->notes)
                        <tr>
                            <th>Catatan</th>
                            <td>{{ $website->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-history"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pengecekan</span>
                    <span class="info-box-number">{{ $website->monitoringLogs->count() }}</span>
                </div>
            </div>

            @if($website->has_suspicious_content)
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Konten Mencurigakan</span>
                    <span class="info-box-number">{{ $website->suspicious_posts_count }}</span>
                </div>
            </div>
            @endif

            {{-- File Changes Info Box --}}
            @if(isset($recentFileChanges) && $recentFileChanges->count() > 0)
            <div class="info-box bg-{{ $recentFileChanges->where('is_suspicious', true)->count() > 0 ? 'danger' : 'secondary' }}">
                <span class="info-box-icon"><i class="fas fa-file-code"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Perubahan File</span>
                    <span class="info-box-number">{{ $recentFileChanges->count() }}</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $recentFileChanges->where('is_suspicious', true)->count() > 0 ? '100' : '0' }}%"></div>
                    </div>
                    <span class="progress-description">
                        {{ $recentFileChanges->where('is_suspicious', true)->count() }} suspicious
                    </span>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aksi Cepat</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('websites.edit', $website) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit"></i> Edit Website
                    </a>
                    <form action="{{ route('websites.check', $website) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-sync"></i> Cek Ulang Sekarang
                        </button>
                    </form>
                    
                    {{-- File Monitoring Actions --}}
                    @if($website->server_path)
                    <hr>
                    <h6 class="text-muted mb-2"><i class="fas fa-shield-alt"></i> File Integrity Monitoring</h6>
                    <form action="{{ route('websites.file-baseline', $website) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info btn-block btn-sm" title="Create initial file snapshot">
                            <i class="fas fa-camera"></i> Create Baseline
                        </button>
                    </form>
                    <form action="{{ route('websites.file-scan', $website) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block btn-sm" title="Scan for file changes">
                            <i class="fas fa-search"></i> Scan Files
                        </button>
                    </form>
                    <a href="{{ route('websites.file-changes', $website) }}" class="btn btn-secondary btn-block btn-sm">
                        <i class="fas fa-list"></i> View All Changes
                    </a>
                    @else
                    <hr>
                    <div class="alert alert-warning mb-0 py-2">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            <strong>File Monitoring belum aktif.</strong><br>
                            Isi Server Path di <a href="{{ route('websites.edit', $website) }}" class="alert-link">Edit</a> untuk mengaktifkan FIM.
                        </small>
                    </div>
                    @endif
                    
                    <hr>
                    <form action="{{ route('websites.destroy', $website) }}" 
                          method="POST" 
                          onsubmit="return confirm('Yakin ingin menghapus website ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash"></i> Hapus Website
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent File Changes Section --}}
    @if(isset($recentFileChanges) && $recentFileChanges->count() > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card card-warning collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-code"></i> 
                        Perubahan File Terbaru (10 Terakhir)
                        @php
                            $newCount = $recentFileChanges->where('change_type', 'new')->count();
                            $modifiedCount = $recentFileChanges->where('change_type', 'modified')->count();
                            $deletedCount = $recentFileChanges->where('change_type', 'deleted')->count();
                            $suspiciousCount = $recentFileChanges->where('is_suspicious', true)->count();
                        @endphp
                        @if($suspiciousCount > 0)
                            <span class="badge badge-danger ml-2">{{ $suspiciousCount }} Suspicious</span>
                        @endif
                        @if($newCount > 0)
                            <span class="badge badge-success ml-1">{{ $newCount }} New</span>
                        @endif
                        @if($modifiedCount > 0)
                            <span class="badge badge-warning ml-1">{{ $modifiedCount }} Modified</span>
                        @endif
                        @if($deletedCount > 0)
                            <span class="badge badge-secondary ml-1">{{ $deletedCount }} Deleted</span>
                        @endif
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0" style="display: none;">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45%">File Path</th>
                                <th style="width: 10%">Type</th>
                                <th style="width: 10%">Severity</th>
                                <th style="width: 20%">Detected At</th>
                                <th style="width: 15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentFileChanges as $change)
                            <tr class="{{ $change->is_suspicious ? 'table-danger' : '' }}">
                                <td>
                                    <small><code>{{ Str::limit($change->file_path, 60) }}</code></small>
                                    @if($change->is_suspicious)
                                        <i class="fas fa-exclamation-triangle text-danger ml-1" title="Suspicious file"></i>
                                    @endif
                                </td>
                                <td>{!! $change->change_type_badge !!}</td>
                                <td>{!! $change->severity_badge !!}</td>
                                <td>
                                    <small>{{ $change->created_at->format('d M Y H:i') }}</small>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-info" 
                                            data-toggle="modal" 
                                            data-target="#fileModal{{ $change->id }}">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="card-footer text-center">
                        <a href="{{ route('websites.file-changes', $website) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-list"></i> View All Changes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- File Change Detail Modals --}}
    @foreach($recentFileChanges as $change)
    <div class="modal fade" id="fileModal{{ $change->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-{{ $change->severity === 'critical' ? 'danger' : ($change->severity === 'warning' ? 'warning' : 'info') }}">
                    <h5 class="modal-title">
                        <i class="fas fa-file-code"></i> File Change Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">File Path:</dt>
                        <dd class="col-sm-9"><code>{{ $change->file_path }}</code></dd>
                        
                        <dt class="col-sm-3">Change Type:</dt>
                        <dd class="col-sm-9">{!! $change->change_type_badge !!}</dd>
                        
                        <dt class="col-sm-3">Severity:</dt>
                        <dd class="col-sm-9">{!! $change->severity_badge !!}</dd>
                        
                        <dt class="col-sm-3">Suspicious:</dt>
                        <dd class="col-sm-9">
                            @if($change->is_suspicious)
                                <span class="badge badge-danger">YES</span>
                            @else
                                <span class="badge badge-success">NO</span>
                            @endif
                        </dd>
                        
                        @if($change->suspicious_patterns)
                        <dt class="col-sm-3">Patterns Found:</dt>
                        <dd class="col-sm-9">
                            @foreach($change->suspicious_patterns as $pattern)
                                <span class="badge badge-danger mr-1 mb-1">{{ $pattern }}</span>
                            @endforeach
                        </dd>
                        @endif
                        
                        <dt class="col-sm-3">Recommendation:</dt>
                        <dd class="col-sm-9">{{ $change->recommendation }}</dd>
                        
                        @if($change->file_preview)
                        <dt class="col-sm-3">File Preview:</dt>
                        <dd class="col-sm-9">
                            <pre class="bg-light p-2 border" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">{{ $change->file_preview }}</pre>
                        </dd>
                        @endif
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif

    {{-- Full Scan Results dengan Accordion --}}
    @if($website->has_suspicious_content && $website->last_check_result)
    @php
        $lastResult = json_decode($website->last_check_result, true);
        
        $contentScan = $lastResult['content'] ?? [];
        
        // Posts
        $posts = $contentScan['posts'] ?? [];
        $suspiciousPosts = $posts['suspicious_posts'] ?? [];
        
        // Fallback ke format lama
        if (empty($suspiciousPosts)) {
            $suspiciousPosts = $lastResult['backlink']['suspicious_posts'] ?? [];
        }
        
        // ✅ PAGES
        $pages = $contentScan['pages'] ?? [];
        $suspiciousPages = $pages['suspicious_pages'] ?? [];
        
        // Header/Footer
        $headerFooter = $contentScan['header_footer'] ?? [];
        $headerFooterKeywords = $headerFooter['keywords'] ?? [];
        
        // Meta
        $meta = $contentScan['meta'] ?? [];
        $metaKeywords = $meta['keywords'] ?? [];
        
        // Sitemap
        $sitemap = $contentScan['sitemap'] ?? [];
        $sitemapKeywords = $sitemap['keywords'] ?? [];
        $sitemapUrls = $sitemap['suspicious_urls'] ?? [];
        
        // Hitung total section
        $totalSections = 0;
        if (!empty($suspiciousPosts)) $totalSections++;
        if (!empty($suspiciousPages)) $totalSections++; // ✅ ADDED
        if (!empty($headerFooterKeywords)) $totalSections++;
        if (!empty($metaKeywords)) $totalSections++;
        if (!empty($sitemapKeywords)) $totalSections++;
    @endphp
    
    <div class="row">
        <div class="col-md-12">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> 
                        Hasil Deteksi Konten Mencurigakan 
                        @if($totalSections > 0)
                            <span class="badge badge-warning">{{ $totalSections }} Area Terdeteksi</span>
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div id="accordionDetection">
                        
                        {{-- Posts Section --}}
                        @if(!empty($suspiciousPosts))
                        <div class="card">
                            <div class="card-header" id="headingPosts">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapsePosts" aria-expanded="false" aria-controls="collapsePosts">
                                        <i class="fas fa-file-alt text-danger"></i> 
                                        <strong>Posts Mencurigakan ({{ count($suspiciousPosts) }})</strong>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapsePosts" class="collapse" aria-labelledby="headingPosts" data-parent="#accordionDetection">
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%">Judul Post</th>
                                                <th style="width: 35%">Keyword Terdeteksi</th>
                                                <th style="width: 15%">Tanggal</th>
                                                <th style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($suspiciousPosts as $post)
                                            <tr>
                                                <td>{{ $post['title'] }}</td>
                                                <td>
                                                    @foreach($post['keywords'] as $keyword)
                                                        <span class="badge badge-danger">{{ $keyword }}</span>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <small>{{ \Carbon\Carbon::parse($post['date'])->format('d M Y') }}</small>
                                                </td>
                                                <td>
                                                    <a href="{{ $post['link'] }}" target="_blank" class="btn btn-xs btn-primary">
                                                        <i class="fas fa-external-link-alt"></i> Buka
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- ✅ PAGES SECTION (NEW) --}}
                        @if(!empty($suspiciousPages))
                        <div class="card">
                            <div class="card-header" id="headingPages">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                        <i class="fas fa-file text-danger"></i> 
                                        <strong>Pages Mencurigakan ({{ count($suspiciousPages) }})</strong>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionDetection">
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%">Judul Page</th>
                                                <th style="width: 35%">Keyword Terdeteksi</th>
                                                <th style="width: 15%">Tanggal</th>
                                                <th style="width: 10%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($suspiciousPages as $page)
                                            <tr>
                                                <td>{{ $page['title'] }}</td>
                                                <td>
                                                    @foreach($page['keywords'] as $keyword)
                                                        <span class="badge badge-danger">{{ $keyword }}</span>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <small>{{ \Carbon\Carbon::parse($page['date'])->format('d M Y') }}</small>
                                                </td>
                                                <td>
                                                    <a href="{{ $page['link'] }}" target="_blank" class="btn btn-xs btn-primary">
                                                        <i class="fas fa-external-link-alt"></i> Buka
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Header/Footer Section --}}
                        @if(!empty($headerFooterKeywords))
                        <div class="card">
                            <div class="card-header" id="headingHeader">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseHeader" aria-expanded="false" aria-controls="collapseHeader">
                                        <i class="fas fa-code text-warning"></i> 
                                        <strong>Header & Footer ({{ count($headerFooterKeywords) }} keyword)</strong>
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseHeader" class="collapse" aria-labelledby="headingHeader" data-parent="#accordionDetection">
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Keyword mencurigakan ditemukan di bagian HTML header atau footer website.
                                    </div>
                                    <p><strong>Keyword yang terdeteksi:</strong></p>
                                    <p>
                                        @foreach($headerFooterKeywords as $keyword)
                                            <span class="badge badge-danger badge-lg mr-1 mb-1">{{ $keyword }}</span>
                                        @endforeach
                                    </p>
                                    <a href="{{ $website->url }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-external-link-alt"></i> Buka Website
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Meta Tags Section --}}
                        @if(!empty($metaKeywords))
                        <div class="card">
                            <div class="card-header" id="headingMeta">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseMeta" aria-expanded="false" aria-controls="collapseMeta">
                                        <i class="fas fa-tags text-info"></i> 
                                        <strong>Meta Tags ({{ count($metaKeywords) }} keyword)</strong>
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseMeta" class="collapse" aria-labelledby="headingMeta" data-parent="#accordionDetection">
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> 
                                        Keyword mencurigakan ditemukan di meta tags (title, description, keywords).
                                    </div>
                                    
                                    @if(!empty($meta['meta_title']))
                                    <p><strong>Meta Title:</strong><br>
                                    <code>{{ $meta['meta_title'] }}</code></p>
                                    @endif
                                    
                                    @if(!empty($meta['meta_description']))
                                    <p><strong>Meta Description:</strong><br>
                                    <code>{{ $meta['meta_description'] }}</code></p>
                                    @endif
                                    
                                    <p><strong>Keyword yang terdeteksi:</strong></p>
                                    <p>
                                        @foreach($metaKeywords as $keyword)
                                            <span class="badge badge-danger badge-lg mr-1 mb-1">{{ $keyword }}</span>
                                        @endforeach
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Sitemap Section --}}
                        @if(!empty($sitemapKeywords))
                        <div class="card">
                            <div class="card-header" id="headingSitemap">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseSitemap" aria-expanded="false" aria-controls="collapseSitemap">
                                        <i class="fas fa-sitemap text-secondary"></i> 
                                        <strong>Sitemap ({{ count($sitemapUrls) }} URL mencurigakan)</strong>
                                    </button>
                                </h5>
                            </div>
                            <div id="collapseSitemap" class="collapse" aria-labelledby="headingSitemap" data-parent="#accordionDetection">
                                <div class="card-body">
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-search"></i> 
                                        URL mencurigakan ditemukan di sitemap.xml website.
                                    </div>
                                    
                                    <p><strong>Keyword yang terdeteksi:</strong></p>
                                    <p>
                                        @foreach($sitemapKeywords as $keyword)
                                            <span class="badge badge-danger badge-lg mr-1 mb-1">{{ $keyword }}</span>
                                        @endforeach
                                    </p>
                                    
                                    @if(!empty($sitemapUrls))
                                    <p><strong>URL Mencurigakan:</strong></p>
                                    <ul class="list-unstyled">
                                        @foreach(array_slice($sitemapUrls, 0, 10) as $suspiciousUrl)
                                        <li class="mb-2">
                                            <a href="{{ $suspiciousUrl }}" target="_blank" class="text-danger">
                                                <i class="fas fa-external-link-alt"></i> {{ $suspiciousUrl }}
                                            </a>
                                        </li>
                                        @endforeach
                                        
                                        @if(count($sitemapUrls) > 10)
                                        <li class="text-muted">
                                            <em>... dan {{ count($sitemapUrls) - 10 }} URL lainnya</em>
                                        </li>
                                        @endif
                                    </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Recommendations Section --}}
    @if($website->last_check_result)
    @php
        $lastResult = json_decode($website->last_check_result, true);
        $recommendations = $lastResult['recommendations'] ?? [];
        $recList = $recommendations['recommendations'] ?? [];
        $criticalCount = $recommendations['critical_count'] ?? 0;
        $highCount = $recommendations['high_count'] ?? 0;
    @endphp

    @if(!empty($recList))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb"></i> 
                        Rekomendasi Penanganan
                        <span class="badge badge-light ml-2">{{ $recommendations['total'] ?? 0 }}</span>
                        @if($criticalCount > 0)
                            <span class="badge badge-danger ml-1">{{ $criticalCount }} Critical</span>
                        @endif
                        @if($highCount > 0)
                            <span class="badge badge-warning ml-1">{{ $highCount }} High</span>
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div id="accordionRecommendations">
                        @foreach($recList as $index => $rec)
                        <div class="card">
                            <div class="card-header" id="headingRec{{ $index }}">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" 
                                            data-toggle="collapse" 
                                            data-target="#collapseRec{{ $index }}" 
                                            aria-expanded="false" 
                                            aria-controls="collapseRec{{ $index }}">
                                        @if($rec['severity'] === 'critical')
                                            <i class="fas fa-exclamation-circle text-danger"></i>
                                        @elseif($rec['severity'] === 'high')
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        @elseif($rec['severity'] === 'success')
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-info-circle text-info"></i>
                                        @endif
                                        <strong>{{ $rec['title'] }}</strong>
                                        <span class="badge 
                                            @if($rec['severity'] === 'critical') badge-danger
                                            @elseif($rec['severity'] === 'high') badge-warning
                                            @elseif($rec['severity'] === 'success') badge-success
                                            @else badge-info
                                            @endif ml-2">
                                            {{ ucfirst($rec['severity']) }}
                                        </span>
                                    </button>
                                </h5>
                            </div>

                            <div id="collapseRec{{ $index }}" 
                                 class="collapse" 
                                 aria-labelledby="headingRec{{ $index }}" 
                                 data-parent="#accordionRecommendations">
                                <div class="card-body">
                                    <div class="alert alert-light border-left-{{ $rec['severity'] === 'critical' ? 'danger' : ($rec['severity'] === 'high' ? 'warning' : 'info') }}">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Deskripsi:</strong> {{ $rec['description'] }}
                                    </div>
                                    
                                    @if(!empty($rec['recommendations']))
                                    <h6 class="font-weight-bold text-success mt-3">
                                        <i class="fas fa-check-circle"></i> Langkah Penanganan:
                                    </h6>
                                    <ol class="mb-3 pl-4">
                                        @foreach($rec['recommendations'] as $recommendation)
                                        <li class="mb-2">{{ $recommendation }}</li>
                                        @endforeach
                                    </ol>
                                    @endif
                                    
                                    @if(!empty($rec['actions']))
                                    <h6 class="font-weight-bold text-primary">
                                        <i class="fas fa-wrench"></i> Cara Implementasi:
                                    </h6>
                                    <ul class="mb-3 pl-4">
                                        @foreach($rec['actions'] as $action)
                                        <li class="mb-2">{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                    @endif
                                    
                                    @if(!empty($rec['prevention']))
                                    <h6 class="font-weight-bold text-info">
                                        <i class="fas fa-shield-alt"></i> Pencegahan Ke Depan:
                                    </h6>
                                    <ul class="mb-0 pl-4">
                                        @foreach($rec['prevention'] as $prevention)
                                        <li class="mb-2">{{ $prevention }}</li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- Monitoring Logs --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Monitoring (20 Terakhir)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe Cek</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>HTTP Code</th>
                                <th>Konten Mencurigakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($website->monitoringLogs as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('d M Y H:i:s') }}</small><br>
                                    <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </td>
                                <td>{!! $log->check_type_badge !!}</td>
                                <td>{!! $log->status_badge !!}</td>
                                <td>{{ $log->response_time ?? 'N/A' }} ms</td>
                                <td>{{ $log->http_code ?? 'N/A' }}</td>
                                <td>
                                    @if($log->has_suspicious_content)
                                        <span class="badge badge-danger">
                                            {{ $log->suspicious_posts_count }} item
                                        </span>
                                    @else
                                        <span class="badge badge-success">Clean</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada riwayat monitoring
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .badge-lg {
        font-size: 0.9rem;
        padding: 0.4rem 0.6rem;
    }
    
    #accordionRecommendations .card,
    #accordionDetection .card {
        margin-bottom: 0.5rem;
        border: 1px solid #dee2e6;
    }
    
    #accordionRecommendations .btn-link,
    #accordionDetection .btn-link {
        text-decoration: none;
        color: #333;
        font-size: 1rem;
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.75rem 1rem;
    }
    
    #accordionRecommendations .btn-link:hover,
    #accordionDetection .btn-link:hover {
        text-decoration: none;
        background-color: #f8f9fa;
    }
    
    #accordionRecommendations .card-header,
    #accordionDetection .card-header {
        padding: 0;
        background-color: #fff;
    }
    
    .border-left-danger {
        border-left: 3px solid #dc3545 !important;
    }
    
    .border-left-warning {
        border-left: 3px solid #ffc107 !important;
    }
    
    .border-left-info {
        border-left: 3px solid #17a2b8 !important;
    }
</style>
@stop
