@extends('adminlte::page')

@section('title', 'Tambah Website')

@section('content_header')
    <h1>Tambah Website Baru</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Website</h3>
        </div>
        <form action="{{ route('websites.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nama Website / Klien <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           placeholder="Contoh: Website PT. ABC"
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
                           value="{{ old('url') }}"
                           placeholder="https://example.com"
                           required>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Masukkan URL lengkap dengan https:// atau http://
                    </small>
                </div>

                {{-- Server Path Field untuk File Monitoring --}}
                <div class="form-group">
                    <label for="server_path">
                        <i class="fas fa-folder"></i> Server Path (File Monitoring)
                        <span class="badge badge-info badge-sm ml-1">
                            <i class="fas fa-tag"></i> Opsional
                        </span>
                        <span class="badge badge-warning badge-sm ml-1">
                            <i class="fas fa-flask"></i> Development
                        </span>
                    </label>
                    <input type="text" 
                           class="form-control @error('server_path') is-invalid @enderror" 
                           id="server_path" 
                           name="server_path" 
                           value="{{ old('server_path') }}"
                           placeholder="Contoh: /home/username/public_html atau /var/www/html">
                    @error('server_path')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> 
                        Path absolut ke folder root website di server (untuk File Integrity Monitoring).
                        <br>
                        <strong>Contoh:</strong> <code>/home/user123/public_html</code> atau <code>/var/www/mysite.com</code>
                    </small>
                </div>

                <div class="form-group">
                    <label for="notes">Catatan (Opsional)</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                              id="notes" 
                              name="notes" 
                              rows="3"
                              placeholder="Catatan tambahan tentang website ini...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Info Alert dengan Development Notice --}}
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h5>
                        <i class="icon fas fa-info-circle"></i> Informasi
                    </h5>
                    <ul class="mb-0 pl-3">
                        <li>Website akan <strong>otomatis dicek</strong> statusnya setelah ditambahkan.</li>
                        <li>Jika Server Path diisi, Anda dapat menggunakan <strong>File Monitoring</strong> untuk mendeteksi perubahan file mencurigakan.</li>
                        <li>Server Path dapat ditambahkan/diubah nanti melalui menu Edit.</li>
                        <li class="mt-2">
                            <strong>
                                <i class="fas fa-flask"></i> File Monitoring (Development):
                            </strong>
                            Fitur ini masih dalam tahap pengembangan dan saat ini hanya dapat memonitor file di server lokal.
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan & Cek Website
                </button>
                <a href="{{ route('websites.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
@stop

@section('css')
    <style>
        .badge-sm {
            font-size: 0.75rem;
            padding: 0.3rem 0.5rem;
            display: inline-block;
            margin-right: 0.25rem;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        .badge-warning i {
            margin-right: 0.25rem;
        }

        .badge-info i {
            margin-right: 0.25rem;
        }

        /* Label styling untuk better readability */
        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* Alert improvement */
        .alert h5 {
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .alert ul li {
            margin-bottom: 0.4rem;
        }

        .alert ul li strong {
            color: #0c5460;
        }
    </style>
@stop
