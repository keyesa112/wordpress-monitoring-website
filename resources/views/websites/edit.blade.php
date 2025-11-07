@extends('adminlte::page')

@section('title', 'Edit Website - ' . $website->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-edit text-warning"></i> Edit Website
        </h1>
        <div class="btn-group" role="group">
            <a href="{{ route('websites.show', $website) }}" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i> Lihat Detail
            </a>
            <a href="{{ route('websites.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Form Edit Website
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light">
                            <i class="far fa-clock"></i> Terakhir Update: {{ $website->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <form action="{{ route('websites.update', $website) }}" method="POST" id="editForm">
                    @csrf
                    @method('PUT')
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
                                   value="{{ old('name', $website->name) }}"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
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
                                       value="{{ old('url', $website->url) }}"
                                       required>
                                @error('url')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        {{-- Server Path --}}
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
                                       value="{{ old('server_path', $website->server_path) }}"
                                       placeholder="/var/www/html atau /home/user/public_html">
                                @error('server_path')
                                    <div class="invalid-feedback">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-lightbulb"></i> 
                                Path absolut folder root website untuk File Integrity Monitoring
                            </small>
                        </div>

                        {{-- Notes --}}
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-sticky-note text-secondary"></i> 
                                Catatan
                                <span class="badge badge-secondary ml-1">Optional</span>
                            </label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="4">{{ old('notes', $website->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Active Toggle - Opsi 2 (Professional) --}}
                        <div class="form-group">
                            <fieldset>
                                <legend class="col-form-label">
                                    <i class="fas fa-toggle-on text-success"></i> Monitoring Status
                                </legend>
                                <div class="custom-control custom-switch custom-switch-lg mt-2">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="is_active" 
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', $website->is_active) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active">
                                        <strong>{{ $website->is_active ? 'Monitoring Aktif' : 'Monitoring Tidak Aktif' }}</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> 
                                    Toggle untuk mengaktifkan atau menonaktifkan monitoring website ini
                                </small>
                            </fieldset>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-warning btn-lg" id="submitBtn">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Current Status --}}
            <div class="card shadow-sm">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Status Saat Ini
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="120"><strong>Status</strong></td>
                            <td>
                                @if($website->status === 'online')
                                    <span class="badge badge-success badge-lg">
                                        <i class="fas fa-check-circle"></i> Online
                                    </span>
                                @elseif($website->status === 'offline')
                                    <span class="badge badge-danger badge-lg">
                                        <i class="fas fa-times-circle"></i> Offline
                                    </span>
                                @else
                                    <span class="badge badge-secondary badge-lg">{{ $website->status }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Response</strong></td>
                            <td>
                                @if($website->response_time)
                                    <span class="badge badge-info">{{ $website->response_time }} ms</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Konten</strong></td>
                            <td>
                                @if($website->has_suspicious_content)
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Mencurigakan
                                    </span>
                                @else
                                    <span class="badge badge-success">Clean</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Last Check</strong></td>
                            <td>
                                <small>
                                    @if($website->last_checked_at)
                                        {{ $website->last_checked_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">Belum pernah</span>
                                    @endif
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- File Monitoring Status --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header {{ $website->server_path ? 'bg-success' : 'bg-secondary' }}">
                    <h3 class="card-title">
                        <i class="fas fa-folder-open"></i> File Monitoring
                    </h3>
                </div>
                <div class="card-body">
                    @if($website->server_path)
                        <div class="alert alert-success mb-2">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Aktif</strong>
                        </div>
                        <p class="mb-2">
                            <strong>Path:</strong><br>
                            <code>{{ $website->server_path }}</code>
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb"></i> 
                            Anda dapat scan file di halaman detail
                        </small>
                    @else
                        <div class="alert alert-secondary mb-2">
                            <i class="fas fa-times-circle"></i> 
                            <strong>Nonaktif</strong>
                        </div>
                        <p class="mb-0">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Isi Server Path untuk mengaktifkan File Monitoring
                            </small>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-primary">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('websites.check', $website) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sync"></i> Scan Sekarang
                        </button>
                    </form>
                    <a href="{{ route('websites.show', $website) }}" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
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

    .badge-lg {
        font-size: 0.85rem;
        padding: 0.35rem 0.6rem;
    }

    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        color: #e83e8c;
        word-break: break-all;
        font-size: 0.85rem;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
        font-size: 0.9rem;
    }

    .input-group-lg {
        font-size: 1rem;
    }

    /* FIELDSET STYLING */
    fieldset {
        border: none;
        padding: 0;
        margin: 0;
    }

    legend {
        padding: 0 !important;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        width: auto;
    }

    .col-form-label {
        padding: 0;
        font-weight: 600;
        font-size: 0.95rem;
    }

    /* CUSTOM SWITCH IMPROVEMENTS */
    .custom-switch {
        padding-left: 0;
    }

    .custom-control-label {
        margin-bottom: 0 !important;
        padding-top: 0.25rem;
        padding-left: 7%;
    }

    .custom-switch .custom-control-label::before {
        left: 0;
        border-radius: 0.25rem;
    }

    .custom-switch .custom-control-label::after {
        top: calc(0.25rem + 2px);
        left: 0.25rem;
        transition: all 0.2s ease;
    }

    .custom-switch .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(calc(1.4rem - 4px));
    }

    .custom-switch-lg .custom-control-label::before {
        height: 1.4rem;
        width: 2.5rem;
    }

    .custom-switch-lg .custom-control-label::after {
        width: calc(1.4rem - 4px);
        height: calc(1.4rem - 4px);
    }

    .custom-switch-lg .custom-control-label {
        font-size: 0.95rem;
    }

    /* SPACING IMPROVEMENTS */
    .table-borderless td {
        padding: 0.4rem 0;
        font-size: 0.9rem;
    }

    .btn-lg {
        font-size: 0.95rem;
        padding: 0.5rem 1rem;
    }

    .btn-block {
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    .btn-block + .btn-block {
        margin-top: 0.5rem;
    }

    .card-title {
        font-size: 1rem;
    }

    .content-header h1 {
        font-size: 1.5rem;
    }

    small, .form-text {
        font-size: 0.8rem;
    }

    .alert {
        font-size: 0.9rem;
    }

    ::placeholder {
        font-size: 0.9rem;
    }

    /* FORM GROUP SPACING */
    .form-group:last-child {
        margin-bottom: 0;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }
</style>
@stop

@section('js')
<script>
// Form submission handler
document.getElementById('editForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
});

// Track form changes
let formChanged = false;
const form = document.getElementById('editForm');
const inputs = form.querySelectorAll('input, textarea, select');

inputs.forEach(input => {
    input.addEventListener('change', function() {
        formChanged = true;
    });
});

// Warn before leaving if form changed
window.addEventListener('beforeunload', function(e) {
    if (formChanged && !form.submitted) {
        e.preventDefault();
        e.returnValue = '';
    }
});

form.addEventListener('submit', function() {
    form.submitted = true;
});

// URL validation
document.getElementById('url').addEventListener('blur', function() {
    let url = this.value.trim();
    
    if (url && !url.match(/^https?:\/\//i)) {
        this.value = 'https://' + url;
    }
});

// Update label text saat toggle berubah
document.getElementById('is_active').addEventListener('change', function() {
    const legendText = document.querySelector('legend');
    const statusText = this.checked ? 'Monitoring Aktif' : 'Monitoring Tidak Aktif';
    if (legendText) {
        legendText.querySelector('strong').textContent = statusText;
    }
});
</script>
@stop
