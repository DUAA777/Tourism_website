@extends('layout.admin')

@section('title', 'Restaurants Management')

@section('content')
<div class="management-section">
    <div class="section-header">
        <h1>Restaurants Management</h1>
        <div class="header-actions">

            <a href="{{ route('admin.restaurants.create') }}" class="btn-primary">
                <i class="ri-add-line"></i> Add Restaurant
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by name, location, or cuisine..." 
                       value="{{ request('search') }}" class="filter-input">
            </div>
            
            <div class="filter-group">
                <select name="food_type" class="filter-select">
                    <option value="">All Cuisines</option>
                    @foreach($foodTypes as $type)
                        <option value="{{ $type }}" {{ request('food_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="filter-group">
                <select name="price_tier" class="filter-select">
                    <option value="">All Price Tiers</option>
                    @foreach($priceTiers as $tier)
                        <option value="{{ $tier }}" {{ request('price_tier') == $tier ? 'selected' : '' }}>
                            {{ $tier }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="filter-group">
                <select name="min_rating" class="filter-select">
                    <option value="">Any Rating</option>
                    <option value="4" {{ request('min_rating') == '4' ? 'selected' : '' }}>4+ Stars</option>
                    <option value="3" {{ request('min_rating') == '3' ? 'selected' : '' }}>3+ Stars</option>
                    <option value="2" {{ request('min_rating') == '2' ? 'selected' : '' }}>2+ Stars</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="sort" class="filter-select">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest First</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                    <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Highest Rated</option>
                    <option value="rating_low" {{ request('sort') == 'rating_low' ? 'selected' : '' }}>Lowest Rated</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('admin.restaurants.index') }}" class="btn-secondary">Reset</a>
        </form>
    </div>
    
    <!-- Bulk Actions -->
    <div class="bulk-actions" style="display: none;">
        <button class="btn-danger bulk-delete-btn">
            <i class="ri-delete-bin-line"></i> Delete Selected
        </button>
    </div>
    
    <!-- Restaurants Grid -->
    <div class="restaurants-grid">
        @forelse($restaurants as $restaurant)
        <div class="restaurant-card" data-id="{{ $restaurant->id }}">
            <div class="card-checkbox">
                <input type="checkbox" class="restaurant-select" value="{{ $restaurant->id }}">
            </div>
            
            <div class="card-image">
                <img src="{{ $restaurant->image ?? '/images/placeholder-restaurant.jpg' }}" alt="{{ $restaurant->restaurant_name }}">
                <div class="card-rating">
                    <i class="ri-star-fill"></i> {{ $restaurant->rating ?? 'N/A' }}
                </div>
            </div>
            
            <div class="card-content">
                <h3>{{ $restaurant->restaurant_name }}</h3>
                <div class="card-location">
                    <i class="ri-map-pin-line"></i> {{ $restaurant->location }}
                </div>
                <div class="card-meta">
                    <span class="meta-badge">{{ $restaurant->food_type ?? 'Various' }}</span>
                    <span class="price-tier">{{ $restaurant->price_tier ?? '$$' }}</span>
                </div>
                <div class="card-description">
                    {{ Str::limit($restaurant->description, 100) }}
                </div>
                <div class="card-actions">
                    <a href="{{ route('admin.restaurants.show', $restaurant) }}" class="btn-icon" title="View">
                        <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('admin.restaurants.edit', $restaurant) }}" class="btn-icon" title="Edit">
                        <i class="ri-edit-line"></i>
                    </a>
                    <form action="{{ route('admin.restaurants.destroy', $restaurant) }}" method="POST" class="inline-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon btn-delete" title="Delete" 
                                onclick="return confirm('Are you sure you want to delete this restaurant?')">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="ri-restaurant-line"></i>
            <p>No restaurants found</p>
            <a href="{{ route('admin.restaurants.create') }}" class="btn-primary">Add Your First Restaurant</a>
        </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    <div class="pagination-container">
        {{ $restaurants->links() }}
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk delete functionality
    const checkboxes = document.querySelectorAll('.restaurant-select');
    const bulkActions = document.querySelector('.bulk-actions');
    const bulkDeleteBtn = document.querySelector('.bulk-delete-btn');
    
    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.restaurant-select:checked').length;
        if (checkedCount > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.restaurant-select:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) return;
            
            if (confirm(`Are you sure you want to delete ${selectedIds.length} restaurant(s)?`)) {
                fetch('{{ route("admin.restaurants.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: selectedIds })
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload();
                      }
                  });
            }
        });
    }
});
</script>
@endpush
@endsection