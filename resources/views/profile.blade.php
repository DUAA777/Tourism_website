@extends('layout.app')

@push('meta')
<title>Profile | Yalla Nemshi</title>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
@endpush

@section('content')

<section class="profile-page">
  <div class="profile-shell">

    <div class="profile-topbar">
      <div>
        <p class="profile-kicker">MY ACCOUNT</p>
        <h1 class="profile-title">My Profile</h1>
        <p class="profile-subtitle">
          Manage your details, travel preferences, saved places, and recent plans.
        </p>
      </div>

      <div class="profile-topbar__actions">
        <a href="{{ route('places.index') }}" class="profile-btn profile-btn--ghost">Explore Places</a>
        <a href="{{ route('chatbot') }}" class="profile-btn profile-btn--primary">Plan a Trip</a>
      </div>
    </div>

    <div class="profile-grid">

      <aside class="profile-sidebar">
        <div class="profile-card profile-card--sticky">

          <div class="profile-avatar-wrap">
            <img
              id="profileAvatarPreview"
              src="{{ asset('images/destination-3.jpg') }}"
              alt="Profile avatar"
              class="profile-avatar"
            >
            <label for="profileAvatarInput" class="profile-avatar-edit">Change Photo</label>
            <input type="file" id="profileAvatarInput" accept="image/*" hidden>
          </div>

          <div class="profile-sidebar__text">
            <h2 id="profileDisplayName">Traveler Name</h2>
            <p id="profileDisplayEmail">traveler@example.com</p>
          </div>

          <div class="profile-stats">
            <div class="profile-stat">
              <span class="profile-stat__num">12</span>
              <span class="profile-stat__label">Saved Places</span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat__num">5</span>
              <span class="profile-stat__label">Trips Planned</span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat__num">4.8</span>
              <span class="profile-stat__label">Travel Mood</span>
            </div>
          </div>

          <div class="profile-sidebar__quick">
            <a href="#profileInfo" class="profile-quick-link">Personal Info</a>
            <a href="#travelPrefs" class="profile-quick-link">Travel Preferences</a>
            <a href="#savedPlaces" class="profile-quick-link">Saved Places</a>
            <a href="#recentPlans" class="profile-quick-link">Recent Plans</a>
          </div>
        </div>
      </aside>

      <div class="profile-main">

        <section class="profile-card" id="profileInfo">
          <div class="section-head">
            <div>
              <p class="section-head__kicker">PROFILE DETAILS</p>
              <h3>Personal Information</h3>
            </div>
          </div>

          <form id="profileForm" class="profile-form">
            <div class="profile-form__grid">
              <div class="profile-field">
                <label for="fullName">Full Name</label>
                <input id="fullName" type="text" placeholder="Enter your full name">
              </div>

              <div class="profile-field">
                <label for="email">Email Address</label>
                <input id="email" type="email" placeholder="Enter your email">
              </div>

              <div class="profile-field">
                <label for="phone">Phone Number</label>
                <input id="phone" type="text" placeholder="Enter your phone number">
              </div>

              <div class="profile-field">
                <label for="city">City</label>
                <select id="city">
                  <option value="">Select your city</option>
                  <option>Beirut</option>
                  <option>Batroun</option>
                  <option>Byblos</option>
                  <option>Baalbek</option>
                  <option>Sidon</option>
                  <option>Tripoli</option>
                  <option>Chouf</option>
                </select>
              </div>
            </div>

            <div class="profile-field">
              <label for="bio">Short Bio</label>
              <textarea id="bio" rows="4" placeholder="Tell us a little about your travel style..."></textarea>
            </div>

            <div class="profile-form__actions">
              <button type="button" class="profile-btn profile-btn--primary" id="saveProfileBtn">Save Changes</button>
              <button type="button" class="profile-btn profile-btn--ghost" id="resetProfileBtn">Reset</button>
            </div>
          </form>
        </section>

        <section class="profile-card" id="travelPrefs">
          <div class="section-head">
            <div>
              <p class="section-head__kicker">TRAVEL STYLE</p>
              <h3>Travel Preferences</h3>
            </div>
          </div>

          <div class="profile-preferences">
            <label class="pref-chip">
              <input type="checkbox" value="Beach">
              <span>Beach</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Nature">
              <span>Nature</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Historic">
              <span>Historic</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Food">
              <span>Food</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="City">
              <span>City</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Luxury">
              <span>Luxury</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Budget">
              <span>Budget</span>
            </label>

            <label class="pref-chip">
              <input type="checkbox" value="Photography">
              <span>Photography</span>
            </label>
          </div>
        </section>

        <section class="profile-card" id="savedPlaces">
          <div class="section-head">
            <div>
              <p class="section-head__kicker">MY FAVORITES</p>
              <h3>Saved Places</h3>
            </div>
            <a href="{{ route('places.index') }}" class="section-link">View all</a>
          </div>

          <div class="saved-grid">
            <article class="saved-place">
              <img src="{{ asset('images/destination-1.jpg') }}" alt="Baalbek">
              <div class="saved-place__body">
                <h4>Baalbek</h4>
                <p><i class="ri-map-pin-2-fill"></i> Baalbek, Lebanon</p>
                <a href="{{ route('places.show', 'roman-temple-baalbek') }}">Open Details</a>
              </div>
            </article>

            <article class="saved-place">
              <img src="{{ asset('images/destination-2.jpg') }}" alt="Sidon">
              <div class="saved-place__body">
                <h4>Sidon</h4>
                <p><i class="ri-map-pin-2-fill"></i> Sidon, Lebanon</p>
                <a href="{{ route('places.show', 'sidon-sea-castle') }}">Open Details</a>
              </div>
            </article>

            <article class="saved-place">
              <img src="{{ asset('images/destination-3.jpg') }}" alt="Beirut">
              <div class="saved-place__body">
                <h4>Downtown Beirut</h4>
                <p><i class="ri-map-pin-2-fill"></i> Beirut, Lebanon</p>
                <a href="{{ route('places.show', 'downtown-beirut') }}">Open Details</a>
              </div>
            </article>
          </div>
        </section>

        <section class="profile-card" id="recentPlans">
          <div class="section-head">
            <div>
              <p class="section-head__kicker">RECENT ACTIVITY</p>
              <h3>Recent Plans</h3>
            </div>
          </div>

          <div class="recent-list">
            <div class="recent-item">
              <div class="recent-item__icon"><i class="ri-road-map-line"></i></div>
              <div class="recent-item__content">
                <h4>Batroun sunset + coffee</h4>
                <p>Saved as a half-day beach plan with a relaxed vibe.</p>
              </div>
            </div>

            <div class="recent-item">
              <div class="recent-item__icon"><i class="ri-restaurant-2-line"></i></div>
              <div class="recent-item__content">
                <h4>Byblos history + lunch</h4>
                <p>Balanced route with a historic site and a food stop.</p>
              </div>
            </div>

            <div class="recent-item">
              <div class="recent-item__icon"><i class="ri-landscape-line"></i></div>
              <div class="recent-item__content">
                <h4>Nature day in Chouf</h4>
                <p>Full-day nature plan focused on calm scenery and walking spots.</p>
              </div>
            </div>
          </div>
        </section>

      </div>
    </div>
  </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const storageKey = 'yallaNemshiProfile';

    const fields = {
        fullName: document.getElementById('fullName'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        city: document.getElementById('city'),
        bio: document.getElementById('bio')
    };

    const displayName = document.getElementById('profileDisplayName');
    const displayEmail = document.getElementById('profileDisplayEmail');

    const saveBtn = document.getElementById('saveProfileBtn');
    const resetBtn = document.getElementById('resetProfileBtn');

    const avatarInput = document.getElementById('profileAvatarInput');
    const avatarPreview = document.getElementById('profileAvatarPreview');

    const preferenceInputs = document.querySelectorAll('.pref-chip input');

    const defaultData = {
        fullName: 'Traveler Name',
        email: 'traveler@example.com',
        phone: '',
        city: '',
        bio: '',
        preferences: [],
        avatar: '{{ asset('images/destination-3.jpg') }}'
    };

    function loadProfile() {
        const stored = localStorage.getItem(storageKey);
        const data = stored ? JSON.parse(stored) : defaultData;

        fields.fullName.value = data.fullName || '';
        fields.email.value = data.email || '';
        fields.phone.value = data.phone || '';
        fields.city.value = data.city || '';
        fields.bio.value = data.bio || '';

        displayName.textContent = data.fullName || defaultData.fullName;
        displayEmail.textContent = data.email || defaultData.email;

        avatarPreview.src = data.avatar || defaultData.avatar;

        preferenceInputs.forEach(input => {
            input.checked = (data.preferences || []).includes(input.value);
        });
    }

    function saveProfile() {
        const preferences = Array.from(preferenceInputs)
            .filter(input => input.checked)
            .map(input => input.value);

        const currentData = JSON.parse(localStorage.getItem(storageKey) || '{}');

        const data = {
            ...defaultData,
            ...currentData,
            fullName: fields.fullName.value.trim() || defaultData.fullName,
            email: fields.email.value.trim() || defaultData.email,
            phone: fields.phone.value.trim(),
            city: fields.city.value,
            bio: fields.bio.value.trim(),
            preferences
        };

        localStorage.setItem(storageKey, JSON.stringify(data));

        displayName.textContent = data.fullName;
        displayEmail.textContent = data.email;

        saveBtn.textContent = 'Saved!';
        setTimeout(() => {
            saveBtn.textContent = 'Save Changes';
        }, 1200);
    }

    function resetProfile() {
        localStorage.removeItem(storageKey);
        loadProfile();
    }

    saveBtn.addEventListener('click', saveProfile);
    resetBtn.addEventListener('click', resetProfile);

    avatarInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            avatarPreview.src = e.target.result;

            const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
            stored.avatar = e.target.result;
            localStorage.setItem(storageKey, JSON.stringify({
                ...defaultData,
                ...stored
            }));
        };
        reader.readAsDataURL(file);
    });

    loadProfile();
});
</script>
@endpush