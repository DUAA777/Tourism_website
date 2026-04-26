@extends('layout.app')

@section('bodyClass', 'profile-layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')
<section class="profile-page">
    <div class="profile-page__inner">
        <header class="profile-head">
            @php
                $avatarPath = $user->profile_picture ? asset($user->profile_picture) : null;
                $avatarInitial = strtoupper(substr($user->name ?? 'U', 0, 1));
            @endphp
            <div class="profile-head__avatar-wrap">
                @if($avatarPath)
                    <img src="{{ $avatarPath }}" alt="{{ $user->name }}" class="profile-head__avatar" id="profileAvatarPreview">
                @else
                    <div class="profile-head__avatar profile-head__avatar--fallback" id="profileAvatarPreview">{{ $avatarInitial }}</div>
                @endif
            </div>
            <div class="profile-head__text">
                <p class="profile-head__eyebrow">Account Settings</p>
                <h1>{{ $user->name }}</h1>
                <p>{{ $user->email }}</p>
                <span class="profile-head__since">Member since {{ optional($user->created_at)->format('M Y') }}</span>
            </div>
        </header>

        @if(session('profile_success'))
            <div class="profile-alert profile-alert--success">{{ session('profile_success') }}</div>
        @endif

        @if(session('password_success'))
            <div class="profile-alert profile-alert--success">{{ session('password_success') }}</div>
        @endif

        @if(session('password_error'))
            <div class="profile-alert profile-alert--error">{{ session('password_error') }}</div>
        @endif

        @if($errors->any())
            <div class="profile-alert profile-alert--error">Please review the highlighted fields and try again.</div>
        @endif

        <div class="profile-grid">
            <article class="profile-card">
                <h2>Personal Information</h2>
                <p class="profile-card__desc">Update your profile photo, name, and phone number.</p>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-form">
                    @csrf
                    @method('PUT')

                    <div class="profile-form__field">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept=".jpg,.jpeg,.png,.webp">
                        @error('profile_picture')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($user->profile_picture)
                        <div class="profile-form__field">
                            <button
                                type="submit"
                                form="remove-profile-photo-form"
                                class="profile-btn profile-btn--ghost"
                            >
                                Remove Profile Picture
                            </button>
                        </div>
                    @endif

                    <div class="profile-form__field">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form__field">
                        <label for="profile_email">Email</label>
                        <input type="email" id="profile_email" value="{{ $user->email }}" disabled readonly>
                        <p class="profile-field-hint">Your email is used for sign-in and password recovery and cannot be changed here.</p>
                    </div>

                    <div class="profile-form__field">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                        @error('phone')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form__actions">
                        <button type="submit" class="profile-btn profile-btn--primary">Save Changes</button>
                    </div>
                </form>

                @if($user->profile_picture)
                    <form action="{{ route('profile.photo.delete') }}" method="POST" class="profile-remove-photo" id="remove-profile-photo-form">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </article>

            <article class="profile-card">
                <h2>Password Change Request</h2>
                <p class="profile-card__desc">Request a secure password reset link and finish the change from your email inbox.</p>

                <div class="profile-security">
                    <div class="profile-security__meta">
                        <span class="profile-security__label">Reset Password</span>
                        <strong>{{ $user->email }}</strong>
                    </div>
                    <p class="profile-security__note">
                        We will send a one-time password reset link to your account email. This is safer than changing it directly inside the profile page.
                    </p>
                </div>

                <form
                    action="{{ route('profile.password.request') }}"
                    method="POST"
                    class="profile-form profile-form--inline js-profile-reset-form"
                    data-reset-title="Request password reset?"
                    data-reset-message="Send a secure password reset link to {{ $user->email }}? You will finish the password change from your email inbox."
                    data-reset-confirm="Send Reset Link"
                >
                    @csrf
                    <div class="profile-form__actions">
                        <button type="submit" class="profile-btn profile-btn--primary">Request Password Change</button>
                    </div>
                </form>
            </article>
        </div>
    </div>
</section>

<div class="profile-confirm-modal" id="profileResetConfirm" aria-hidden="true">
    <div class="profile-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="profileResetConfirmTitle">
        <div class="profile-confirm-icon">
            <i class="ri-mail-send-line"></i>
        </div>
        <h3 id="profileResetConfirmTitle">Request password reset?</h3>
        <p id="profileResetConfirmMessage">We will send a secure password reset link to your email.</p>
        <div class="profile-confirm-actions">
            <button type="button" class="profile-btn profile-btn--ghost" data-reset-cancel>Cancel</button>
            <button type="button" class="profile-btn profile-btn--primary" data-reset-confirm>Send Reset Link</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile.js') }}"></script>
@endpush
