@extends('adminlte::page')

@section('title', 'Edit Website')

@section('content_header')
    <h1>Edit Website</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Edit Website</h3>
        </div>
        <form action="{{ route('websites.update', $website) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nama Website / Klien <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $website->name) }}"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="url">URL Website <span class="text-danger">*</span></label>
                    <input type="url" 
                           class="form-control @error('url') is-invalid @enderror" 
                           id="url" 
                           name="url" 
                           value="{{ old('url', $website->url) }}"
                           required>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- NEW: Server Path Field untuk File Monitoring --}}
                <div class="form-group">
                    <label for="server_path">
                        <i class="fas fa-folder"></i> Server Path (File Monitoring)
                        <span class="badge badge-info badge-sm ml-1">Opsional</span>
                    </label>
                    <input type="text" 
                           class="form-control @error('server_path') is-invalid @enderror" 
                           id="server_path" 
                           name="server_path" 
                           value="{{ old('server_path', $website->server_path) }}"
                           placeholder="Contoh: /home/username/public_html atau /var/www/html">
                    @error('server_path')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Path absolut ke folder root website di server (untuk File Integrity Monitoring).
                        <br>
                        @if($website->server_path)
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> File Monitoring Enabled
                            </span>
                        @else
                            <span class="badge badge-secondary">
                                <i class="fas fa-times-circle"></i> File Monitoring Disabled
                            </span>
                        @endif
                    </small>
                </div>

                <div class="form-group">
                    <label for="notes">Catatan</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" 
                              name="notes" 
                              rows="3">{{ old('notes', $website->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="is_active" 
                               name="is_active"
                               value="1"
                               {{ old('is_active', $website->is_active) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">
                            Aktifkan Monitoring
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Jika dinonaktifkan, website tidak akan dicek secara otomatis
                    </small>
                </div>

                {{-- Info Alert --}}
                @if(!$website->server_path)
                <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> File Monitoring Tidak Aktif</h5>
                    Isi <strong>Server Path</strong> untuk mengaktifkan File Integrity Monitoring (deteksi perubahan file mencurigakan).
                </div>
                @else
                <div class="alert alert-success">
                    <h5><i class="icon fas fa-check-circle"></i> File Monitoring Aktif</h5>
                    <ul class="mb-0 pl-3">
                        <li>Server Path: <code>{{ $website->server_path }}</code></li>
                        <li>Anda dapat membuat baseline dan scan file di halaman detail website.</li>
                    </ul>
                </div>
                @endif
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="{{ route('websites.show', $website) }}" class="btn btn-info">
                    <i class="fas fa-eye"></i> Lihat Detail
                </a>
                <a href="{{ route('websites.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
    </div>
@stop

@section('css')
<style>
    .badge-sm {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem;
    }
</style>
@stop
