<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-mark">YN</div>
        <div>
            <h2>Yalla Nemshi</h2>
            <p>Admin workspace</p>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="ri-user-line"></i>
            <span>Users</span>
        </a>
        <a href="{{ route('admin.restaurants.index') }}" class="nav-item {{ request()->routeIs('admin.restaurants.*') ? 'active' : '' }}">
            <i class="ri-restaurant-line"></i>
            <span>Restaurants</span>
        </a>
        <a href="{{ route('admin.hotels.index') }}" class="nav-item {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
            <i class="ri-hotel-line"></i>
            <span>Hotels</span>
        </a>
    </nav>
    <a href="{{ route('home') }}" class="sidebar-return">
        <span class="sidebar-return-icon">
            <i class="ri-arrow-left-line"></i>
        </span>
        <span>
            <strong>Return</strong>
            <small>Back to website</small>
        </span>
    </a>
</aside>
