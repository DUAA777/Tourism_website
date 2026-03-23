@extends('layout.app')

@push('meta')
<title>Register | Yalla Nemshi</title>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/register.css') }}">
@endpush

@section('content')

<section class="auth-page">
  <div class="auth-shell">

    <div class="auth-brand">
      <a href="{{ route('home') }}" class="auth-brand__logo">YALLA NEMSHI</a>
      <p class="auth-brand__kicker">CREATE ACCOUNT</p>
      <h1 class="auth-brand__title">Join and start planning better trips across Lebanon</h1>
      <p class="auth-brand__subtitle">
        Create your account to save favorite places, personalize recommendations,
        and build smarter travel plans anytime.
      </p>

      <div class="auth-brand__highlights">
        <div class="auth-highlight">
          <span><i class="ri-heart-3-line"></i></span>
          <div>
            <h4>Save your favorite spots</h4>
            <p>Keep your top destinations ready whenever you want to plan again.</p>
          </div>
        </div>

        <div class="auth-highlight">
          <span><i class="ri-filter-3-line"></i></span>
          <div>
            <h4>Personalized preferences</h4>
            <p>Choose your travel style once and get smarter recommendations later.</p>
          </div>
        </div>

        <div class="auth-highlight">
          <span><i class="ri-road-map-line"></i></span>
          <div>
            <h4>Build day plans faster</h4>
            <p>Use your account to organize and improve your trip ideas over time.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="auth-card">
      <div class="auth-card__head">
        <p class="auth-card__kicker">NEW ACCOUNT</p>
        <h2>Sign Up</h2>
        <p>Create your profile and start exploring.</p>
      </div>

      <form class="auth-form" id="registerForm">
        <div class="auth-field">
          <label for="registerName">Full Name</label>
          <input id="registerName" type="text" placeholder="Enter your full name" required>
        </div>

        <div class="auth-field">
          <label for="registerEmail">Email Address</label>
          <input id="registerEmail" type="email" placeholder="Enter your email" required>
        </div>

        <div class="auth-field">
          <label for="registerPassword">Password</label>
          <div class="auth-password">
            <input id="registerPassword" type="password" placeholder="Create a password" required minlength="6">
            <button type="button" class="auth-password__toggle" id="toggleRegisterPassword">
              <i class="ri-eye-line"></i>
            </button>
          </div>
        </div>

        <div class="auth-field">
          <label for="registerConfirmPassword">Confirm Password</label>
          <div class="auth-password">
            <input id="registerConfirmPassword" type="password" placeholder="Confirm your password" required minlength="6">
            <button type="button" class="auth-password__toggle" id="toggleRegisterConfirmPassword">
              <i class="ri-eye-line"></i>
            </button>
          </div>
        </div>

        <div class="auth-row">
          <label class="auth-check">
            <input type="checkbox" id="agreeTerms" required>
            <span>I agree to the terms and privacy policy</span>
          </label>
        </div>

        <button type="submit" class="auth-btn auth-btn--primary">Create Account</button>

        <a href="{{ route('home') }}" class="auth-btn auth-btn--ghost">Back to Home</a>
      </form>

      <div class="auth-divider">
        <span>Already have an account?</span>
      </div>

      <a href="{{ route('login') }}" class="auth-register-link">
        Log in instead
      </a>
    </div>

  </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('registerPassword');
    const confirmPasswordInput = document.getElementById('registerConfirmPassword');

    const togglePassword = document.getElementById('toggleRegisterPassword');
    const toggleConfirmPassword = document.getElementById('toggleRegisterConfirmPassword');

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.innerHTML = isPassword
            ? '<i class="ri-eye-off-line"></i>'
            : '<i class="ri-eye-line"></i>';
    });

    toggleConfirmPassword.addEventListener('click', function () {
        const isPassword = confirmPasswordInput.type === 'password';
        confirmPasswordInput.type = isPassword ? 'text' : 'password';
        this.innerHTML = isPassword
            ? '<i class="ri-eye-off-line"></i>'
            : '<i class="ri-eye-line"></i>';
    });

    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const name = document.getElementById('registerName').value.trim();
        const email = document.getElementById('registerEmail').value.trim();
        const password = passwordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();
        const agreeTerms = document.getElementById('agreeTerms').checked;

        if (!name || !email || !password || !confirmPassword) {
            alert('Please fill in all required fields.');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters.');
            return;
        }

        if (password !== confirmPassword) {
            alert('Passwords do not match.');
            return;
        }

        if (!agreeTerms) {
            alert('Please agree to the terms and privacy policy.');
            return;
        }

        alert('Registration form submitted successfully.');
    });
});
</script>
@endpush