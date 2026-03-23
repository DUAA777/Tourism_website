@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/restaurant-details.css') }}">
@endpush

@section('content')
<section class="restaurant-details-hero">
    <!-- Your existing hero section code remains the same -->
    <div class="restaurant-details-hero__image">
        <img src="{{ asset($restaurant->image) }}" alt="{{ $restaurant->restaurant_name }}">
    </div>

    <div class="restaurant-details-hero__content">
        <a href="/restaurants" class="back-link">
            <i class="ri-arrow-left-line"></i> Back to Restaurants
        </a>

        <div class="restaurant-details-meta">
            <span class="restaurant-type">{{ $restaurant->restaurant_type }}</span>
            <span class="restaurant-rating">
                <i class="ri-star-fill"></i> {{ $restaurant->rating }}
            </span>
            <span class="price-tier">
                @for($i = 1; $i <= 4; $i++)
                    @if($i <= strlen($restaurant->price_tier))
                        <i class="ri-money-dollar-circle-fill"></i>
                    @else
                        <i class="ri-money-dollar-circle-line"></i>
                    @endif
                @endfor
            </span>
        </div>

        <h1>{{ $restaurant->restaurant_name }}</h1>
        <p class="restaurant-location">
            <i class="ri-map-pin-2-fill"></i> {{ $restaurant->location }}
        </p>

        <div class="restaurant-info-grid">
            <div class="info-item">
                <i class="ri-restaurant-fill"></i>
                <div>
                    <span class="info-label">Food Type</span>
                    <span class="info-value">{{ $restaurant->food_type }}</span>
                </div>
            </div>

            <div class="info-item">
                <i class="ri-time-fill"></i>
                <div>
                    <span class="info-label">Opening Hours</span>
                    <span class="info-value">{{ $restaurant->opening_hours }}</span>
                </div>
            </div>

            <div class="info-item">
                <i class="ri-phone-fill"></i>
                <div>
                    <span class="info-label">Contact</span>
                    <span class="info-value">{{ $restaurant->phone_number }}</span>
                </div>
            </div>

            @if($restaurant->website)
            <div class="info-item">
                <i class="ri-global-fill"></i>
                <div>
                    <span class="info-label">Website</span>
                    <a href="{{ $restaurant->website }}" class="info-value" target="_blank">Visit Website</a>
                </div>
            </div>
            @endif
        </div>

        <p class="restaurant-details-description">{{ $restaurant->description }}</p>

        @if($restaurant->tags)
        <div class="restaurant-feature-list">
            @foreach($restaurant->getTagsArrayAttribute() as $tag)
                <span class="feature-pill">{{ $tag }}</span>
            @endforeach
        </div>
        @endif

        @if($restaurant->directory_url)
        <div class="restaurant-actions">
            <a href="{{ $restaurant->directory_url }}" class="btn-primary" target="_blank">
                <i class="ri-menu-line"></i> View Full Menu
            </a>
        </div>
        @endif
    </div>
</section>

<section class="similar-restaurants-section">
    <h2>Similar Restaurants You Might Like</h2>
    <div class="similar-restaurants-grid" id="similarRestaurantsGrid">
        <!-- Similar restaurants will be loaded here dynamically -->
        <div class="loading-similar" style="grid-column: 1/-1; text-align: center; padding: 40px;">
            <div class="loading-spinner"></div>
            <p>Finding similar restaurants...</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/similar-restaurants.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the current restaurant ID from Laravel
    const restaurantId = {{ $restaurant->id }};
    
    // Load and render similar restaurants
    similarRestaurantsAPI.loadAndRenderSimilar(
        restaurantId, 
        'similarRestaurantsGrid', 
        6  // Number of similar restaurants to show
    );
});
</script>
@endpush