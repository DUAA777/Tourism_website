@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/password-reset.css') }}">
@endpush

@section('content')
<div class="login-page">
    <div class="login-container password-reset-container">
        <div class="login-header">
            <h2>Create a new password</h2>
            <p class="login-subtitle">
                Choose a strong password with at least 8 characters so you can sign back in securely.
            </p>
        </div>

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">
            <input
                type="hidden"
                name="email"
                value="{{ old('email', $email) }}"
            >

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-icon">
                    <i class="ri-lock-line"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a new password"
                        required
                        autocomplete="new-password"
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-icon">
                    <i class="ri-lock-line"></i>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your new password"
                        required
                        autocomplete="new-password"
                    >
                </div>
            </div>

            <button class="btn-login" type="submit">
                Save New Password
            </button>
        </form>

        <div class="auth-link-row auth-link-row-single">
            <a href="{{ route('login') }}" class="auth-secondary-link">
                <i class="ri-arrow-left-line"></i>
                Back to sign in
            </a>
        </div>
    </div>
</div>
@endsection
