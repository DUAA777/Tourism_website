@extends('layout.admin')

@section('title', isset($user) ? 'Edit User' : 'Create User')

@section('content')
<div class="form-container">
    <h1>{{ isset($user) ? 'Edit User' : 'Create New User' }}</h1>
    
    <form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if(isset($user)) @method('PUT') @endif
        
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
            @error('phone') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="form-group">
            <label for="password">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
            <input type="password" id="password" name="password" {{ isset($user) ? '' : 'required' }}>
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation">
        </div>
        
        <div class="form-group checkbox">
            <label>
                <input type="checkbox" name="is_admin" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}>
                Admin Access
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">{{ isset($user) ? 'Update' : 'Create' }} User</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection