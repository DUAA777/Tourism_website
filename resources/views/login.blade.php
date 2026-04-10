@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
@endpush

@section('content')
<div class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome Back</h2>
            <p class="login-subtitle">Sign in to continue your culinary journey</p>
        </div>

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="success-msg">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="ri-mail-line"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="Enter your email"
                        required 
                        autocomplete="email"
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="ri-lock-line"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required 
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">
                        Forgot Password?
                    </a>
                @endif
            </div>

            <button class="btn-login" type="submit" id="loginBtn">
                Sign In
            </button>
            
            <div class="divider">
                <span>Or continue with</span>
            </div>
            
            <a href="{{ url('auth/google') }}" class="btn-google">
                <i class="ri-google-fill"></i>
                Sign in with Google
            </a>
        </form>

        <div class="register-link">
            <p>Don't have an account?</p>
            <a href="{{ route('register') }}">
                Create an account
                <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/login.js') }}"></script>
@endpush
