@extends('layout.app')

@push('styles')
<style>
    :root {
        --hotel-bg: #f8f9fc;
        --hotel-card-bg: #ffffff;
        --hotel-text-dark: #1e293b;
        --hotel-text-soft: #64748b;
        --hotel-text-muted: #94a3b8;
        --hotel-border: #e2e8f0;
        --hotel-border-light: #f1f5f9;
        --hotel-primary: #ea580c;
        --hotel-primary-dark: #c2410c;
        --hotel-primary-light: #fff7ed;
        --hotel-success: #10b981;
        --hotel-warning: #f59e0b;
        --hotel-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --hotel-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        --hotel-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --hotel-radius-sm: 8px;
        --hotel-radius-md: 12px;
        --hotel-radius-lg: 16px;
        --hotel-transition: all 0.2s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background-color: var(--hotel-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        color: var(--hotel-text-dark);
        line-height: 1.5;
    }

    /* Main Layout */
    .hotel-layout {
        max-width: 1440px;
        margin: 0 auto;
        padding: 32px 40px;
        display: flex;
        gap: 32px;
        align-items: flex-start;
    }

    /* Filter Sidebar */
    .filters-panel {
        width: 320px;
        flex-shrink: 0;
        background: var(--hotel-card-bg);
        border-radius: var(--hotel-radius-lg);
        box-shadow: var(--hotel-shadow-sm);
        border: 1px solid var(--hotel-border);
        position: sticky;
        top: 24px;
    }

    .panel-header {
        padding: 20px 20px 16px;
        border-bottom: 1px solid var(--hotel-border-light);
    }

    .panel-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--hotel-text-dark);
        margin-bottom: 4px;
    }

    .panel-header p {
        font-size: 13px;
        color: var(--hotel-text-soft);
    }

    .filter-form {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-height: calc(100vh - 140px);
        overflow-y: auto;
    }

    .filter-form::-webkit-scrollbar {
        width: 4px;
    }

    .filter-form::-webkit-scrollbar-track {
        background: var(--hotel-border-light);
        border-radius: 4px;
    }

    .filter-form::-webkit-scrollbar-thumb {
        background: var(--hotel-text-muted);
        border-radius: 4px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 500;
        color: var(--hotel-text-dark);
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--hotel-border);
        border-radius: var(--hotel-radius-sm);
        background-color: var(--hotel-card-bg);
        font-size: 14px;
        color: var(--hotel-text-dark);
        transition: var(--hotel-transition);
        font-family: inherit;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: var(--hotel-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .price-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .price-btn {
        flex: 1;
        text-align: center;
        padding: 8px 8px;
        background: var(--hotel-bg);
        border: 1px solid var(--hotel-border);
        border-radius: var(--hotel-radius-sm);
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--hotel-transition);
        color: var(--hotel-text-soft);
    }

    .price-btn:hover {
        border-color: var(--hotel-primary);
        background: var(--hotel-primary-light);
    }

    .price-btn.active {
        background: var(--hotel-primary);
        border-color: var(--hotel-primary);
        color: white;
    }

    .rating-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .rating-btn {
        padding: 8px 14px;
        background: var(--hotel-bg);
        border: 1px solid var(--hotel-border);
        border-radius: var(--hotel-radius-sm);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--hotel-transition);
        color: var(--hotel-text-soft);
    }

    .rating-btn:hover {
        border-color: var(--hotel-primary);
        background: var(--hotel-primary-light);
    }

    .rating-btn.active {
        background: var(--hotel-primary);
        border-color: var(--hotel-primary);
        color: white;
    }

    .filter-actions {
        display: flex;
        gap: 12px;
        margin-top: 8px;
    }

    .btn-apply {
        flex: 2;
        background: var(--hotel-primary);
        color: white;
        padding: 10px 16px;
        border: none;
        border-radius: var(--hotel-radius-sm);
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: var(--hotel-transition);
    }

    .btn-apply:hover {
        background: var(--hotel-primary-dark);
    }

    .btn-reset {
        flex: 1;
        background: white;
        color: var(--hotel-text-soft);
        padding: 10px 12px;
        border: 1px solid var(--hotel-border);
        border-radius: var(--hotel-radius-sm);
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: var(--hotel-transition);
    }

    .btn-reset:hover {
        background: var(--hotel-bg);
        border-color: var(--hotel-text-muted);
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
        color: var(--hotel-text-dark);
    }

    .results-title span {
        font-size: 14px;
        color: var(--hotel-text-soft);
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
        border: 1px solid var(--hotel-border);
        border-radius: var(--hotel-radius-sm);
        font-size: 13px;
        background: white;
        cursor: pointer;
        color: var(--hotel-text-dark);
    }

    .sort-select:focus {
        outline: none;
        border-color: var(--hotel-primary);
    }

    /* Hotel Grid */
    .hotel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
    }

    .hotel-card {
        background: var(--hotel-card-bg);
        border-radius: var(--hotel-radius-md);
        border: 1px solid var(--hotel-border);
        overflow: hidden;
        transition: var(--hotel-transition);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .hotel-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--hotel-shadow-lg);
        border-color: transparent;
    }

    .card-image {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: var(--hotel-bg);
    }

    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .hotel-card:hover .card-image img {
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

    .card-badge.luxury {
        background: #8b5cf6;
    }

    .card-badge.premium {
        background: #ef4444;
    }

    .card-content {
        padding: 16px;
    }

    .hotel-name {
        font-size: 17px;
        font-weight: 600;
        color: var(--hotel-text-dark);
        margin-bottom: 6px;
        line-height: 1.4;
    }

    .hotel-address {
        font-size: 13px;
        color: var(--hotel-text-soft);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .rating-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--hotel-primary-light);
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        color: var(--hotel-primary);
        margin-bottom: 12px;
    }

    .hotel-features {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .feature-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        background: var(--hotel-bg);
        border-radius: 6px;
        font-size: 11px;
        color: var(--hotel-text-soft);
        font-weight: 500;
    }

    .hotel-description {
        color: var(--hotel-text-soft);
        font-size: 12px;
        margin: 10px 0;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .price-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--hotel-border-light);
    }

    .price {
        font-size: 20px;
        font-weight: 700;
        color: var(--hotel-primary);
    }

    .price small {
        font-size: 12px;
        font-weight: 500;
        color: var(--hotel-text-soft);
    }
    .pagination-jump{
        display: none;
        align-items: center;
        gap: 8px;
    }
    .details-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--hotel-primary);
        font-weight: 500;
        font-size: 12px;
        transition: var(--hotel-transition);
    }

    .hotel-card:hover .details-link {
        gap: 10px;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--hotel-card-bg);
        border-radius: var(--hotel-radius-lg);
        border: 1px solid var(--hotel-border);
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--hotel-text-dark);
        margin-bottom: 8px;
    }

    .empty-message {
        font-size: 14px;
        color: var(--hotel-text-soft);
    }

    /* Loading Skeleton */
    .skeleton-card {
        background: var(--hotel-card-bg);
        border-radius: var(--hotel-radius-md);
        border: 1px solid var(--hotel-border);
        overflow: hidden;
    }

    .skeleton-image {
        height: 200px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    .skeleton-content {
        padding: 16px;
    }

    .skeleton-title {
        height: 20px;
        background: #f0f0f0;
        margin-bottom: 8px;
        border-radius: 4px;
        width: 70%;
    }

    .skeleton-address {
        height: 16px;
        background: #f0f0f0;
        margin-bottom: 12px;
        border-radius: 4px;
        width: 50%;
    }

    .skeleton-rating {
        height: 28px;
        background: #f0f0f0;
        margin-bottom: 12px;
        border-radius: 20px;
        width: 30%;
    }

    .skeleton-features {
        height: 24px;
        background: #f0f0f0;
        margin-bottom: 12px;
        border-radius: 6px;
        width: 80%;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
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
        border-radius: var(--hotel-radius-sm);
        background: white;
        border: 1px solid var(--hotel-border);
        color: var(--hotel-text-soft);
        font-size: 13px;
        font-weight: 500;
        transition: var(--hotel-transition);
        text-decoration: none;
    }

    .page-link:hover {
        border-color: var(--hotel-primary);
        color: var(--hotel-primary);
    }

    .active .page-link {
        background: var(--hotel-primary);
        border-color: var(--hotel-primary);
        color: white;
    }

    .disabled .page-link {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .hotel-layout {
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
            max-height: none;
            overflow-y: visible;
        }
        .filter-actions {
            grid-column: span 2;
        }
    }

    @media (max-width: 640px) {
        .hotel-layout {
            padding: 16px;
        }
        .filter-form {
            grid-template-columns: 1fr;
        }
        .filter-actions {
            grid-column: span 1;
        }
        .hotel-grid {
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

<div class="hotel-layout">
    <!-- Filter Sidebar -->
    <aside class="filters-panel">
        <div class="panel-header">
            <h3>Find Hotels</h3>
        </div>
        
        <form id="filterForm" class="filter-form" method="GET" action="{{ route('hotels.index') }}">
            <!-- Room Type -->
            <div class="filter-group">
                <label>Room Type</label>
                <select name="room_type" id="room_type">
                    <option value="">Any room type</option>
                    @foreach($uniqueRoomTypes as $roomType)
                        <option value="{{ $roomType }}" {{ request('room_type') == $roomType ? 'selected' : '' }}>{{ ucfirst($roomType) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Bed Type -->
            <div class="filter-group">
                <label>Bed Type</label>
                <select name="bed_info" id="bed_info">
                    <option value="">Any bed type</option>
                    @foreach($uniqueBedTypes as $bedType)
                        <option value="{{ $bedType }}" {{ request('bed_info') == $bedType ? 'selected' : '' }}>{{ ucfirst($bedType) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Price Range -->
            <div class="filter-group">
                <label>Price Range</label>
                <div class="price-buttons" id="priceGroup">
                    <div class="price-btn {{ !request('price_tier') ? 'active' : '' }}" data-price="">Any</div>
                    <div class="price-btn {{ request('price_tier') == 'budget' ? 'active' : '' }}" data-price="budget">Budget</div>
                    <div class="price-btn {{ request('price_tier') == 'mid' ? 'active' : '' }}" data-price="mid">Mid</div>
                    <div class="price-btn {{ request('price_tier') == 'luxury' ? 'active' : '' }}" data-price="luxury">Luxury</div>
                </div>
                <input type="hidden" name="price_tier" id="price_tier" value="{{ request('price_tier') }}">
            </div>

            <!-- Max Price -->
            <div class="filter-group">
                <label>Max Price per Night</label>
                <input type="number" name="max_price" id="max_price" placeholder="e.g., 200" step="10" value="{{ request('max_price') }}">
            </div>

            <!-- Minimum Rating -->
            <div class="filter-group">
                <label>Minimum Rating</label>
                <div class="rating-buttons" id="ratingGroup">
                    <div class="rating-btn {{ !request('min_rating') ? 'active' : '' }}" data-rating="">Any</div>
                    <div class="rating-btn {{ request('min_rating') == '4.5' ? 'active' : '' }}" data-rating="4.5">4.5+</div>
                    <div class="rating-btn {{ request('min_rating') == '4.0' ? 'active' : '' }}" data-rating="4.0">4.0+</div>
                    <div class="rating-btn {{ request('min_rating') == '3.5' ? 'active' : '' }}" data-rating="3.5">3.5+</div>
                    <div class="rating-btn {{ request('min_rating') == '3.0' ? 'active' : '' }}" data-rating="3.0">3.0+</div>
                </div>
                <input type="hidden" name="min_rating" id="min_rating" value="{{ request('min_rating') }}">
            </div>

            <!-- Location -->
            <div class="filter-group">
                <label>Location</label>
                <select name="address" id="address">
                    <option value="">All locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location }}" {{ request('address') == $location ? 'selected' : '' }}>{{ ucfirst($location) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Nearby Landmark -->
            <div class="filter-group">
                <label>Nearby Landmark</label>
                <select name="nearby_landmark" id="nearby_landmark">
                    <option value="">Any landmark</option>
                    @foreach($landmarks as $landmark)
                        <option value="{{ $landmark }}" {{ request('nearby_landmark') == $landmark ? 'selected' : '' }}>{{ ucfirst($landmark) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Distance from Beach -->
            <div class="filter-group">
                <label>Distance from Beach</label>
                <select name="distance_from_beach" id="distance_from_beach">
                    <option value="">Any distance</option>
                    @foreach($beachDistances as $distance)
                        <option value="{{ $distance }}" {{ request('distance_from_beach') == $distance ? 'selected' : '' }}>{{ ucfirst($distance) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Distance from Center -->
            <div class="filter-group">
                <label>Distance from Center</label>
                <select name="distance_from_center" id="distance_from_center">
                    <option value="">Any distance</option>
                    @foreach($centerDistances as $distance)
                        <option value="{{ $distance }}" {{ request('distance_from_center') == $distance ? 'selected' : '' }}>{{ ucfirst($distance) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Keyword Search -->
            <div class="filter-group">
                <label>Keyword</label>
                <input type="text" name="keyword" id="keyword" placeholder="pool, wifi, breakfast..." value="{{ request('keyword') }}">
            </div>

            <!-- Actions -->
            <div class="filter-actions">
                <button type="submit" class="btn-apply">Apply Filters</button>
                <button type="button" id="resetFiltersBtn" class="btn-reset">Reset</button>
            </div>
        </form>
    </aside>

    <!-- Results Section -->
    <main class="results-panel">
        <div class="results-header">
            <div class="results-title">
                <h2>Hotels <span>{{ $hotels->total() }} found</span></h2>
            </div>
           
        </div>

        <div id="hotelGrid" class="hotel-grid">
            @forelse($hotels as $hotel)
                <div class="hotel-card" data-hotel-id="{{ $hotel->id }}">
                    <div class="card-image">
                        @php
                            $badgeText = '';
                            $badgeClass = '';
                            if($hotel->rating_score >= 4.5) {
                                $badgeText = 'Top Rated';
                                $badgeClass = 'top-rated';
                            } elseif($hotel->price_tier == 'luxury') {
                                $badgeText = 'Luxury';
                                $badgeClass = 'luxury';
                            } elseif($hotel->price_tier == 'premium') {
                                $badgeText = 'Premium';
                                $badgeClass = 'premium';
                            }
                        @endphp

                        <img src="{{ $hotel->hotel_image ?? 'https://placehold.co/400x240?text=Hotel' }}" 
                             alt="{{ $hotel->hotel_name }}"
                             onerror="this.src='https://placehold.co/400x240?text=No+Image'">
                    </div>
                    <div class="card-content">
                        <h3 class="hotel-name">{{ $hotel->hotel_name }}</h3>
                        <div class="hotel-address">
                            {{ $hotel->address ?? 'Location not specified' }}
                        </div>
                        <div class="rating-badge">
                            {{ number_format($hotel->rating_score, 1) ?? 'N/A' }} stars
                            @if($hotel->review_count)
                                ({{ $hotel->review_count }} reviews)
                            @endif
                        </div>
                        <div class="hotel-features">
                            @if($hotel->room_type)
                                @php $roomTypes = array_map('trim', explode(',', $hotel->room_type)); @endphp
                                @foreach(array_slice($roomTypes, 0, 2) as $room)
                                    <span class="feature-chip">{{ ucfirst($room) }}</span>
                                @endforeach
                            @endif
                            @if($hotel->bed_info)
                                @php $bedTypes = array_map('trim', explode(',', $hotel->bed_info)); @endphp
                                @foreach(array_slice($bedTypes, 0, 2) as $bed)
                                    <span class="feature-chip">{{ ucfirst($bed) }}</span>
                                @endforeach
                            @endif
                            @if($hotel->distance_from_beach)
                                <span class="feature-chip">Beach: {{ $hotel->distance_from_beach }}</span>
                            @endif
                            @if($hotel->distance_from_center)
                                <span class="feature-chip">Center: {{ $hotel->distance_from_center }}</span>
                            @endif
                        </div>
                        @if($hotel->description)
                            <div class="hotel-description">{{ Str::limit($hotel->description, 100) }}</div>
                        @endif
                        <div class="price-section">
                            <div class="price">
                                @php
                                    $price = preg_replace('/[^0-9]/', '', $hotel->price_per_night);
                                @endphp
                                @if($price && is_numeric($price))
                                    ${{ number_format($price, 0) }}
                                @else
                                    {{ $hotel->price_per_night ?? 'Contact' }}
                                @endif
                                <small>/night</small>
                            </div>
                            <div class="details-link">
                                View details →
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-icon">🏨</div>
                    <div class="empty-title">No hotels found</div>
                    <div class="empty-message">Try adjusting your filters to see more results.</div>
                </div>
            @endforelse
        </div>

        @if($hotels->hasPages())
            <div class="pagination-wrapper">
                {{ $hotels->appends(request()->query())->links() }}
            </div>
        @endif
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '{{ url("/hotels") }}';
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
    
    // Hotel card click navigation
    document.querySelectorAll('.hotel-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const hotelId = this.dataset.hotelId;
            if (hotelId) {
                window.location.href = `${baseUrl}/${hotelId}`;
            }
        });
    });
});
</script>

@endsection