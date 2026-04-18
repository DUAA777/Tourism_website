@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/password-reset.css') }}">
@endpush

@section('content')
<div class="login-page">
    <div class="login-container password-reset-container">
        <div class="login-header">
            <h2>Reset your password</h2>
            <p class="login-subtitle">
                Enter the email address linked to your account and we will send you a reset link.
            </p>
        </div>

        @if (session('status'))
            <div class="success-msg">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('password.email') }}">
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

            <button class="btn-login" type="submit">
                Email Password Reset Link
            </button>
        </form>

        <div class="auth-link-row">
            <a href="{{ route('login') }}" class="auth-secondary-link">
                <i class="ri-arrow-left-line"></i>
                Back to sign in
            </a>
            <a href="{{ route('register') }}" class="auth-secondary-link">
                Create account
                <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>
@endsection
