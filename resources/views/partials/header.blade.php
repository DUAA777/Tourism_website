<header class="admin-header">
    <div class="header-left">
        <button class="mobile-toggle" id="mobileToggle">
            <i class="ri-menu-line"></i>
        </button>
        
        <div class="header-search">
            <i class="ri-search-line"></i>
            <input type="text" placeholder="Search anything..." id="globalSearch">
            <div class="search-results" id="searchResults" style="display: none;">
                <!-- Dynamic search results will appear here -->
            </div>
        </div>
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
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item" id="quickBulkAction">
                    <i class="ri-checkbox-multiple-line"></i>
                    <span>Bulk Actions</span>
                </a>
            </div>
        </div>
        
        <!-- Notifications Dropdown -->
        <div class="header-dropdown">
            <button class="dropdown-trigger" id="notificationsTrigger">
                <i class="ri-notification-3-line"></i>
                <span class="notification-badge" id="notificationBadge">3</span>
            </button>
            <div class="dropdown-menu dropdown-menu-large" id="notificationsMenu">
                <div class="dropdown-header">
                    <span>Notifications</span>
                    <a href="#" id="markAllRead">Mark all as read</a>
                </div>
                <div class="notifications-list">
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="ri-restaurant-line"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-title">New Restaurant Added</p>
                            <p class="notification-text">"The Gourmet Kitchen" was added by Admin</p>
                            <span class="notification-time">5 minutes ago</span>
                        </div>
                    </div>
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="ri-hotel-line"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-title">Hotel Booking Alert</p>
                            <p class="notification-text">10 new bookings for Ocean View Hotel</p>
                            <span class="notification-time">1 hour ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="ri-user-line"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-title">New User Registration</p>
                            <p class="notification-text">John Doe joined the platform</p>
                            <span class="notification-time">3 hours ago</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>
        
        <!-- User Menu Dropdown -->
        <div class="header-dropdown">
            <button class="dropdown-trigger user-trigger" id="userMenuTrigger">
                <div class="user-avatar">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="user-info">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">{{ auth()->user()->is_admin ? 'Administrator' : 'Staff' }}</span>
                </div>
                <i class="ri-arrow-down-s-line"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" id="userMenu">
                <div class="dropdown-user-info">
                    <div class="user-avatar-large">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-email">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>

                <a href="#" class="dropdown-item" id="changePasswordBtn">
                    <i class="ri-lock-password-line"></i>
                    <span>Change Password</span>
                </a>
                <a href="#" class="dropdown-item" id="activityLogBtn">
                    <i class="ri-history-line"></i>
                    <span>Activity Log</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item" id="themeToggle">
                    <i class="ri-moon-line"></i>
                    <span>Dark Mode</span>
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
    
    // Global Search
    const globalSearch = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    
    if (globalSearch) {
        globalSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!globalSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
    
    function performSearch(query) {
        fetch(`/admin/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.results && data.results.length > 0) {
                    searchResults.innerHTML = data.results.map(result => `
                        <a href="${result.url}" class="search-result-item">
                            <i class="${result.icon}"></i>
                            <div>
                                <div class="result-title">${result.title}</div>
                                <div class="result-subtitle">${result.subtitle}</div>
                            </div>
                        </a>
                    `).join('');
                    searchResults.style.display = 'block';
                } else {
                    searchResults.innerHTML = '<div class="search-result-empty">No results found</div>';
                    searchResults.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
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
    
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            
            const icon = this.querySelector('i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('ri-moon-line');
                icon.classList.add('ri-sun-line');
                this.querySelector('span').textContent = 'Light Mode';
            } else {
                icon.classList.remove('ri-sun-line');
                icon.classList.add('ri-moon-line');
                this.querySelector('span').textContent = 'Dark Mode';
            }
        });
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.querySelector('i').classList.remove('ri-moon-line');
            themeToggle.querySelector('i').classList.add('ri-sun-line');
            themeToggle.querySelector('span').textContent = 'Light Mode';
        }
    }
    
    // Change Password Modal
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const modal = document.getElementById('changePasswordModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCancel = document.querySelector('.modal-cancel');
    
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        });
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    if (modalCancel) {
        modalCancel.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Mark all notifications as read
    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) {
        markAllRead.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            document.getElementById('notificationBadge').style.display = 'none';
        });
    }
    
    // Quick Bulk Action
    const quickBulkAction = document.getElementById('quickBulkAction');
    if (quickBulkAction) {
        quickBulkAction.addEventListener('click', function(e) {
            e.preventDefault();
            // Check if there are any checkboxes on the page
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            if (checkboxes.length > 0) {
                const firstCheckbox = checkboxes[0];
                if (firstCheckbox.closest('.restaurant-card') || firstCheckbox.closest('.hotel-card')) {
                    // Trigger bulk select mode
                    const bulkActions = document.querySelector('.bulk-actions');
                    if (bulkActions) {
                        bulkActions.style.display = 'block';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }
        });
    }
});
</script>
@endpush