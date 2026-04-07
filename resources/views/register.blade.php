@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/register.css') }}">
@endpush

@section('content')
<div class="register-page">
    <div class="register-container">
        <div class="register-header">
            <h2>Create Account</h2>
            <p class="register-subtitle">Join us and discover amazing restaurants</p>
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

        <form class="register-form" method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf
            
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="ri-user-line"></i>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        placeholder="Enter your full name"
                        required 
                        autocomplete="name"
                        autofocus
                    >
                </div>
                <div class="field-error" id="nameError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>Please enter your full name</span>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="ri-mail-line"></i>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="Enter your email address"
                        required 
                        autocomplete="email"
                    >
                </div>
                <div class="field-error" id="emailError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>Please enter a valid email address</span>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="ri-phone-line"></i>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="{{ old('phone') }}" 
                        placeholder="Enter your phone number"
                        required 
                        autocomplete="tel"
                    >
                </div>
                <div class="field-error" id="phoneError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>Please enter a valid phone number</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="ri-lock-line"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Create a password"
                        required 
                        autocomplete="new-password"
                    >

                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
                <div class="password-strength-text" id="passwordStrengthText">
                    Use at least 8 characters with letters and numbers
                </div>
                <div class="field-error" id="passwordError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>Password must be at least 8 characters</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password <span class="required">*</span></label>
                <div class="input-icon">
                    <i class="ri-lock-line"></i>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        placeholder="Confirm your password"
                        required 
                        autocomplete="new-password"
                    >

                </div>
                <div class="field-error" id="confirmPasswordError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>Passwords do not match</span>
                </div>
            </div>

            <div class="terms-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span>
                        I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                        <a href="#" target="_blank">Privacy Policy</a>
                    </span>
                </label>
                <div class="field-error" id="termsError" style="display: none;">
                    <i class="ri-error-warning-line"></i>
                    <span>You must agree to the terms and conditions</span>
                </div>
            </div>

            <button class="btn-register" type="submit" id="registerBtn">
                Create Account
            </button>

            <div class="divider">
                <span>Or sign up with</span>
            </div>
            
            <a href="{{ url('auth/google') }}" class="btn-google">
                <i class="ri-google-fill"></i>
                Continue with Google
            </a>
        </form>

        <div class="login-link">
            <p>Already have an account?</p>
            <a href="{{ route('login') }}">
                Sign in here
                <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/register.js') }}"></script>
@endpush
