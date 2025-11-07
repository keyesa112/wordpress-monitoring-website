@extends('adminlte::auth.register')

@section('auth_header')
    <div class="text-center mb-3">
        <h4 class="mt-2">
            <strong>Buat Akun Baru</strong>
        </h4>
        <p class="text-muted">Daftar untuk memulai monitoring website</p>
    </div>
@stop

@section('auth_body')
    {{-- Register Form --}}
    <form action="{{ route('register') }}" method="post" id="registerForm">
        @csrf

        {{-- Name Field --}}
        <div class="form-group">
            <label for="name" class="text-muted">
                <i class="fas fa-user"></i> Nama Lengkap
            </label>
            <input type="text" 
                   name="name" 
                   id="name"
                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                   value="{{ old('name') }}" 
                   placeholder="Masukkan nama lengkap Anda" 
                   required 
                   autofocus>
            @error('name')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Email Field --}}
        <div class="form-group">
            <label for="email" class="text-muted">
                <i class="fas fa-envelope"></i> Email
            </label>
            <input type="email" 
                   name="email" 
                   id="email"
                   class="form-control form-control-lg @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" 
                   placeholder="Masukkan email Anda" 
                   required>
            @error('email')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Password Field --}}
        <div class="form-group">
            <label for="password" class="text-muted">
                <i class="fas fa-lock"></i> Password
            </label>
            <input type="password" 
                   name="password" 
                   id="password"
                   class="form-control form-control-lg @error('password') is-invalid @enderror" 
                   placeholder="Masukkan password (minimal 8 karakter)" 
                   required>
            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
            <small class="form-text text-muted d-block mt-2">
                <i class="fas fa-lightbulb"></i>
                Gunakan kombinasi huruf besar, kecil, angka dan simbol
            </small>
        </div>

        {{-- Confirm Password Field --}}
        <div class="form-group">
            <label for="password_confirmation" class="text-muted">
                <i class="fas fa-check"></i> Konfirmasi Password
            </label>
            <input type="password" 
                   name="password_confirmation" 
                   id="password_confirmation"
                   class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" 
                   placeholder="Ulangi password Anda" 
                   required>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>
        {{-- Submit Button --}}
        <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">
            <i class="fas fa-user-check"></i> Daftar Sekarang
        </button>
    </form>

    {{-- Divider --}}
    <hr class="my-3">

    {{-- Login Link --}}
    <p class="text-center text-muted">
        <small>
            Sudah punya akun? 
            <a href="{{ route('login') }}" class="text-primary font-weight-bold">
                Masuk di sini
            </a>
        </small>
    </p>
@stop

@section('auth_footer')
    {{-- Footer Info --}}
    <div class="text-center text-muted mt-4">
        <small>
            <i class="fas fa-shield-alt"></i>
            Secure Registration &nbsp; | &nbsp;
            <i class="fas fa-lock"></i>
            Data Encrypted
        </small>
    </div>
@stop

@section('css')
<style>
    /* Register Page Custom Styling */
    .register-page {
        background-color: #f5f5f5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .register-box {
        width: 100%;
        max-width: 450px;
    }

    .register-card-body {
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        background-color: #fff;
    }

    /* Form Controls */
    .form-control-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
        height: auto;
    }

    .form-control-lg:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-control {
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* Labels */
    label {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    /* Form Groups */
    .form-group {
        margin-bottom: 1.25rem;
    }

    /* Checkbox */
    .custom-checkbox .custom-control-label {
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    /* Buttons */
    .btn-lg {
        font-weight: 600;
        border-radius: 0.25rem;
        font-size: 0.95rem;
        padding: 0.75rem 1rem;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    /* Links */
    a {
        color: #007bff;
        text-decoration: none;
    }

    a:hover {
        color: #0056b3;
        text-decoration: underline;
    }

    /* Invalid Feedback */
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    /* Card Styling */
    .register-box-msg {
        font-size: 0.9rem;
    }

    .register-logo a {
        color: #333;
        text-decoration: none;
    }

    /* Text Utilities */
    .text-muted {
        color: #6c757d !important;
    }

    .text-center {
        text-align: center;
    }

    .text-primary {
        color: #007bff !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .mb-0 {
        margin-bottom: 0 !important;
    }

    .d-block {
        display: block;
    }

    .font-weight-bold {
        font-weight: 600;
    }

    /* Small text */
    small {
        font-size: 0.8rem;
    }

    .form-text {
        font-size: 0.8rem;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .register-page {
            padding: 1rem;
        }

        .register-box {
            max-width: 100%;
        }
    }
</style>
@stop

@section('js')
<script>
// Auto-focus name field
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    if (nameInput && !nameInput.value) {
        nameInput.focus();
    }
});

// Form submission
document.getElementById('registerForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftar...';
});

// Password match validation
document.getElementById('password_confirmation').addEventListener('blur', function() {
    const password = document.getElementById('password').value;
    const confirmation = this.value;
    
    if (confirmation && password !== confirmation) {
        this.classList.add('is-invalid');
        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password tidak cocok';
            this.parentNode.insertBefore(feedback, this.nextSibling);
        }
    } else {
        this.classList.remove('is-invalid');
        const feedback = this.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
    }
});
</script>
@stop
