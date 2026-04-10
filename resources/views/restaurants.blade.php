@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
<style>
    :root {
        --bg-color: #f8f9fc;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --border-color: #e2e8f0;
        --border-light: #f1f5f9;
        --primary: #ea580c;
        --primary-dark: #c2410c;
        --primary-light: #fff7ed;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --transition: all 0.2s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        color: var(--text-primary);
        line-height: 1.5;
    }

    /* Main Layout */
    .resto-layout {
        max-width: 1440px;
        margin: 0 auto;
        padding: 32px 40px;
        display: flex;
        gap: 32px;
        align-items: flex-start;
    }

    /* Sidebar Filters */
    .filters-panel {
        width: 300px;
        flex-shrink: 0;
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        position: sticky;
        top: 24px;
    }

    .panel-header {
        padding: 20px 20px 16px;
        border-bottom: 1px solid var(--border-light);
    }

    .panel-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .panel-header p {
        font-size: 13px;
        color: var(--text-secondary);
    }

    .filter-form {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text-primary);
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background-color: var(--card-bg);
        font-size: 14px;
        color: var(--text-primary);
        transition: var(--transition);
        font-family: inherit;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
    }

    .price-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .price-btn {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-secondary);
    }

    .price-btn:hover {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .price-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .rating-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .rating-btn {
        padding: 8px 14px;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        color: var(--text-secondary);
    }

    .rating-btn:hover {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .rating-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        margin-top: 8px;
    }

    .btn-apply {
        flex: 2;
        background: var(--primary);
        color: white;
        padding: 10px 16px;
        border: none;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-apply:hover {
        background: var(--primary-dark);
    }

    .btn-reset {
        flex: 1;
        background: white;
        color: var(--text-secondary);
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-reset:hover {
        background: var(--bg-color);
        border-color: var(--text-muted);
    }

    /* Results Section */
    .results-panel {
        flex: 1;
        min-width: 0;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .results-title h2 {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .results-title span {
        font-size: 14px;
        color: var(--text-secondary);
        margin-left: 8px;
        font-weight: normal;
    }

    .results-controls {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sort-select {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-size: 13px;
        background: white;
        cursor: pointer;
        color: var(--text-primary);
    }

    .sort-select:focus {
        outline: none;
        border-color: var(--primary);
    }
    .pagination-jump{
        display: none;
        align-items: center;
        gap: 8px;
    }
    .pagination-jump input {
        width: 60px;
        padding: 6px 10px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-size: 13px;
        color: var(--text-primary);
    }


    /* Restaurant Grid */
    .restaurant-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    .restaurant-card {
        background: var(--card-bg);
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: var(--transition);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .restaurant-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        border-color: transparent;
    }

    .card-image {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: var(--bg-color);
    }

    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .restaurant-card:hover .card-image img {
        transform: scale(1.03);
    }

    .card-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(4px);
        color: white;
    }

    .card-badge.top-rated {
        background: #f59e0b;
        color: #1a1a2e;
    }

    .card-content {
        padding: 16px;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
        gap: 12px;
    }

    .restaurant-name {
        font-size: 17px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.4;
    }

    .rating {
        font-size: 13px;
        font-weight: 600;
        background: var(--primary-light);
        padding: 4px 8px;
        border-radius: 6px;
        color: var(--primary);
        white-space: nowrap;
    }

    .restaurant-meta {
        font-size: 13px;
        color: var(--text-secondary);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .price-tag {
        display: inline-block;
        padding: 2px 8px;
        background: var(--bg-color);
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 16px;
    }

    .tag {
        padding: 4px 10px;
        background: var(--bg-color);
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .tag.cuisine {
        background: var(--primary-light);
        color: var(--primary);
    }

    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid var(--border-light);
        font-size: 12px;
    }

    .details-link {
        color: var(--primary);
        font-weight: 500;
    }

    .phone {
        color: var(--text-secondary);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .empty-message {
        font-size: 14px;
        color: var(--text-secondary);
    }

    /* Loading */
    .loading-container {
        text-align: center;
        padding: 60px 20px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border-color);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Pagination */
    .pagination-wrapper {
        margin-top: 40px;
        display: flex;
        justify-content: center;
    }

    .pagination {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        list-style: none;
    }

    .page-item {
        display: inline-block;
    }

    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border-radius: var(--radius-sm);
        background: white;
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 500;
        transition: var(--transition);
        text-decoration: none;
    }

    .page-link:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    .active .page-link {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .disabled .page-link {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .resto-layout {
            flex-direction: column;
            padding: 24px;
        }
        .filters-panel {
            width: 100%;
            position: relative;
            top: 0;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .filter-actions {
            grid-column: span 2;
        }
    }

    @media (max-width: 640px) {
        .resto-layout {
            padding: 16px;
        }
        .filter-form {
            grid-template-columns: 1fr;
        }
        .filter-actions {
            grid-column: span 1;
        }
        .restaurant-grid {
            grid-template-columns: 1fr;
        }
        .results-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')

<div class="resto-layout">
    <!-- Filter Sidebar -->
    <aside class="filters-panel">
        <div class="panel-header">
            <h3>Find Restaurants</h3>
        </div>
        
        <form id="filterForm" class="filter-form" method="GET" action="{{ route('restaurants.index') }}">
            <!-- Cuisine -->
            <div class="filter-group">
                <label>Cuisine</label>
                <select name="food_type" id="food_type">
                    <option value="">All cuisines</option>
                    @foreach($uniqueCuisines as $cuisine)
                        <option value="{{ $cuisine }}" {{ request('food_type') == $cuisine ? 'selected' : '' }}>{{ ucfirst($cuisine) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Location -->
            <div class="filter-group">
                <label>Location</label>
                <select name="location" id="location">
                    <option value="">All locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc }}" {{ request('location') == $loc ? 'selected' : '' }}>{{ ucfirst($loc) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Price Range -->
            <div class="filter-group">
                <label>Price range</label>
                <div class="price-buttons" id="priceGroup">
                    <div class="price-btn {{ !request('price_tier') ? 'active' : '' }}" data-price="">Any</div>
                    <div class="price-btn {{ request('price_tier') == 'budget' ? 'active' : '' }}" data-price="budget">Budget</div>
                    <div class="price-btn {{ request('price_tier') == 'mid-range' ? 'active' : '' }}" data-price="mid-range">Mid</div>
                    <div class="price-btn {{ request('price_tier') == 'premium' ? 'active' : '' }}" data-price="premium">Premium</div>
                </div>
                <input type="hidden" name="price_tier" id="price_tier" value="{{ request('price_tier') }}">
            </div>

            <!-- Rating -->
            <div class="filter-group">
                <label>Minimum rating</label>
                <div class="rating-buttons" id="ratingGroup">
                    <div class="rating-btn {{ !request('min_rating') ? 'active' : '' }}" data-rating="">Any</div>
                    <div class="rating-btn {{ request('min_rating') == '4.5' ? 'active' : '' }}" data-rating="4.5">4.5+</div>
                    <div class="rating-btn {{ request('min_rating') == '4.0' ? 'active' : '' }}" data-rating="4.0">4.0+</div>
                    <div class="rating-btn {{ request('min_rating') == '3.5' ? 'active' : '' }}" data-rating="3.5">3.5+</div>
                    <div class="rating-btn {{ request('min_rating') == '3.0' ? 'active' : '' }}" data-rating="3.0">3.0+</div>
                </div>
                <input type="hidden" name="min_rating" id="min_rating" value="{{ request('min_rating') }}">
            </div>

            <!-- Dining Setting -->
            <div class="filter-group">
                <label>Setting</label>
                <select name="restaurant_type" id="restaurant_type">
                    <option value="">Any setting</option>
                    @foreach($restaurantTypes as $type)
                        <option value="{{ $type }}" {{ request('restaurant_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Atmosphere -->
            <div class="filter-group">
                <label>Atmosphere</label>
                <select name="tags" id="tags">
                    <option value="">All atmospheres</option>
                    @foreach($atmospheres as $atm)
                        <option value="{{ $atm }}" {{ request('tags') == $atm ? 'selected' : '' }}>{{ ucfirst($atm) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Keyword -->
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="keyword" id="keyword" placeholder="Name, cuisine, feature..." value="{{ request('keyword') }}">
            </div>

            <!-- Actions -->
            <div class="filter-actions">
                <button type="submit" class="btn-apply">Apply filters</button>
                <button type="button" id="resetFiltersBtn" class="btn-reset">Reset</button>
            </div>
        </form>
    </aside>

    <!-- Results Section -->
    <main class="results-panel">
        <div class="results-header">
            <div class="results-title">
                <h2>Restaurants <span>{{ $restaurants->total() }} found</span></h2>
            </div>

        </div>

        <div class="restaurant-grid">
            @forelse($restaurants as $restaurant)
                <div class="restaurant-card" data-restaurant-id="{{ $restaurant->id }}">
                    <div class="card-image">

                        <img src="{{ $restaurant->image ?? 'https://placehold.co/400x240?text=Restaurant' }}" 
                             alt="{{ $restaurant->restaurant_name }}"
                             onerror="this.src='https://placehold.co/400x240?text=No+Image'">
                    </div>
                    <div class="card-content">
                        <div class="card-header">
                            <h3 class="restaurant-name">{{ $restaurant->restaurant_name }}</h3>
                            <span class="rating">{{ number_format($restaurant->rating, 1) }} ★</span>
                        </div>
                        <div class="restaurant-meta">
                            <span>{{ $restaurant->location ?? 'Location n/a' }}</span>
                            @if($restaurant->price_tier)
                                <span class="price-tag">
                                    {{ $restaurant->price_tier === 'budget' ? 'Budget' : ($restaurant->price_tier === 'mid-range' ? 'Mid-range' : 'Premium') }}
                                </span>
                            @endif
                        </div>
                        <div class="tags">
                            @php $tags = $restaurant->tags ? explode(',', $restaurant->tags) : []; @endphp
                            @foreach(array_slice($tags, 0, 3) as $tag)
                                <span class="tag">{{ trim($tag) }}</span>
                            @endforeach
                            @if($restaurant->food_type)
                                @php
                                    $foodTypes = array_map('trim', explode(',', $restaurant->food_type));
                                @endphp
                                @foreach(array_slice($foodTypes, 0, 2) as $foodType)
                                    <span class="tag cuisine">{{ ucfirst($foodType) }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="card-footer">
                            <span class="details-link">View details →</span>
                            @if($restaurant->phone_number)
                                <span class="phone">{{ $restaurant->phone_number }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">🍽️</div>
                    <div class="empty-title">No restaurants found</div>
                    <div class="empty-message">Try adjusting your filters to see more results.</div>
                </div>
            @endforelse
        </div>

        @if($restaurants->hasPages())
            <div class="pagination-wrapper">
                {{ $restaurants->appends(request()->query())->links() }}
            </div>
        @endif
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '{{ url("/restaurants") }}';
    const filterForm = document.getElementById('filterForm');
    const resetBtn = document.getElementById('resetFiltersBtn');
    const sortSelect = document.getElementById('sortSelect');
    
    // Price selection
    const priceBtns = document.querySelectorAll('.price-btn');
    const priceInput = document.getElementById('price_tier');
    
    priceBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const price = this.dataset.price;
            priceInput.value = price || '';
            priceBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Rating selection
    const ratingBtns = document.querySelectorAll('.rating-btn');
    const ratingInput = document.getElementById('min_rating');
    
    ratingBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const rating = this.dataset.rating;
            ratingInput.value = rating || '';
            ratingBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Sort change
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });
    }
    
    // Reset filters
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = baseUrl;
        });
    }
    
    // Card click navigation
    document.querySelectorAll('.restaurant-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const restaurantId = this.dataset.restaurantId;
            if (restaurantId) {
                window.location.href = `${baseUrl}/${restaurantId}`;
            }
        });
    });
});
</script>


@endsection