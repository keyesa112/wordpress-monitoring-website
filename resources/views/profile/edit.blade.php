@extends('adminlte::page')

@section('title', 'Edit Profil')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-user-edit text-primary"></i> Edit Profil
        </h1>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Update Profile Information --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary">
                    <h3 class="card-title">
                        <i class="fas fa-id-card"></i> Informasi Profil
                    </h3>
                </div>
                <form action="{{ route('profile.update') }}" method="POST" id="profileForm">
                    @csrf
                    @method('PATCH')
                    <div class="card-body">
                        {{-- Nama Lengkap --}}
                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user text-primary"></i>
                                Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', auth()->user()->name) }}"
                                   placeholder="Masukkan nama lengkap Anda"
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope text-success"></i>
                                Email
                                <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', auth()->user()->email) }}"
                                   placeholder="Masukkan email Anda"
                                   required>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Email digunakan untuk login dan komunikasi penting.
                            </small>
                        </div>

                        {{-- Email Verification Status --}}
                        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                            @if (auth()->user()->hasVerifiedEmail())
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Email Terverifikasi</strong> - Email Anda sudah terverifikasi.
                                </div>
                            @else
                                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Email Belum Terverifikasi</strong> - Silakan verifikasi email Anda.
                                    <form method="POST" action="{{ route('verification.send') }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning text-white ml-2">
                                            <i class="fas fa-envelope"></i> Kirim Link Verifikasi
                                        </button>
                                    </form>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg text-white">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

            {{-- Update Password --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-key"></i> Ubah Password
                    </h3>
                </div>
                <form action="{{ route('password.update') }}" method="POST" id="passwordForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        {{-- Current Password --}}
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-lock text-secondary"></i>
                                Password Saat Ini
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" 
                                   name="current_password" 
                                   placeholder="Masukkan password saat ini"
                                   required>
                            @error('current_password')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock-open text-info"></i>
                                Password Baru
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Masukkan password baru (minimal 8 karakter)"
                                   required>
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-lightbulb"></i>
                                Password minimal 8 karakter, kombinasi huruf besar, kecil, angka dan simbol lebih aman.
                            </small>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-group">
                            <label for="password_confirmation">
                                <i class="fas fa-check text-success"></i>
                                Konfirmasi Password
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Ulangi password baru Anda"
                                   required>
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="submit" class="btn btn-warning btn-lg text-white">
                            <i class="fas fa-sync"></i> Ubah Password
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg text-white">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>

            {{-- Delete Account --}}
            <div class="card shadow-sm">
                <div class="card-header bg-danger">
                    <h3 class="card-title">
                        <i class="fas fa-trash"></i> Hapus Akun
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> Peringatan!
                        </h5>
                        <p class="mb-0">
                            Menghapus akun Anda adalah <strong>tindakan permanen</strong> dan <strong>tidak dapat dibatalkan</strong>. 
                            Semua data Anda termasuk website yang dipantau akan dihapus selamanya.
                        </p>
                    </div>

                    <form action="{{ route('profile.destroy') }}" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan!');">
                        @csrf
                        @method('DELETE')
                        
                        {{-- Delete Password Confirmation --}}
                        <div class="form-group">
                            <label for="delete_password">
                                <i class="fas fa-lock text-danger"></i>
                                Masukkan Password untuk Konfirmasi
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="delete_password" 
                                   name="password" 
                                   placeholder="Masukkan password Anda"
                                   required>
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Password diperlukan untuk keamanan akun Anda.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-trash"></i> Hapus Akun Saya Selamanya
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Profile Security Tips --}}
            <div class="card shadow-sm">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Tips Keamanan
                    </h3>
                </div>
                <div class="card-body">
                    <ul style="font-size: 0.9rem; padding-left: 1.5rem;">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success"></i>
                            Gunakan password <strong>yang kuat</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope text-info"></i>
                            Verifikasi email Anda
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-key text-warning"></i>
                            Ubah password secara berkala
                        </li>
                        <li>
                            <i class="fas fa-sign-out-alt text-danger"></i>
                            Logout dari perangkat lain jika perlu
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Account Info --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Informasi Akun
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="100"><strong>Status</strong></td>
                            <td>
                                @if(auth()->user()->hasVerifiedEmail())
                                    <span class="badge badge-success">Terverifikasi</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Join</strong></td>
                            <td>
                                <small>{{ auth()->user()->created_at->format('d M Y') }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Update</strong></td>
                            <td>
                                <small>{{ auth()->user()->updated_at->diffForHumans() }}</small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* UNIVERSAL STYLING */
    .card {
        border-radius: 0.5rem;
        border: none;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* FONT SIZES */
    label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control {
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .invalid-feedback {
        color: #dc3545;
        font-size: 0.8rem;
    }

    small, .form-text {
        font-size: 0.8rem;
    }

    .alert {
        font-size: 0.9rem;
    }

    .card-title {
        font-size: 1rem;
    }

    .content-header h1 {
        font-size: 1.5rem;
    }

    /* BUTTONS */
    .btn-lg {
        font-size: 0.95rem;
        padding: 0.5rem 1rem;
    }

    .btn-secondary {
        color: #fff;
    }

    .btn-secondary:hover {
        color: #fff;
    }

    a.btn-secondary {
        color: #fff;
    }

    a.btn-secondary:hover {
        color: #fff;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #fff;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
        color: #fff;
    }

    .btn-warning.text-white {
        color: #fff !important;
    }

    /* FLEXBOX */
    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .ml-2 {
        margin-left: 0.5rem !important;
    }

    /* TABLE */
    .table-sm th,
    .table-sm td {
        font-size: 0.9rem;
    }

    .table-borderless td {
        padding: 0.4rem 0;
    }

    /* ALERT */
    .alert h5 {
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    /* TEXT COLORS */
    .text-danger {
        color: #dc3545;
    }
</style>
@stop

@section('js')
<script>
// Form change tracking
let formChanged = false;
const forms = [
    document.getElementById('profileForm'),
    document.getElementById('passwordForm')
];

forms.forEach(form => {
    if (form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });

        form.addEventListener('submit', function() {
            formChanged = false;
        });
    }
});

// Warn before leaving if form changed
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
@stop
