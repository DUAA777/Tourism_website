<nav>
  <div class="nav__header">
    <div class="nav__logo">
      <a href="{{ route('home') }}" class="logo">Yalla Nemshi</a>
    </div>
    <div class="nav__menu__btn" id="menu-btn">
      <i class="ri-menu-line"></i>
    </div>
  </div>
  <ul class="nav__links" id="nav-links">
    <li><a href="{{ route('home') }}">HOME</a></li>
    <li><a href="{{ route('aboutUs') }}">ABOUT</a></li>
    
    {{-- Show 'DASHBOARD' link only to Admins --}}
    @auth
        @if(Auth::user()->is_admin == 1)
            <li><a href="/admin/" style="color: #ffcc00; font-weight: bold;">DASHBOARD</a></li>
        @endif
    @endauth

    <li><a href="{{ route('chatbot') }}">PLAN</a></li>
    <li><a href="{{ route('restaurants') }}">RESTAURANTS</a></li>
        <li><a href="{{ route('hotels') }}">HOTELS</a></li>
    <li><a href="{{ route('contactUs') }}">CONTACT</a></li>
    
    

    {{-- Toggle between LOGIN and LOGOUT based on status --}}
    @guest
        <li><a href="{{ route('login') }}">LOGIN</a></li>
        <li><a href="{{ route('register') }}">REGISTER</a></li>
    @else
        <li>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                LOGOUT ({{ Auth::user()->name }})
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </li>
    @endguest
  </ul>
</nav>
