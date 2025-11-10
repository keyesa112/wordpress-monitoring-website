@extends('adminlte::page')

@section('title', 'Tambah Website')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-plus-circle text-primary"></i> Tambah Website Baru
        </h1>
        <a href="{{ route('websites.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
@stop

@section('content')

 <div class="alert alert-info d-flex align-items-start mb-4 shadow-sm" role="alert" style="border-left: 4px solid #17a2b8;">
        <div style="font-size: 2rem; margin-right: 1rem;">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <h6 class="alert-heading mb-2">
                <i class="fas fa-exclamation-triangle"></i> <strong>Persyaratan Website</strong>
            </h6>
            <p class="mb-0">
                Website yang ditambahkan harus menggunakan <strong>WordPress dengan REST API aktif</strong>. 
                Pastikan endpoint <code>/wp-json/wp/v2/posts</code> dapat diakses untuk monitoring yang optimal. 
                Jika REST API dinonaktifkan, fitur monitoring akan terbatas.
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Form Input Website
                    </h3>
                </div>
                <form action="{{ route('websites.store') }}" method="POST" id="websiteForm">
                    @csrf
                    <div class="card-body">
                        {{-- Nama Website --}}
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-tag text-primary"></i> 
                                Nama Website / Klien 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="Contoh: Website PT. ABC Indonesia"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @else
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Gunakan nama yang mudah dikenali
                                </small>
                            @enderror
                        </div>

                        {{-- URL Website --}}
                        <div class="form-group">
                            <label for="url">
                                <i class="fas fa-globe text-success"></i> 
                                URL Website 
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-link"></i>
                                    </span>
                                </div>
                                <input type="url" 
                                       class="form-control @error('url') is-invalid @enderror" 
                                       id="url" 
                                       name="url" 
                                       value="{{ old('url') }}"
                                       placeholder="https://example.com"
                                       required>
                                @error('url')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Harus lengkap dengan <code>https://</code> atau <code>http://</code>
                            </small>
                        </div>

                        {{-- Server Path (Optional) --}}
                        <div class="form-group">
                            <label for="server_path">
                                <i class="fas fa-folder text-warning"></i> 
                                Server Path (File Monitoring)
                                <span class="badge badge-info ml-1">Optional</span>
                                <span class="badge badge-warning ml-1">
                                    <i class="fas fa-flask"></i> Beta
                                </span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-server"></i>
                                    </span>
                                </div>
                                <input type="text" 
                                       class="form-control @error('server_path') is-invalid @enderror" 
                                       id="server_path" 
                                       name="server_path" 
                                       value="{{ old('server_path') }}"
                                       placeholder="/var/www/html atau /home/user/public_html">
                                @error('server_path')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb"></i> 
                                <strong>Path absolut</strong> folder root website untuk File Integrity Monitoring.
                                <br>
                                <strong>Contoh:</strong> 
                                <code>/home/user123/public_html</code> atau 
                                <code>/var/www/mysite.com</code>
                            </small>
                        </div>

                        {{-- Notes (Optional) --}}
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-sticky-note text-secondary"></i> 
                                Catatan
                                <span class="badge badge-secondary ml-1">Optional</span>
                            </label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="4"
                                      placeholder="Contoh: Website klien sejak 2024, maintenance schedule setiap Sabtu...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @else
                                <small class="form-text text-muted">
                                    <i class="fas fa-pen"></i> 
                                    Catatan tambahan untuk referensi Anda
                                </small>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fas fa-save"></i> Simpan & Scan Website
                        </button>
                        <a href="{{ route('websites.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Info Card --}}
            <div class="card shadow-sm">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Informasi
                    </h3>
                </div>
                <div class="card-body">
                    <h5 class="font-weight-bold">
                        <i class="fas fa-check-circle text-success"></i> Yang Akan Dilakukan:
                    </h5>
                    <ul class="mb-3">
                        <li>Website akan <strong>otomatis dicek</strong> setelah disimpan</li>
                        <li>Status online/offline akan dideteksi</li>
                        <li>Response time akan diukur</li>
                        <li>Konten mencurigakan akan di-scan (jika WordPress)</li>
                    </ul>

                    <h5 class="font-weight-bold mt-4">
                        <i class="fas fa-lightbulb text-warning"></i> Tips:
                    </h5>
                    <ul class="mb-0">
                        <li>Gunakan nama yang mudah diingat</li>
                        <li>URL harus bisa diakses dari internet</li>
                        <li>Server path hanya untuk monitoring file lokal</li>
                    </ul>
                </div>
            </div>

            {{-- File Monitoring Notice --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-flask"></i> File Monitoring (Beta)
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-exclamation-triangle text-warning"></i> 
                        <strong>Fitur dalam pengembangan</strong>
                    </p>
                    <small class="text-muted">
                        Saat ini hanya dapat memonitor file di server lokal. 
                        Fitur remote monitoring akan segera hadir.
                    </small>
                </div>
            </div>

            {{-- Quick Stats (Optional) --}}
            <div class="card shadow-sm mt-3 bg-gradient-light">
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0">
                        <i class="fas fa-globe"></i>
                    </h3>
                    <p class="text-muted mb-0">
                        Siap melakukan monitoring profesional untuk website Anda
                    </p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .card {
        border-radius: 0.5rem;
        border: none;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* REDUCED FONT SIZES */
    .form-control-lg {
        font-size: 1rem;  
    }

    label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem; 
    }

    .badge {
        font-size: 0.7rem;  
        padding: 0.25rem 0.4rem; 
    }

    .form-text {
        font-size: 0.8rem;
    }

    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        color: #e83e8c;
        font-size: 0.85rem;  
    }

    .card-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.125);
    }

    .bg-gradient-light {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
        font-size: 0.9rem; 
    }

    .input-group-lg {
        font-size: 1rem;  
    }

    ul {
        padding-left: 1.5rem;
        font-size: 0.9rem; 
    }

    ul li {
        margin-bottom: 0.4rem; 
        line-height: 1.5;
    }

    h5 {
        font-size: 0.9rem;  
        margin-bottom: 0.75rem;
    }

    /* Button sizes */
    .btn-lg {
        font-size: 0.95rem; 
        padding: 0.5rem 1rem;
    }

    /* Card title */
    .card-title {
        font-size: 1rem; 
    }

    /* Content header */
    .content-header h1 {
        font-size: 1.5rem;  
    }

    /* Input placeholder */
    ::placeholder {
        font-size: 0.9rem;  
    }
</style>
@stop

@section('js')
<script>
// Form validation & UX enhancements
document.getElementById('websiteForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    
    // Disable button & show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
});

// URL validation helper
document.getElementById('url').addEventListener('blur', function() {
    let url = this.value.trim();
    
    // Auto-add https:// if missing
    if (url && !url.match(/^https?:\/\//i)) {
        this.value = 'https://' + url;
    }
});

// Character counter for notes (optional)
const notesField = document.getElementById('notes');
if (notesField) {
    const maxLength = 500;
    const counter = document.createElement('small');
    counter.className = 'form-text text-muted text-right';
    counter.id = 'notesCounter';
    notesField.parentNode.appendChild(counter);
    
    function updateCounter() {
        const remaining = maxLength - notesField.value.length;
        counter.textContent = `${notesField.value.length}/${maxLength} karakter`;
        
        if (remaining < 50) {
            counter.classList.add('text-warning');
        } else {
            counter.classList.remove('text-warning');
        }
    }
    
    notesField.addEventListener('input', updateCounter);
    updateCounter();
}

// Auto-focus first input on page load
window.addEventListener('load', function() {
    document.getElementById('name').focus();
});
</script>
@stop
