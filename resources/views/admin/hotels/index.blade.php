@extends('layout.admin')

@section('title', 'Hotels Management')

@section('content')
<div class="management-section">
    <div class="section-header">
        <h1>Hotels Management</h1>
        <div class="header-actions">
            <div class="view-toggle">
                <a href="{{ route('admin.hotels.index', ['trashed' => 'false']) }}" 
                   class="{{ !request('trashed') ? 'active' : '' }}">Active</a>
                <a href="{{ route('admin.hotels.index', ['trashed' => 'true']) }}" 
                   class="{{ request('trashed') == 'true' ? 'active' : '' }}">Trash</a>
            </div>
            <a href="{{ route('admin.hotels.create') }}" class="btn-primary">
                <i class="ri-add-line"></i> Add Hotel
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" class="filters-form">
            <input type="hidden" name="trashed" value="{{ request('trashed') }}">
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search hotels..." 
                       value="{{ request('search') }}" class="filter-input">
            </div>
            
            <div class="filter-group">
                <select name="min_rating" class="filter-select">
                    <option value="">All Ratings</option>
                    <option value="4" {{ request('min_rating') == '4' ? 'selected' : '' }}>4+ Stars</option>
                    <option value="3" {{ request('min_rating') == '3' ? 'selected' : '' }}>3+ Stars</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="room_type" class="filter-select">
                    <option value="">All Room Types</option>
                    @foreach($roomTypes as $type)
                        <option value="{{ $type }}" {{ request('room_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="filter-group">
                <select name="sort" class="filter-select">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest First</option>
                    <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Highest Rated</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                </select>
            </div>
            
            <button type="submit" class="btn-primary">Apply Filters</button>
            <a href="{{ route('admin.hotels.index') }}" class="btn-secondary">Reset</a>
        </form>
    </div>
    
    <!-- Hotels Grid -->
    <div class="hotels-grid">
        @forelse($hotels as $hotel)
        <div class="hotel-card">
            <div class="card-image">
<img 
    src="{{ 
        filter_var($hotel->hotel_image, FILTER_VALIDATE_URL) 
            ? $hotel->hotel_image 
            : asset($hotel->hotel_image) 
    }}" 
    alt="{{ $hotel->hotel_name }}"
    onerror="this.src='{{ asset('images/placeholder-hotel.jpg') }}';"
>
                <div class="card-rating">
                    <i class="ri-star-fill"></i> {{ $hotel->rating_score ?? 'N/A' }}
                    @if($hotel->review_count)
                        <span>({{ $hotel->review_count }} reviews)</span>
                    @endif
                </div>
            </div>
            
            <div class="card-content">
                <h3>{{ $hotel->hotel_name }}</h3>
                <div class="card-location">
                    <i class="ri-map-pin-line"></i> {{ $hotel->address ?? 'Address not specified' }}
                </div>
                
                @if($hotel->price_per_night)
                <div class="card-price">
                    <strong>{{ $hotel->price_per_night }}</strong>
                    @if($hotel->taxes_fees)
                        <small>+ {{ $hotel->taxes_fees }} taxes</small>
                    @endif
                </div>
                @endif
                
                <div class="card-amenities">
                    @if($hotel->distance_from_beach)
                        <span class="amenity">🏖️ {{ $hotel->distance_from_beach }}</span>
                    @endif
                    @if($hotel->distance_from_center)
                        <span class="amenity">🏙️ {{ $hotel->distance_from_center }}</span>
                    @endif
                </div>
                
                <div class="card-actions">
                    <a href="{{ route('admin.hotels.show', $hotel) }}" class="btn-icon" title="View">
                        <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('admin.hotels.edit', $hotel) }}" class="btn-icon" title="Edit">
                        <i class="ri-edit-line"></i>
                    </a>
                    
                    @if($hotel->trashed())
                        <form action="{{ route('admin.hotels.restore', $hotel->id) }}" method="POST" class="inline-form">
                            @csrf
                            <button type="submit" class="btn-icon btn-restore" title="Restore">
                                <i class="ri-restart-line"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.hotels.force-delete', $hotel->id) }}" method="POST" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete" title="Permanently Delete" 
                                    onclick="return confirm('This action cannot be undone. Are you sure?')">
                                <i class="ri-delete-bin-7-line"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" class="inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete" title="Move to Trash"
                                    onclick="return confirm('Move this hotel to trash?')">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="ri-hotel-line"></i>
            <p>No hotels found</p>
            <a href="{{ route('admin.hotels.create') }}" class="btn-primary">Add Your First Hotel</a>
        </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    <div class="pagination-container">
        {{ $hotels->links() }}
    </div>
</div>
@endsection