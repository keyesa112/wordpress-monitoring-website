@extends('adminlte::auth.login')

@section('auth_header')
    <div class="text-center mb-3">
        <h4 class="mt-2">
            <strong>Masuk ke Akun Anda</strong>
        </h4>
        <p class="text-muted">Masuk untuk memulai monitoring website</p>
    </div>
@stop

@section('auth_body')
    {{-- Session Status Alert --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-check-circle"></i>
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    {{-- Login Form --}}
    <form action="{{ route('login') }}" method="post" id="loginForm">
        @csrf

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
                   autofocus 
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
                   placeholder="Masukkan password Anda" 
                   required>
            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="btn btn-primary btn-lg btn-block mb-3">
            <i class="fas fa-sign-in-alt"></i> Masuk
        </button>
    </form>

    {{-- Register Link --}}
    @if (Route::has('register'))
        <hr class="my-3">
        <p class="text-center text-muted">
            <small>
                Belum punya akun? 
                <a href="{{ route('register') }}" class="text-primary font-weight-bold">
                    Daftar di sini
                </a>
            </small>
        </p>
    @endif
@stop

@section('auth_footer')
    {{-- Footer Info --}}
    <div class="text-center text-muted mt-4">
        <small>
            <i class="fas fa-shield-alt"></i>
            Secure Login &nbsp; | &nbsp;
            <i class="fas fa-lock"></i>
            Data Encrypted
        </small>
    </div>
@stop

@section('css')
<style>
    /* Login Page Custom Styling */
    .login-page {
        background-color: #f5f5f5;  /* Putih abu-abu aja */
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-box {
        width: 100%;
        max-width: 400px;
    }

    .login-card-body {
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

    /* Alerts */
    .alert {
        border-radius: 0.25rem;
        font-size: 0.9rem;
    }

    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
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
    .login-box-msg {
        font-size: 0.9rem;
    }

    .login-logo a {
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

    .text-right {
        text-align: right;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .login-page {
            padding: 1rem;
        }

        .login-box {
            max-width: 100%;
        }
    }
</style>
@stop