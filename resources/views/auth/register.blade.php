@extends('adminlte::auth.register')

@section('auth_header', 'Register a new membership')

@section('auth_body')
    <form action="{{ route('register') }}" method="post">
        @csrf

        {{-- Name Field --}}
        <div class="input-group mb-3">
            <input type="text" 
                   name="name" 
                   class="form-control @error('name') is-invalid @enderror" 
                   value="{{ old('name') }}" 
                   placeholder="{{ __('Full name') }}" 
                   required 
                   autofocus>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-user"></span>
                </div>
            </div>
            @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Email Field --}}
        <div class="input-group mb-3">
            <input type="email" 
                   name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" 
                   placeholder="{{ __('Email') }}" 
                   required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Password Field --}}
        <div class="input-group mb-3">
            <input type="password" 
                   name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   placeholder="{{ __('Password') }}" 
                   required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Confirm Password Field --}}
        <div class="input-group mb-3">
            <input type="password" 
                   name="password_confirmation" 
                   class="form-control" 
                   placeholder="{{ __('Retype password') }}" 
                   required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">
                    {{ __('Register') }}
                </button>
            </div>
        </div>
    </form>
@stop
