<style>
  .nav__links a.active {
    color: #065089;
    font-weight: bold;
  }
</style>

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
    <li>
      <a href="{{ route('home') }}"
         class="{{ request()->routeIs('home') ? 'active' : '' }}">
         HOME
      </a>
    </li>

    <li>
      <a href="{{ route('aboutUs') }}"
         class="{{ request()->routeIs('aboutUs') ? 'active' : '' }}">
         ABOUT
      </a>
    </li>

    @auth
      @if(Auth::user()->is_admin == 1)
        <li>
          <a href="/admin/dashboard"
             class="{{ request()->is('admin/dashboard*') ? 'active' : '' }}"
             style="color: #ffcc00; font-weight: bold;">
             DASHBOARD
          </a>
        </li>
      @endif
    @endauth

    <li>
      <a href="{{ route('chatbot') }}"
         class="{{ request()->routeIs('chatbot') ? 'active' : '' }}">
         PLAN
      </a>
    </li>

    <li>
      <a href="{{ route('restaurants') }}"
         class="{{ request()->routeIs('restaurants') ? 'active' : '' }}">
         RESTAURANTS
      </a>
    </li>

    <li>
      <a href="{{ route('hotels') }}"
         class="{{ request()->routeIs('hotels') ? 'active' : '' }}">
         HOTELS
      </a>
    </li>

    <li>
      <a href="{{ route('contactUs') }}"
         class="{{ request()->routeIs('contactUs') ? 'active' : '' }}">
         CONTACT
      </a>
    </li>

    @guest
      <li>
        <a href="{{ route('login') }}"
           class="{{ request()->routeIs('login') ? 'active' : '' }}">
           LOGIN
        </a>
      </li>

      <li>
        <a href="{{ route('register') }}"
           class="{{ request()->routeIs('register') ? 'active' : '' }}">
           REGISTER
        </a>
      </li>
    @else
      <li>
        <a href="#"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
           LOGOUT ({{ Auth::user()->name }})
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          @csrf
        </form>
      </li>
    @endguest
  </ul>
</nav>
