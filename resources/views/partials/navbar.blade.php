<nav>
  @php
    $homeActive = request()->routeIs('home');
    $aboutActive = request()->routeIs('aboutUs');
    $chatbotActive = request()->routeIs('chatbot*');
    $restaurantsActive = request()->routeIs('restaurants*');
    $hotelsActive = request()->routeIs('hotels*');
    $contactActive = request()->routeIs('contactUs');
    $loginActive = request()->routeIs('login');
    $registerActive = request()->routeIs('register');
    $profileActive = request()->routeIs('profile*');
    $adminActive = request()->routeIs('admin.*');
  @endphp

  <div class="nav__header">
    <div class="nav__logo">
      <a href="{{ route('home') }}" class="logo" aria-label="Yalla Nemshi home">
        <span class="nav__logo-mark">
          <img src="{{ asset('images/navbar-mark.png') }}" alt="" class="nav__logo-image">
        </span>
        <span class="nav__logo-wordmark">Yalla Nemshi</span>
      </a>
    </div>
    <div class="nav__menu__btn" id="menu-btn" aria-controls="nav-links" aria-expanded="false" aria-label="Open navigation menu">
      <i class="ri-menu-line"></i>
    </div>
  </div>

  <div class="nav__panel" id="nav-links">
    <ul class="nav__links nav__links--primary">
      <li>
        <a href="{{ route('home') }}" class="{{ $homeActive ? 'is-active' : '' }}" @if($homeActive) aria-current="page" @endif>HOME</a>
      </li>
      <li>
        <a href="{{ route('aboutUs') }}" class="{{ $aboutActive ? 'is-active' : '' }}" @if($aboutActive) aria-current="page" @endif>ABOUT</a>
      </li>
      <li>
        <a href="{{ route('chatbot') }}" class="nav__link--plan {{ $chatbotActive ? 'is-active' : '' }}" @if($chatbotActive) aria-current="page" @endif>
          <span class="nav__link-plan-star" aria-hidden="true"><i class="ri-sparkling-2-fill"></i></span>
          <span>PLAN</span>
        </a>
      </li>
      <li>
        <a href="{{ route('restaurants.index') }}" class="{{ $restaurantsActive ? 'is-active' : '' }}" @if($restaurantsActive) aria-current="page" @endif>RESTAURANTS</a>
      </li>
      <li>
        <a href="{{ route('hotels.index') }}" class="{{ $hotelsActive ? 'is-active' : '' }}" @if($hotelsActive) aria-current="page" @endif>HOTELS</a>
      </li>
      <li>
        <a href="{{ route('contactUs') }}" class="{{ $contactActive ? 'is-active' : '' }}" @if($contactActive) aria-current="page" @endif>CONTACT</a>
      </li>
    </ul>

    <div class="nav__panel-divider" aria-hidden="true"></div>

    <ul class="nav__links nav__links--account">
      @guest
        <li>
          <a href="{{ route('login') }}" class="{{ $loginActive ? 'is-active' : '' }}" @if($loginActive) aria-current="page" @endif>LOGIN</a>
        </li>
        <li>
          <a href="{{ route('register') }}" class="{{ $registerActive ? 'is-active' : '' }}" @if($registerActive) aria-current="page" @endif>REGISTER</a>
        </li>
      @else
        @php
          $displayName = trim((string) (Auth::user()->name ?? 'User'));
          $userInitial = strtoupper(substr($displayName !== '' ? $displayName : 'User', 0, 1));
          $isAdmin = (bool) Auth::user()->is_admin;
          $profilePicture = Auth::user()->profile_picture ? asset(Auth::user()->profile_picture) : null;
        @endphp
        <li class="nav__account-item">
          <div class="nav__account-menu">
            <button
              type="button"
              class="nav__account-trigger {{ ($profileActive || $adminActive) ? 'is-active' : '' }}"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <span class="nav__account-avatar">
                @if($profilePicture)
                  <img src="{{ $profilePicture }}" alt="{{ $displayName }}" class="nav__account-avatar-image">
                @else
                  {{ $userInitial }}
                @endif
              </span>
              <span class="nav__account-copy">
                <strong>{{ $displayName }}</strong>
                <small>{{ $isAdmin ? 'Admin' : 'User' }}</small>
              </span>
              <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
            </button>

            <div class="nav__account-dropdown">
              <div class="nav__account-dropdown-header">
                <div class="nav__account-dropdown-copy">
                  <strong>{{ $displayName }}</strong>
                  <small>{{ $isAdmin ? 'Admin' : 'User' }}</small>
                </div>
              </div>

              <div class="nav__account-dropdown-links">
                <a href="{{ route('profile') }}" class="nav__account-link {{ $profileActive ? 'is-active' : '' }}">
                  <i class="ri-user-line" aria-hidden="true"></i>
                  <span>Profile</span>
                </a>

                @if($isAdmin)
                  <a href="{{ route('admin.dashboard') }}" class="nav__account-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <i class="ri-layout-grid-line" aria-hidden="true"></i>
                    <span>Admin dashboard</span>
                  </a>
                @endif

                <a href="#" class="nav__account-link nav__account-link--logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                  <i class="ri-logout-box-r-line" aria-hidden="true"></i>
                  <span>Logout</span>
                </a>
              </div>

              <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
              </form>
            </div>
          </div>
        </li>
      @endguest
    </ul>
  </div>
</nav>
