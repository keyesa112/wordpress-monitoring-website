@extends('adminlte::auth.login')

@section('auth_header', 'Sign in to start your session')

@section('auth_body')
    <form action="{{ route('login') }}" method="post">
        @csrf

        {{-- Email Field --}}
        <div class="input-group mb-3">
            <input type="email" 
                   name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" 
                   placeholder="{{ __('Email') }}" 
                   autofocus 
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

        {{-- Remember Me Checkbox --}}
        <div class="row">
            <div class="col-8">
                <div class="icheck-primary">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">
                        {{ __('Remember Me') }}
                    </label>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">
                    {{ __('Sign In') }}
                </button>
            </div>
        </div>
    </form>

    {{-- Register Link (optional) --}}
    @if (Route::has('register'))
        <p class="mb-0">
            <a href="{{ route('register') }}" class="text-center">
                {{ __('Register') }}
            </a>
        </p>
    @endif
@stop

@section('auth_footer')
    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
@stop
