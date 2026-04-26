@extends('layout.admin')

@section('title', isset($user) ? 'Edit User' : 'Create User')

@section('content')
<div class="form-container">
    <h1>Edit User</h1>
    <p class="form-helper">Update profile details and access level. Email and password are managed through secure reset links.</p>
    
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <div class="readonly-field">
                <i class="ri-mail-line"></i>
                <span>{{ $user->email }}</span>
            </div>
            <small>Email is used for sign-in and password recovery, so admins should not edit it directly.</small>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            @error('phone') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            @if(auth()->id() === $user->id)
                <input type="hidden" name="is_admin" value="1">
            @endif
            <label class="admin-access-check" for="is_admin">
                <input type="checkbox" id="is_admin" name="is_admin" value="1"
                       {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                       {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                <span>Admin Access</span>
            </label>
            @if(auth()->id() === $user->id)
                <small>You cannot remove admin access from your own account.</small>
            @endif
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Update User</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>

    <div class="admin-password-reset-card">
        <div>
            <span class="section-kicker">Password</span>
            <h3>Send password reset link</h3>
            <p>This sends a secure reset email to {{ $user->email }}. Admins do not set or view user passwords directly.</p>
        </div>
        <form
            method="POST"
            action="{{ route('admin.users.password-reset', $user) }}"
            class="js-delete-form"
            data-delete-title="Send password reset link?"
            data-delete-message="Send a secure password reset email to {{ $user->email }}? The user will finish the password change from their inbox."
            data-delete-confirm="Send Reset Link"
            data-delete-confirm-class="btn-primary"
        >
            @csrf
            <button type="submit" class="btn-secondary">
                <i class="ri-mail-send-line"></i> Send Reset Link
            </button>
        </form>
    </div>
</div>
@endsection
