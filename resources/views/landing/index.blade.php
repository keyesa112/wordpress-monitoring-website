<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Web Monitor - Sistem Monitoring Keamanan WordPress</title>
    
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .scan-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        /* .navbar-landing {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        } */

        .navbar-landing {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky !important;
            top: 0;
            z-index: 1030;
            transition: all 0.3s ease;
        }

        .navbar-landing.scrolled {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        
        .scan-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 12px 40px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .scan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }
        
        .comparison-table {
            margin-top: 30px;
        }
        
        .comparison-table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .modal-content, .modal-content * {
                visibility: visible;
            }
            .modal-dialog {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                padding: 0;
                overflow: visible !important;
            }
            .modal-header, .modal-footer {
                display: none !important;
            }
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-landing">
        <div class="container">
            <a href="/" class="navbar-brand">
                <i class="fas fa-shield-alt text-primary"></i>
                <span class="brand-text font-weight-bold text-primary">Web Monitor</span>
            </a>

            <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a href="#scan" class="nav-link">
                            <i class="fas fa-search"></i> Scan Website
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#panduan" class="nav-link">
                            <i class="fas fa-book"></i> Panduan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#docs" class="nav-link">
                            <i class="fas fa-file-alt"></i> Dokumentasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('register') }}" class="btn btn-primary ml-2">
                            <i class="fas fa-user-plus"></i> Daftar Gratis
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 font-weight-bold mb-4">
                <i class="fas fa-shield-virus"></i> 
                Sistem Monitoring Keamanan WordPress
            </h1>
            <p class="lead mb-4">
                Sistem Deteksi Keamanan dan Kesehatan Website WordPress berbasis Laravel yang memantau status situs, 
                menganalisis potensi penyusupan pada konten, meta, dan sitemap, serta memberikan rekomendasi otomatis 
                untuk tindakan perbaikan.
            </p>
            <div class="row justify-content-center mt-4">
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>Gratis 100%</h5>
                        <p>Scan tanpa biaya</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <i class="fas fa-bolt fa-2x mb-2"></i>
                        <h5>Scan Cepat</h5>
                        <p>Hasil dalam 30-60 detik</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="text-center">
                        <i class="fas fa-lock fa-2x mb-2"></i>
                        <h5>Aman & Privasi</h5>
                        <p>Data tidak disalahgunakan</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scan Box -->
    <section id="scan" class="container">
        <div class="scan-box">
            <h2 class="text-center mb-4">
                <i class="fas fa-search text-primary"></i> 
                Scan Website WordPress Sekarang
            </h2>
            <form id="scanForm">
                @csrf
                <div class="input-group input-group-lg">
                    <input 
                        type="url" 
                        class="form-control form-control-lg" 
                        id="urlInput" 
                        name="url" 
                        placeholder="Masukkan URL website WordPress (contoh: https://example.com)" 
                        required
                    >
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary scan-btn">
                            <i class="fas fa-search"></i> Scan Sekarang
                        </button>
                    </div>
                </div>
                <small class="form-text text-muted mt-2">
                    <i class="fas fa-info-circle"></i> 
                    Scan gratis untuk 1 URL. Daftar untuk monitoring unlimited!
                </small>
            </form>

            <!-- Loading State -->
            <div id="loadingState" class="text-center mt-4" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Scanning...</span>
                </div>
                <h4 class="mt-3">Sedang melakukan analisis keamanan...</h4>
                <p class="text-muted">Mohon tunggu, sistem sedang memeriksa:</p>
                <ul class="list-unstyled text-muted">
                    <li><i class="fas fa-check text-success"></i> Status website & response time</li>
                    <li><i class="fas fa-check text-success"></i> Konten posts & pages</li>
                    <li><i class="fas fa-check text-success"></i> Header, footer & meta tags</li>
                    <li><i class="fas fa-check text-success"></i> Sitemap XML</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Panduan Section -->
    <section id="panduan" class="container mt-5 mb-5">
        <h2 class="text-center mb-4">
            <i class="fas fa-book text-primary"></i> Panduan Penggunaan
        </h2>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-question-circle"></i> Cara Scan Website</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Masukkan URL website WordPress Anda di form scan</li>
                            <li>Klik tombol <strong>"Scan Sekarang"</strong></li>
                            <li>Tunggu 30-60 detik hingga proses scan selesai</li>
                            <li>Hasil scan akan ditampilkan dalam bentuk modal</li>
                            <li>Baca rekomendasi keamanan yang diberikan sistem</li>
                            <li>Anda bisa print atau save hasil scan</li>
                        </ol>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb"></i> <strong>Tips:</strong> Pastikan URL yang dimasukkan adalah website WordPress untuk hasil optimal.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-shield-alt"></i> Apa yang Discan?</h5>
                    </div>
                    <div class="card-body">
                        <ul class="fa-ul">
                            <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                                <strong>Status Website:</strong> Uptime, response time, HTTP code
                            </li>
                            <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                                <strong>Posts & Pages:</strong> Deteksi keyword mencurigakan (judi, slot, togel, dll)
                            </li>
                            <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                                <strong>Header & Footer:</strong> Script injection, external links
                            </li>
                            <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                                <strong>Meta Tags:</strong> SEO spam, hidden keywords
                            </li>
                            <li><span class="fa-li"><i class="fas fa-check text-success"></i></span>
                                <strong>Sitemap XML:</strong> URL suspicious yang tidak seharusnya ada
                            </li>
                        </ul>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Catatan:</strong> Sistem hanya scan homepage jika WordPress REST API tidak aktif.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5><i class="fas fa-exclamation-circle"></i> Limitasi Scan Gratis</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-times-circle text-danger"></i> Scan Gratis (Guest)</h6>
                        <ul>
                            <li>Hanya 1 URL per scan</li>
                            <li>Tidak ada riwayat scan</li>
                            <li>Tidak ada monitoring otomatis</li>
                            <li>Hasil tidak disimpan</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-check-circle text-success"></i> Akun Terdaftar (Gratis)</h6>
                        <ul>
                            <li>Unlimited URL monitoring</li>
                            <li>Riwayat scan tersimpan</li>
                            <li>Monitoring otomatis via cron job</li>
                            <li>Import bulk URL (CSV)</li>
                            <li>Notifikasi email</li>
                            <li>Dashboard analytics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="feature-icon fas fa-search-plus"></i>
                            <h4>Deteksi Konten Mencurigakan</h4>
                            <p>Scan otomatis untuk mendeteksi keyword judi, slot, togel, dan konten mencurigakan lainnya pada posts, pages, meta tags, dan sitemap.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="feature-icon fas fa-chart-line"></i>
                            <h4>Monitoring Real-time</h4>
                            <p>Monitor status website secara berkala dengan sistem cron job otomatis. Dapatkan notifikasi jika terdeteksi konten mencurigakan.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="feature-icon fas fa-file-invoice"></i>
                            <h4>Rekomendasi Perbaikan</h4>
                            <p>Dapatkan rekomendasi otomatis untuk setiap masalah yang terdeteksi, lengkap dengan severity level dan action steps.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Documentation Section -->
    <section id="docs" class="container py-5">
        <h2 class="text-center mb-4">
            <i class="fas fa-file-alt text-primary"></i> Dokumentasi Teknis
        </h2>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-cogs"></i> Cara Kerja Sistem</h5>
                    </div>
                    <div class="card-body">
                        <h6>1. Status Check</h6>
                        <p>Sistem melakukan HTTP request ke website untuk mengecek status (online/offline), response time, dan HTTP status code.</p>
                        
                        <h6>2. Content Scanning</h6>
                        <p>Menggunakan WordPress REST API untuk mendapatkan data posts dan pages. Jika REST API disabled, sistem akan scan homepage saja.</p>
                        
                        <h6>3. Keyword Detection</h6>
                        <p>Sistem memiliki database keyword mencurigakan (judi, slot, togel, dll) dan akan mencocokkan dengan konten website.</p>
                        
                        <h6>4. Recommendation Engine</h6>
                        <p>Berdasarkan hasil scan, sistem generate rekomendasi dengan severity level (critical, high, medium, low).</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Keterbatasan Sistem</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h6>WordPress REST API Disabled</h6>
                            <p>Jika WordPress REST API tidak aktif atau di-block, sistem hanya bisa scan <strong>homepage</strong> saja. Konten di halaman lain tidak terdeteksi.</p>
                            <p><strong>Solusi:</strong> Aktifkan REST API di WordPress Settings → Permalinks.</p>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6>JavaScript-Rendered Content</h6>
                            <p>Konten yang di-load via JavaScript (React, Vue, Ajax) mungkin tidak terdeteksi karena sistem scan HTML statis.</p>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6>Obfuscated/Encrypted Content</h6>
                            <p>Konten yang di-encode atau di-hide dengan teknik advanced (base64, hex, dll) sulit dideteksi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-table"></i> Perbandingan Fitur: Guest vs Registered</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered comparison-table">
                        <thead>
                            <tr>
                                <th>Fitur</th>
                                <th class="text-center">Guest (Scan Gratis)</th>
                                <th class="text-center">Registered (Gratis)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Scan 1 URL</strong></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Unlimited URL Monitoring</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Riwayat Scan</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Monitoring Otomatis (Cron Job)</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Import Bulk URL (CSV)</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Dashboard Analytics</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Email Notification</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>API Access</strong></td>
                                <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                                <td class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td><strong>Harga</strong></td>
                                <td class="text-center"><span class="badge badge-success">Gratis</span></td>
                                <td class="text-center"><span class="badge badge-success">Gratis</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang - Gratis & Unlimited!
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer text-center bg-light">
        <strong>Copyright &copy; 2025 <a href="/">Web Monitor</a>.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>

</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt"></i> Hasil Scan Keamanan Website
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="resultContent">
                <!-- Hasil scan akan ditampilkan di sini -->
            </div>
            <div class="modal-footer no-print">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="printModal()">
                    <i class="fas fa-print"></i> Print / Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    $('#scanForm').on('submit', function(e) {
        e.preventDefault();
        
        const url = $('#urlInput').val();
        const $loadingState = $('#loadingState');
        const $scanBtn = $('.scan-btn');
        
        // Show loading
        $loadingState.show();
        $scanBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Scanning...');
        
        // AJAX request
        $.ajax({
            url: '{{ route("guest.scan") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                url: url
            },
            success: function(response) {
                if (response.success) {
                    displayResult(response.data);
                    $('#resultModal').modal('show');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Terjadi kesalahan saat scan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert('Error: ' + errorMsg);
            },
            complete: function() {
                $loadingState.hide();
                $scanBtn.prop('disabled', false).html('<i class="fas fa-search"></i> Scan Sekarang');
            }
        });
    });
});

function displayResult(data) {
    const status = data.status;
    const content = data.content;
    const recommendations = data.recommendations || [];
    
    const statusBadge = status.status === 'online' 
        ? '<span class="badge badge-success badge-lg">Online</span>' 
        : '<span class="badge badge-danger badge-lg">Offline</span>';
    
    const suspiciousBadge = content.has_suspicious_content
        ? '<span class="badge badge-danger badge-lg"><i class="fas fa-exclamation-triangle"></i> Terdeteksi Konten Mencurigakan!</span>'
        : '<span class="badge badge-success badge-lg"><i class="fas fa-check"></i> Website Bersih</span>';
    
    let html = `
        <div class="alert alert-${content.has_suspicious_content ? 'danger' : 'success'} text-center">
            <h4>${suspiciousBadge}</h4>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info">
                        <h6><i class="fas fa-link"></i> Informasi Website</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>URL:</strong></td>
                                <td>${content.url}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>${statusBadge}</td>
                            </tr>
                            <tr>
                                <td><strong>HTTP Code:</strong></td>
                                <td><span class="badge badge-secondary">${status.http_code}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Response Time:</strong></td>
                                <td>${status.response_time} ms</td>
                            </tr>
                            <tr>
                                <td><strong>Waktu Scan:</strong></td>
                                <td>${content.scanned_at}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h6><i class="fas fa-shield-alt"></i> Ringkasan Hasil Scan</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Posts Mencurigakan:</strong></td>
                                <td><span class="badge badge-${content.posts.suspicious_count > 0 ? 'danger' : 'success'}">${content.posts.suspicious_count}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Pages Mencurigakan:</strong></td>
                                <td><span class="badge badge-${content.pages.suspicious_count > 0 ? 'danger' : 'success'}">${content.pages.suspicious_count}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Header/Footer:</strong></td>
                                <td><span class="badge badge-${content.header_footer.has_suspicious ? 'danger' : 'success'}">${content.header_footer.keyword_count} keyword</span></td>
                            </tr>
                            <tr>
                                <td><strong>Meta Tags:</strong></td>
                                <td><span class="badge badge-${content.meta.has_suspicious ? 'danger' : 'success'}">${content.meta.keyword_count} keyword</span></td>
                            </tr>
                            <tr>
                                <td><strong>Sitemap:</strong></td>
                                <td><span class="badge badge-${content.sitemap.has_suspicious ? 'danger' : 'success'}">${content.sitemap.keyword_count} URL</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Detail Posts
    if (content.posts.suspicious_count > 0) {
        html += `
            <div class="card mt-3">
                <div class="card-header bg-danger text-white">
                    <h6><i class="fas fa-file-alt"></i> Posts Mencurigakan (${content.posts.suspicious_count})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Judul Post</th>
                                    <th>Keywords Terdeteksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${content.posts.suspicious_posts.slice(0, 10).map(post => `
                                    <tr>
                                        <td>${post.title}</td>
                                        <td><span class="badge badge-danger">${post.keywords.join(', ')}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Detail Pages
    if (content.pages.suspicious_count > 0) {
        html += `
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    <h6><i class="fas fa-file"></i> Pages Mencurigakan (${content.pages.suspicious_count})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Judul Page</th>
                                    <th>Keywords Terdeteksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${content.pages.suspicious_pages.slice(0, 10).map(page => `
                                    <tr>
                                        <td>${page.title}</td>
                                        <td><span class="badge badge-warning">${page.keywords.join(', ')}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Recommendations
    if (recommendations.length > 0) {
        html += `
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h6><i class="fas fa-lightbulb"></i> Rekomendasi Perbaikan (${recommendations.length})</h6>
                </div>
                <div class="card-body">
                    ${recommendations.map((rec, index) => `
                        <div class="alert alert-${rec.severity === 'critical' ? 'danger' : rec.severity === 'high' ? 'warning' : 'info'} mb-2">
                            <h6>
                                <span class="badge badge-${rec.severity === 'critical' ? 'danger' : rec.severity === 'high' ? 'warning' : 'info'}">${rec.severity}</span>
                                ${index + 1}. ${rec.title}
                            </h6>
                            <p class="mb-1">${rec.description}</p>
                            <small><strong>Tindakan:</strong> ${rec.action}</small>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // CTA
    html += `
        <div class="alert alert-info mt-4">
            <h5><i class="fas fa-user-plus"></i> Ingin Monitoring Otomatis?</h5>
            <p>Daftar sekarang untuk mendapatkan fitur:</p>
            <ul>
                <li>Monitoring otomatis via cron job</li>
                <li>Email notification jika terdeteksi konten mencurigakan</li>
                <li>Import bulk URL (CSV)</li>
                <li>Dashboard analytics lengkap</li>
            </ul>
            <a href="{{ route('register') }}" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Daftar Gratis Sekarang!
            </a>
        </div>
    `;
    
    $('#resultContent').html(html);
}

function printModal() {
    window.print();
}
</script>

</body>
</html>
