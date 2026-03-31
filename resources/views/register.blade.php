@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
<style>
    :root {
        --register-bg: #f6f7fb;
        --register-card-bg: #ffffff;
        --register-text-dark: #1b1b1f;
        --register-text-soft: #6f7380;
        --register-primary: #ff6b2c;
        --register-primary-dark: #e85d22;
        --register-error: #dc3545;
        --register-success: #28a745;
        --register-shadow: 0 20px 50px rgba(16, 24, 40, 0.08);
        --register-shadow-hover: 0 30px 60px rgba(16, 24, 40, 0.12);
        --register-radius: 30px;
        --register-radius-md: 16px;
        --register-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .register-page {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--home-bg) 0%, #ffffff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        overflow: hidden;
    }

    /* Animated background elements */
    .register-page::before,
    .register-page::after {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(255, 107, 44, 0.05) 0%, rgba(255, 107, 44, 0.02) 100%);
        pointer-events: none;
    }

    .register-page::before {
        top: -150px;
        right: -150px;
        animation: floatRegister 20s ease-in-out infinite;
    }

    .register-page::after {
        bottom: -150px;
        left: -150px;
        animation: floatRegister 15s ease-in-out infinite reverse;
    }

    @keyframes floatRegister {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(40px, -40px) scale(1.1); }
    }

    .register-container {
        max-width: 780px;
        width: 100%;
        background: var(--register-card-bg);
        border-radius: var(--register-radius);
        box-shadow: var(--register-shadow);
        padding: 48px 40px;
        position: relative;
        margin-top:100px;
        z-index: 1;
        animation: slideUpRegister 0.5s ease-out;
    }

    @keyframes slideUpRegister {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Header Section */
    .register-header {
        text-align: center;
        margin-bottom: 36px;
    }

    .register-header h2 {
        font-size: 2rem;
        font-weight: 800;
        margin: 0 0 12px 0;
        background: linear-gradient(135deg, var(--register-text-dark) 0%, var(--register-primary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .register-subtitle {
        color: var(--register-text-soft);
        font-size: 0.95rem;
        margin: 0;
    }

    /* Form Styles */
    .register-form {
        margin-top: 32px;
    }

    .form-group {
        margin-bottom: 24px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--register-text-dark);
        font-size: 0.9rem;
    }

    .form-group label .required {
        color: var(--register-error);
        margin-left: 4px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: var(--register-radius-md);
        font-size: 1rem;
        font-family: inherit;
        transition: var(--register-transition);
        background: #fafbfc;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--register-primary);
        box-shadow: 0 0 0 4px rgba(255, 107, 44, 0.1);
        background: white;
    }

    .form-group input:hover,
    .form-group select:hover,
    .form-group textarea:hover {
        border-color: #d1d5db;
    }

    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
        border-color: var(--register-error);
    }

    /* Input with icon */
    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--register-text-soft);
        font-size: 1.2rem;
        pointer-events: none;
        transition: var(--register-transition);
    }

    .input-icon input,
    .input-icon select,
    .input-icon textarea {
        padding-left: 46px;
    }

    .input-icon textarea {
        padding-top: 12px;
        padding-bottom: 12px;
        min-height: 100px;
        resize: vertical;
    }

    .input-icon i.textarea-icon {
        top: 20px;
        transform: none;
    }

    /* Password toggle */
    .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--register-text-soft);
        transition: var(--register-transition);
        z-index: 2;
        background: transparent;
        border: none;
        font-size: 1.2rem;
    }

    .password-toggle:hover {
        color: var(--register-primary);
    }

    /* Password strength meter */
    .password-strength {
        margin-top: 8px;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
        transition: var(--register-transition);
    }

    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    .password-strength-text {
        font-size: 0.75rem;
        margin-top: 6px;
        color: var(--register-text-soft);
    }

    /* Error messages */
    .field-error {
        color: var(--register-error);
        font-size: 0.75rem;
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .field-error i {
        font-size: 0.9rem;
    }

    /* Terms and conditions */
    .terms-group {
        margin-bottom: 28px;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
        font-size: 0.9rem;
        color: var(--register-text-soft);
        line-height: 1.5;
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        cursor: pointer;
        accent-color: var(--register-primary);
        flex-shrink: 0;
    }

    .checkbox-label a {
        color: var(--register-primary);
        text-decoration: none;
        font-weight: 600;
    }

    .checkbox-label a:hover {
        text-decoration: underline;
    }

    /* Register Button */
    .btn-register {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--register-primary) 0%, var(--register-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: var(--register-radius-md);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--register-transition);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }

    .btn-register::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-register:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 107, 44, 0.3);
    }

    .btn-register:active {
        transform: translateY(0);
    }

    /* Loading State */
    .btn-register.loading {
        position: relative;
        color: transparent;
    }

    .btn-register.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid white;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spinner 0.6s linear infinite;
    }

    @keyframes spinner {
        to { transform: rotate(360deg); }
    }

    /* Divider */
    .divider {
        margin: 28px 0;
        text-align: center;
        border-bottom: 1px solid #e5e7eb;
        line-height: 0.1em;
        position: relative;
    }

    .divider span {
        background: var(--register-card-bg);
        padding: 0 16px;
        color: var(--register-text-soft);
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Google Button */
    .btn-google {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        padding: 14px;
        background: white;
        color: #5f6368;
        border: 2px solid #e5e7eb;
        border-radius: var(--register-radius-md);
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--register-transition);
        text-decoration: none;
        margin-bottom: 20px;
    }

    .btn-google:hover {
        background: #f8f9fa;
        border-color: var(--register-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-google i {
        font-size: 1.2rem;
    }

    /* Login Link */
    .login-link {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .login-link p {
        color: var(--register-text-soft);
        margin: 0 0 8px 0;
        font-size: 0.9rem;
    }

    .login-link a {
        color: var(--register-primary);
        text-decoration: none;
        font-weight: 700;
        transition: var(--register-transition);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .login-link a:hover {
        color: var(--register-primary-dark);
        gap: 10px;
    }

    /* Error Messages Container */
    .error-msg {
        background: #fee2e2;
        border-left: 4px solid var(--register-error);
        border-radius: var(--register-radius-md);
        padding: 16px;
        margin-bottom: 24px;
        animation: shakeRegister 0.5s ease-in-out;
    }

    @keyframes shakeRegister {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .error-msg p {
        margin: 0;
        color: var(--register-error);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .error-msg p::before {
        content: '⚠️';
        font-size: 1rem;
    }

    /* Success Message */
    .success-msg {
        background: #d4edda;
        border-left: 4px solid var(--register-success);
        border-radius: var(--register-radius-md);
        padding: 16px;
        margin-bottom: 24px;
    }

    .success-msg p {
        margin: 0;
        color: var(--register-success);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Animation for form elements */
    .form-group {
        animation: fadeInUpRegister 0.4s ease-out forwards;
        opacity: 0;
    }

    .form-group:nth-child(1) { animation-delay: 0.05s; }
    .form-group:nth-child(2) { animation-delay: 0.1s; }
    .form-group:nth-child(3) { animation-delay: 0.15s; }
    .form-group:nth-child(4) { animation-delay: 0.2s; }
    .form-group:nth-child(5) { animation-delay: 0.25s; }
    .terms-group { animation: fadeInUpRegister 0.4s ease-out 0.3s forwards; opacity: 0; }
    .btn-register { animation: fadeInUpRegister 0.4s ease-out 0.35s forwards; opacity: 0; }
    .divider { animation: fadeInUpRegister 0.4s ease-out 0.4s forwards; opacity: 0; }
    .btn-google { animation: fadeInUpRegister 0.4s ease-out 0.45s forwards; opacity: 0; }
    .login-link { animation: fadeInUpRegister 0.4s ease-out 0.5s forwards; opacity: 0; }

    @keyframes fadeInUpRegister {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 640px) {
        .register-container {
            padding: 32px 24px;
        }

        .register-header h2 {
            font-size: 1.75rem;
        }

        .btn-register,
        .btn-google {
            padding: 14px;
        }

        .checkbox-label {
            font-size: 0.85rem;
        }
    }
</style>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password visibility toggle
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('ri-eye-line');
                        icon.classList.toggle('ri-eye-off-line');
                    }
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'password_confirmation');

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');

        function checkPasswordStrength(password) {
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    message = 'Weak password';
                    strengthBar.style.backgroundColor = '#dc3545';
                    strengthBar.style.width = '25%';
                    break;
                case 2:
                    message = 'Fair password';
                    strengthBar.style.backgroundColor = '#ffc107';
                    strengthBar.style.width = '50%';
                    break;
                case 3:
                    message = 'Good password';
                    strengthBar.style.backgroundColor = '#28a745';
                    strengthBar.style.width = '75%';
                    break;
                case 4:
                    message = 'Strong password';
                    strengthBar.style.backgroundColor = '#28a745';
                    strengthBar.style.width = '100%';
                    break;
            }
            
            strengthText.textContent = message;
            return strength >= 2;
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }

        // Form validation
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');

        function validateField(field, validationFn, errorElement, errorMessage) {
            const value = field.value.trim();
            if (!validationFn(value)) {
                errorElement.style.display = 'flex';
                errorElement.querySelector('span').textContent = errorMessage;
                field.classList.add('error');
                return false;
            } else {
                errorElement.style.display = 'none';
                field.classList.remove('error');
                return true;
            }
        }

        function validateName(name) {
            return name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
        }

        function validateEmail(email) {
            return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
        }

        function validatePhone(phone) {
            return /^[\d\s\-+()]{10,}$/.test(phone);
        }

        function validatePassword(password) {
            return password.length >= 8;
        }

        function validateConfirmPassword(password, confirmPassword) {
            return password === confirmPassword && confirmPassword.length > 0;
        }

        function validateTerms(termsChecked) {
            return termsChecked === true;
        }

        if (registerForm) {
            const nameField = document.getElementById('name');
            const emailField = document.getElementById('email');
            const phoneField = document.getElementById('phone');
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            const termsCheckbox = document.getElementById('terms');

            const nameError = document.getElementById('nameError');
            const emailError = document.getElementById('emailError');
            const phoneError = document.getElementById('phoneError');
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmPasswordError');
            const termsError = document.getElementById('termsError');

            if (nameField) {
                nameField.addEventListener('blur', function() {
                    validateField(this, validateName, nameError, 'Please enter a valid name (at least 2 characters, letters only)');
                });
            }

            if (emailField) {
                emailField.addEventListener('blur', function() {
                    validateField(this, validateEmail, emailError, 'Please enter a valid email address');
                });
            }

            if (phoneField) {
                phoneField.addEventListener('blur', function() {
                    validateField(this, validatePhone, phoneError, 'Please enter a valid phone number (at least 10 digits)');
                });
            }

            if (passwordField) {
                passwordField.addEventListener('blur', function() {
                    validateField(this, validatePassword, passwordError, 'Password must be at least 8 characters');
                });
            }

            if (confirmField && passwordField) {
                confirmField.addEventListener('blur', function() {
                    validateField(this, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                });
                
                passwordField.addEventListener('input', function() {
                    if (confirmField.value) {
                        validateField(confirmField, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                    }
                });
            }

            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', function() {
                    validateField(this, (val) => validateTerms(this.checked), termsError, 'You must agree to the terms and conditions');
                });
            }

            registerForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                isValid &= validateField(nameField, validateName, nameError, 'Please enter a valid name (at least 2 characters, letters only)');
                isValid &= validateField(emailField, validateEmail, emailError, 'Please enter a valid email address');
                isValid &= validateField(phoneField, validatePhone, phoneError, 'Please enter a valid phone number (at least 10 digits)');
                isValid &= validateField(passwordField, validatePassword, passwordError, 'Password must be at least 8 characters');
                isValid &= validateField(confirmField, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                isValid &= validateField(termsCheckbox, (val) => validateTerms(termsCheckbox.checked), termsError, 'You must agree to the terms and conditions');
                
                if (!isValid) {
                    e.preventDefault();
                    registerBtn.classList.remove('loading');
                    // Scroll to first error
                    const firstError = document.querySelector('.field-error[style*="display: flex"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    registerBtn.classList.add('loading');
                    registerBtn.disabled = true;
                }
            });
        }

        // Real-time validation styling
        const inputs = document.querySelectorAll('.form-group input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
            
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        });
    });
</script>
@endsection