<nav>
  <div class="nav__header">
    <div class="nav__logo">
      <a href="{{ route('home') }}" class="logo">Yalla Nemshi</a>
    </div>
    <div class="nav__menu__btn" id="menu-btn" aria-controls="nav-links" aria-expanded="false" aria-label="Open navigation menu">
      <i class="ri-menu-line"></i>
    </div>
  </div>
  <ul class="nav__links" id="nav-links">
    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">HOME</a></li>
    <li><a href="{{ route('aboutUs') }}" class="{{ request()->routeIs('aboutUs') ? 'is-active' : '' }}">ABOUT</a></li>
    
    {{-- Show 'DASHBOARD' link only to Admins --}}
    @auth
        @if(Auth::user()->is_admin == 1)
            <li><a href="/admin/dashboard" style="color: #ffcc00; font-weight: bold;">DASHBOARD</a></li>
        @endif
    @endauth

    <li><a href="{{ route('chatbot') }}" class="{{ request()->routeIs('chatbot*') ? 'is-active' : '' }}">PLAN</a></li>
    <li><a href="{{ route('restaurants') }}" class="{{ request()->routeIs('restaurants*') ? 'is-active' : '' }}">RESTAURANTS</a></li>
        <li><a href="{{ route('hotels') }}" class="{{ request()->routeIs('hotels*') ? 'is-active' : '' }}">HOTELS</a></li>
    <li><a href="{{ route('contactUs') }}" class="{{ request()->routeIs('contactUs') ? 'is-active' : '' }}">CONTACT</a></li>
    
    

    {{-- Toggle between LOGIN and LOGOUT based on status --}}
    @guest
        <li><a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'is-active' : '' }}">LOGIN</a></li>
        <li><a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'is-active' : '' }}">REGISTER</a></li>
    @else
        <li><a href="{{ route('profile') }}" class="{{ request()->routeIs('profile*') ? 'is-active' : '' }}">PROFILE</a></li>
        <li>
            <a href="#" class="nav-logout-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                LOGOUT
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    @endguest
  </ul>
</nav>
