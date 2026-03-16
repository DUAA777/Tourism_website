@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
<style>
    .error-msg { color: #dc3545; font-size: 0.9rem; margin-bottom: 10px; }
    .btn-google { background: #db4437; color: white; margin-top: 10px; width: 100%; border: none; padding: 10px; cursor: pointer; border-radius: 4px; display: block; text-align: center; text-decoration: none; }
    .divider { margin: 20px 0; text-align: center; border-bottom: 1px solid #ccc; line-height: 0.1em; }
    .divider span { background:#fff; padding:0 10px; }
</style>
@endpush

@section('content')
<div class="login-container">
    <h2>Login</h2>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="login-form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button class="btn-login" type="submit">Login</button>
        
        <div class="divider"><span>OR</span></div>
        
        <a href="{{ url('auth/google') }}" class="btn-google">
            Login with Google
        </a>
        <a href="{{ route('register') }}" style="display: block; margin-top: 15px; text-align: center;">Don't have an account? Register</a>
    </form>
</div>
@endsection