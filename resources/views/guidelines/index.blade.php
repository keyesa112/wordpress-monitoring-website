@extends('adminlte::page')

@section('title', 'Panduan Monitoring')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-book-open"></i> Panduan Monitoring</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Panduan</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Cara Kerja Sistem Monitoring</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> 
                        <strong>Penting untuk Dibaca!</strong> 
                        Pahami cara kerja sistem monitoring ini untuk hasil optimal.
                    </div>

                    <h4><i class="fas fa-cogs"></i> Metode Scanning</h4>
                    <p>Sistem ini menggunakan 3 metode untuk mendeteksi konten suspicious:</p>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">WordPress REST API</span>
                                    <span class="info-box-number">Metode Terbaik ✅</span>
                                    <small>Scan lengkap semua post & page</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-code"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Header/Footer/Meta</span>
                                    <span class="info-box-number">Metode Alternatif ⚠️</span>
                                    <small>Scan HTML homepage saja</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-sitemap"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sitemap XML</span>
                                    <span class="info-box-number">Metode Tambahan ℹ️</span>
                                    <small>Cek URL suspicious di sitemap</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h4><i class="fas fa-exclamation-triangle text-warning"></i> Keterbatasan Sistem</h4>
                    
                    <div class="callout callout-warning">
                        <h5>REST API Tidak Aktif</h5>
                        <p>Jika WordPress REST API disabled atau bukan WordPress, sistem hanya scan <strong>homepage</strong> saja. 
                        Konten suspicious di halaman lain <strong>tidak terdeteksi</strong>.</p>
                        <p><strong>Solusi:</strong> Aktifkan REST API atau lakukan pengecekan manual berkala.</p>
                    </div>

                    <div class="callout callout-info">
                        <h5>JavaScript-Rendered Content</h5>
                        <p>Konten yang di-load via JavaScript (React, Vue, Ajax) <strong>mungkin tidak terdeteksi</strong>.</p>
                        <p><strong>Solusi:</strong> Cek manual source code HTML website.</p>
                    </div>

                    <div class="callout callout-danger">
                        <h5>Obfuscated/Encrypted Content</h5>
                        <p>Konten yang di-encode atau di-hide dengan teknik advanced <strong>sulit dideteksi</strong>.</p>
                        <p><strong>Solusi:</strong> Gunakan plugin security tambahan di WordPress.</p>
                    </div>

                    <hr>

                    <h4><i class="fas fa-shield-alt text-success"></i> Best Practices</h4>
                    
                    <ul class="fa-ul">
                        <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                            <strong>Aktifkan REST API</strong> di WordPress untuk monitoring lengkap
                        </li>
                        <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                            <strong>Scan Berkala</strong> minimal 1x per hari untuk deteksi cepat
                        </li>
                        <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                            <strong>Cek Manual</strong> hasil scan dan verifikasi konten suspicious
                        </li>
                        <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                            <strong>Backup Rutin</strong> sebelum membersihkan konten suspicious
                        </li>
                        <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                            <strong>Update WordPress</strong> dan plugin ke versi terbaru
                        </li>
                    </ul>

                    <hr>

                    <h4><i class="fas fa-book text-primary"></i> Cara Mengaktifkan REST API</h4>
                    
                    <div class="alert alert-light border">
                        <ol>
                            <li>Login ke <strong>WordPress Admin</strong> (<code>yoursite.com/wp-admin</code>)</li>
                            <li>Buka <strong>Settings → Permalinks</strong></li>
                            <li>Pilih struktur permalink apa saja <strong>selain "Plain"</strong></li>
                            <li>Klik <strong>Save Changes</strong></li>
                            <li>Test: Akses <code>yoursite.com/wp-json/wp/v2/posts</code></li>
                            <li>Jika muncul JSON data → <strong>REST API aktif! ✅</strong></li>
                        </ol>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> 
                        <strong>Jika REST API masih error:</strong> 
                        Cek plugin security (Wordfence, iThemes Security) yang mungkin block REST API.
                    </div>

                </div>
                <div class="card-footer">
                    <a href="{{ route('websites.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    {{-- Custom CSS jika perlu --}}
@stop

@section('js')
    {{-- Custom JS jika perlu --}}
@stop
