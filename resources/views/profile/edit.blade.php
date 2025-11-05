@extends('adminlte::page')

@section('title', 'Edit Profile')

@section('content_header')
    <h1>
        <i class="fas fa-user-edit"></i> Edit Profile
    </h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Update Profile Information -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-id-card"></i> Informasi Profile
                    </h3>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', auth()->user()->name) }}"
                                   placeholder="Masukkan nama lengkap Anda"
                                   required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', auth()->user()->email) }}"
                                   placeholder="Masukkan email Anda"
                                   required>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                Email digunakan untuk login dan komunikasi penting.
                            </small>
                        </div>

                        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                            @if (auth()->user()->hasVerifiedEmail())
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle"></i> Email Anda sudah terverifikasi.
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @else
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle"></i> Email Anda belum diverifikasi.
                                    <form method="POST" action="{{ route('verification.send') }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning ml-2">
                                            Kirim Link Verifikasi
                                        </button>
                                    </form>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

            <!-- Update Password -->
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-key"></i> Ubah Password
                    </h3>
                </div>
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" 
                                   name="current_password" 
                                   placeholder="Masukkan password saat ini"
                                   required>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Masukkan password baru (minimal 8 karakter)"
                                   required>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                Password minimal 8 karakter, kombinasi huruf besar, kecil, angka dan simbol lebih aman.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Ulangi password baru Anda"
                                   required>
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-sync"></i> Ubah Password
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trash"></i> Hapus Akun
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Peringatan!</h5>
                        <p class="mb-0">
                            Menghapus akun Anda adalah <strong>tindakan permanen</strong> dan <strong>tidak dapat dibatalkan</strong>. 
                            Semua data Anda termasuk website yang dipantau akan dihapus selamanya.
                        </p>
                    </div>
                    <form action="{{ route('profile.destroy') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan!');">
                        @csrf
                        @method('DELETE')
                        
                        <div class="form-group">
                            <label for="delete_password">Masukkan Password untuk Konfirmasi <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="delete_password" 
                                   name="password" 
                                   placeholder="Masukkan password Anda"
                                   required>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                Password diperlukan untuk keamanan akun Anda.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-exclamation-circle"></i> Hapus Akun Saya Selamanya
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* Override offset margin */
    .col-md-12 {
        margin-left: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .card {
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem;
    }

    .card-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        padding: 1rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .form-group label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        display: block;
        text-align: left;
    }

    .form-control {
        width: 100%;
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
        text-align: left;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        text-align: left;
    }

    .invalid-feedback.d-block {
        display: block !important;
    }

    .form-text {
        display: block;
        font-size: 0.875rem;
        color: #6c757d;
        text-align: left;
    }

    .alert {
        border-radius: 0.25rem;
        border-left: 4px solid currentColor;
        text-align: left;
    }

    .text-danger {
        color: #dc3545;
    }

    .btn {
        border-radius: 0.25rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .close {
        float: right;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
        color: #000;
        text-shadow: 0 1px 0 #fff;
        opacity: 0.5;
        cursor: pointer;
    }

    .close:hover {
        opacity: 0.75;
    }
</style>
@stop
