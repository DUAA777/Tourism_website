@extends('layout.app')

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

        @if($errors->any())
            <div class="profile-alert profile-alert--error">Please review the highlighted fields and try again.</div>
        @endif

        <div class="profile-grid">
            <article class="profile-card">
                <h2>Personal Information</h2>
                <p class="profile-card__desc">Update your profile photo, name, email, and phone number.</p>

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

                    <div class="profile-form__field">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form__field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
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
                    <form action="{{ route('profile.photo.delete') }}" method="POST" class="profile-remove-photo">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="profile-btn profile-btn--ghost">Remove Profile Picture</button>
                    </form>
                @endif
            </article>

            <article class="profile-card">
                <h2>Password Change Request</h2>
                <p class="profile-card__desc">Update your password securely by confirming your current password.</p>

                <form action="{{ route('profile.password.update') }}" method="POST" class="profile-form">
                    @csrf
                    @method('PUT')

                    <div class="profile-form__field">
                        <label for="current_password">Current Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle" data-target="current_password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form__field">
                        <label for="password">New Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="password-toggle" data-target="password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="profile-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="profile-form__field">
                        <label for="password_confirmation">Confirm New Password</label>
                        <div class="profile-password-wrap">
                            <input type="password" id="password_confirmation" name="password_confirmation" required>
                            <button type="button" class="password-toggle" data-target="password_confirmation">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="profile-form__actions">
                        <button type="submit" class="profile-btn profile-btn--primary">Change Password</button>
                    </div>
                </form>
            </article>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/profile.js') }}"></script>
@endpush
