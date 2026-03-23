@extends('layout.app')

@push('meta')
<title>Login | Yalla Nemshi</title>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
@endpush

@section('content')

<section class="auth-page">
  <div class="auth-shell">

    <div class="auth-brand">
      <a href="{{ route('home') }}" class="auth-brand__logo">YALLA NEMSHI</a>
      <p class="auth-brand__kicker">WELCOME BACK</p>
      <h1 class="auth-brand__title">Log in to continue planning your next Lebanon adventure</h1>
      <p class="auth-brand__subtitle">
        Access your saved places, preferences, and personalized travel plans in one clean dashboard.
      </p>

      <div class="auth-brand__highlights">
        <div class="auth-highlight">
          <span><i class="ri-map-pin-line"></i></span>
          <div>
            <h4>Saved destinations</h4>
            <p>Quickly return to your favorite places and travel ideas.</p>
          </div>
        </div>

        <div class="auth-highlight">
          <span><i class="ri-road-map-line"></i></span>
          <div>
            <h4>Smart trip planning</h4>
            <p>Continue building custom outings based on your mood and budget.</p>
          </div>
        </div>

        <div class="auth-highlight">
          <span><i class="ri-user-heart-line"></i></span>
          <div>
            <h4>Your travel profile</h4>
            <p>Keep your preferences ready for faster and better recommendations.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="auth-card">
      <div class="auth-card__head">
        <p class="auth-card__kicker">ACCOUNT LOGIN</p>
        <h2>Sign In</h2>
        <p>Use your account details to continue.</p>
      </div>

      <form class="auth-form" id="loginForm">
        <div class="auth-field">
          <label for="loginEmail">Email Address</label>
          <input id="loginEmail" type="email" placeholder="Enter your email" required>
        </div>

        <div class="auth-field">
          <label for="loginPassword">Password</label>
          <div class="auth-password">
            <input id="loginPassword" type="password" placeholder="Enter your password" required minlength="6">
            <button type="button" class="auth-password__toggle" id="togglePassword">
              <i class="ri-eye-line"></i>
            </button>
          </div>
        </div>

        <div class="auth-row">
          <label class="auth-check">
            <input type="checkbox" id="rememberMe">
            <span>Remember me</span>
          </label>

          <a href="#" class="auth-link">Forgot password?</a>
        </div>

        <button type="submit" class="auth-btn auth-btn--primary">Log In</button>

        <a href="{{ route('home') }}" class="auth-btn auth-btn--ghost">Back to Home</a>
      </form>

      <div class="auth-divider">
        <span>New here?</span>
      </div>

      <a href="{{ route('register') }}" class="auth-register-link">
        Create an account
      </a>
    </div>

  </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('loginPassword');
    const loginForm = document.getElementById('loginForm');

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.innerHTML = isPassword
            ? '<i class="ri-eye-off-line"></i>'
            : '<i class="ri-eye-line"></i>';
    });

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const email = document.getElementById('loginEmail').value.trim();
        const password = passwordInput.value.trim();

        if (!email || !password) {
            alert('Please fill in all required fields.');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters.');
            return;
        }

        alert('Login form submitted successfully.');
    });
});
</script>
@endpush