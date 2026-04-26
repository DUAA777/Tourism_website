@php
    $adminUser = auth()->user();
    $adminName = trim((string) ($adminUser->name ?? 'User'));
    $adminName = $adminName !== '' ? $adminName : 'User';
    $adminInitial = strtoupper(substr($adminName, 0, 1));
    $adminProfilePicturePath = $adminUser->profile_picture ? trim((string) $adminUser->profile_picture) : null;
    $adminProfilePicture = $adminProfilePicturePath
        ? (filter_var($adminProfilePicturePath, FILTER_VALIDATE_URL)
            ? $adminProfilePicturePath
            : asset(ltrim($adminProfilePicturePath, '/')))
        : null;
@endphp

<header class="admin-header">
    <div class="header-left">
        <button class="mobile-toggle" id="mobileToggle">
            <i class="ri-menu-line"></i>
        </button>
    </div>
    
    <div class="header-right">
        <!-- Quick Actions Dropdown -->
        <div class="header-dropdown">
            <button class="dropdown-trigger" id="quickActionsTrigger">
                <i class="ri-add-line"></i>
            </button>
            <div class="dropdown-menu" id="quickActionsMenu">
                <div class="dropdown-header">Quick Actions</div>
                <a href="{{ route('admin.restaurants.create') }}" class="dropdown-item">
                    <i class="ri-restaurant-line"></i>
                    <span>Add Restaurant</span>
                </a>
                <a href="{{ route('admin.hotels.create') }}" class="dropdown-item">
                    <i class="ri-hotel-line"></i>
                    <span>Add Hotel</span>
                </a>
                <a href="{{ route('admin.users.create') }}" class="dropdown-item">
                    <i class="ri-user-add-line"></i>
                    <span>Add User</span>
                </a>
            </div>
        </div>
        
        <!-- User Menu Dropdown -->
        <div class="header-dropdown">
            <button class="dropdown-trigger user-trigger" id="userMenuTrigger">
                <div class="user-avatar {{ $adminProfilePicture ? 'has-image' : '' }}">
                    @if($adminProfilePicture)
                        <img src="{{ $adminProfilePicture }}" alt="{{ $adminName }}">
                    @else
                        {{ $adminInitial }}
                    @endif
                </div>
                <div class="user-info">
                    <span class="user-name">{{ $adminName }}</span>
                    <span class="user-role">{{ $adminUser->is_admin ? 'Administrator' : 'Staff' }}</span>
                </div>
                <i class="ri-arrow-down-s-line"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" id="userMenu">
                <div class="dropdown-user-info">
                    <div class="user-avatar-large {{ $adminProfilePicture ? 'has-image' : '' }}">
                        @if($adminProfilePicture)
                            <img src="{{ $adminProfilePicture }}" alt="{{ $adminName }}">
                        @else
                            {{ $adminInitial }}
                        @endif
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $adminName }}</div>
                        <div class="user-email">{{ $adminUser->email }}</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>

                <a href="{{ route('profile') }}" class="dropdown-item">
                    <i class="ri-lock-password-line"></i>
                    <span>Change Password</span>
                </a>
                <div class="dropdown-divider"></div>
                <form action="{{ route('admin.logout') }}" method="POST" id="logoutForm">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="ri-logout-box-r-line"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>



@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const mainContent = document.querySelector('.admin-main');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            mainContent.classList.toggle('sidebar-open');
        });
    }
    
    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.header-dropdown');
    
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (trigger && menu) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.querySelector('.dropdown-menu')?.classList.remove('show');
                    }
                });
                menu.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
});
</script>
@endpush
