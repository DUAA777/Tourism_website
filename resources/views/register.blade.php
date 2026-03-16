@extends('layout.app')

@section('content')
<div class="login-container">
    <h2>Create Account</h2>

    <form class="login-form" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
        </div>

        <div class="form-group">
            <label>Phone:</label>
            <input type="text" name="phone" value="{{ old('phone') }}" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="password_confirmation" required>
        </div>

        <button class="btn-login" type="submit">Register</button>
    </form>
</div>
@endsection