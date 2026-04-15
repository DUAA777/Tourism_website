@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/restaurant-details.css') }}">
<style>
    /* Hotel-specific styles aligned with restaurant theme */
    :root {
        --hotel-details-bg: #f6f7fb;
        --hotel-details-card: #ffffff;
        --hotel-details-text-dark: #1b1b1f;
        --hotel-details-text-soft: #6f7380;
        --hotel-details-primary: #ff6b2c;
        --hotel-details-primary-dark: #e85d22;
        --hotel-details-shadow: 0 20px 50px rgba(16, 24, 40, 0.08);
        --hotel-details-shadow-hover: 0 30px 60px rgba(16, 24, 40, 0.12);
        --hotel-details-radius: 30px;
        --hotel-details-radius-lg: 22px;
        --hotel-details-radius-md: 16px;
        --hotel-details-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hotel-details-hero {
        width: min(1280px, calc(100% - 32px));
        margin: 32px auto 0;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 28px;
        align-items: stretch;
    }

    .hotel-details-hero__image {
        border-radius: var(--hotel-details-radius);
        overflow: hidden;
        box-shadow: var(--hotel-details-shadow);
        min-height: 520px;
    }

    .hotel-details-hero__image img {
        width: 100%;
        height: 100%;
        min-height: 520px;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }

    .hotel-details-hero__image:hover img {
        transform: scale(1.05);
    }

    .hotel-details-hero__content {
        background: var(--hotel-details-card);
        border-radius: var(--hotel-details-radius);
        box-shadow: var(--hotel-details-shadow);
        padding: 34px;
        display: flex;
        flex-direction: column;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: var(--hotel-details-text-soft);
        margin-bottom: 18px;
        font-weight: 600;
        transition: transform 0.2s ease, color 0.2s ease;
        width: fit-content;
    }

    .back-link:hover {
        color: var(--hotel-details-primary);
        transform: translateX(-5px);
    }

    .hotel-details-meta {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .hotel-type {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 107, 44, 0.12);
        color: var(--hotel-details-primary);
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: capitalize;
    }

    .hotel-rating {
        color: #f59e0b;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 6px;
        background: #fef3e8;
        padding: 4px 12px;
        border-radius: 999px;
    }

    .hotel-rating i {
        color: #f59e0b;
    }

    .price-tier {
        color: var(--hotel-details-primary);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 2px;
    }

    h1 {
        margin: 0 0 10px;
        font-size: clamp(2rem, 4vw, 3rem);
        color: var(--hotel-details-text-dark);
        line-height: 1.1;
        font-weight: 800;
    }

    .hotel-location {
        margin: 0 0 20px;
        color: var(--hotel-details-text-soft);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .hotel-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 24px;
        padding: 20px;
        background: #f8f9fc;
        border-radius: 20px;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-item i {
        font-size: 1.5rem;
        color: var(--hotel-details-primary);
        width: 32px;
    }

    .info-item div {
        flex: 1;
    }

    .info-label {
        display: block;
        font-size: 0.75rem;
        color: var(--hotel-details-text-soft);
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .info-value {
        display: block;
        font-weight: 600;
        color: var(--hotel-details-text-dark);
        text-decoration: none;
        font-size: 0.9rem;
    }

    .info-value a {
        color: var(--hotel-details-primary);
        text-decoration: none;
    }

    .info-value a:hover {
        text-decoration: underline;
    }

    .hotel-details-description {
        color: var(--hotel-details-text-soft);
        line-height: 1.8;
        margin: 0 0 24px;
    }

    .hotel-feature-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 26px;
    }

    .feature-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: #f3f4f7;
        color: var(--hotel-details-text-dark);
        font-weight: 600;
        font-size: 0.92rem;
    }

    .review-quote {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 20px;
        margin-bottom: 24px;
        border-left: 4px solid var(--hotel-details-primary);
    }

    .review-quote i {
        color: var(--hotel-details-primary);
        font-size: 1.2rem;
        margin-right: 8px;
    }

    .review-text {
        color: var(--hotel-details-text-dark);
        font-style: italic;
        line-height: 1.6;
        margin: 8px 0 0 0;
    }

    .hotel-actions {
        display: flex;
        gap: 16px;
        margin-top: auto;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex: 1;
        padding: 14px 24px;
        background: linear-gradient(135deg, var(--hotel-details-primary) 0%, var(--hotel-details-primary-dark) 100%);
        color: white;
        text-decoration: none;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: var(--hotel-details-transition);
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 107, 44, 0.3);
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        background: white;
        color: var(--hotel-details-primary);
        text-decoration: none;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.95rem;
        border: 2px solid var(--hotel-details-primary);
        transition: var(--hotel-details-transition);
        cursor: pointer;
    }

    .btn-secondary:hover {
        background: var(--hotel-details-primary);
        color: white;
        transform: translateY(-2px);
    }

    /* Amenities Section */
    .hotel-amenities-section {
        width: min(1280px, calc(100% - 32px));
        margin: 60px auto;
    }

    .amenities-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .amenities-header h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--hotel-details-text-dark);
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }

    .amenities-header p {
        color: var(--hotel-details-text-soft);
        font-size: 1rem;
    }

    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
        background: white;
        padding: 32px;
        border-radius: var(--hotel-details-radius-lg);
        box-shadow: var(--hotel-details-shadow);
    }

    .amenity-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px;
        border-radius: var(--hotel-details-radius-md);
        transition: var(--hotel-details-transition);
    }

    .amenity-item:hover {
        background: #f8f9fc;
        transform: translateX(4px);
    }

    .amenity-item i {
        font-size: 1.5rem;
        color: var(--hotel-details-primary);
        width: 40px;
        text-align: center;
    }

    .amenity-info {
        flex: 1;
    }

    .amenity-name {
        font-weight: 700;
        color: var(--hotel-details-text-dark);
        margin-bottom: 4px;
    }

    .amenity-description {
        font-size: 0.8rem;
        color: var(--hotel-details-text-soft);
    }

    /* Room Types Section */
    .room-types-section {
        width: min(1280px, calc(100% - 32px));
        margin: 60px auto;
    }

    .room-types-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .room-types-header h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--hotel-details-text-dark);
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }

    .room-types-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
    }

    .room-card {
        background: white;
        border-radius: var(--hotel-details-radius-lg);
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: var(--hotel-details-transition);
    }

    .room-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--hotel-details-shadow-hover);
        border-color: transparent;
    }

    .room-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .room-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .room-card:hover .room-image img {
        transform: scale(1.08);
    }

    .room-price {
        position: absolute;
        bottom: 16px;
        right: 16px;
        background: rgba(0, 0, 0, 0.85);
        color: white;
        padding: 6px 14px;
        border-radius: 40px;
        font-weight: 700;
        font-size: 0.9rem;
        backdrop-filter: blur(4px);
    }

    .room-content {
        padding: 20px;
    }

    .room-name {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--hotel-details-text-dark);
        margin-bottom: 8px;
    }

    .room-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin: 12px 0;
        font-size: 0.8rem;
        color: var(--hotel-details-text-soft);
    }

    .room-details span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .room-description {
        color: var(--hotel-details-text-soft);
        font-size: 0.85rem;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .book-now-btn {
        display: block;
        text-align: center;
        background: var(--hotel-details-primary);
        color: white;
        padding: 10px 16px;
        border-radius: 40px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: var(--hotel-details-transition);
    }

    .book-now-btn:hover {
        background: var(--hotel-details-primary-dark);
        transform: translateY(-2px);
    }

    /* Similar Hotels Section */
    .similar-hotels-section {
        width: min(1280px, calc(100% - 32px));
        margin: 60px auto 80px;
    }

    .similar-hotels-section h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--hotel-details-text-dark);
        margin-bottom: 32px;
        text-align: center;
        letter-spacing: -0.01em;
    }

    .similar-hotels-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
    }

    .similar-hotel-card {
        background: white;
        border-radius: var(--hotel-details-radius-lg);
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: var(--hotel-details-transition);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .similar-hotel-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--hotel-details-shadow-hover);
        border-color: transparent;
    }

    .similar-hotel-image {
        height: 200px;
        overflow: hidden;
    }

    .similar-hotel-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .similar-hotel-card:hover .similar-hotel-image img {
        transform: scale(1.08);
    }

    .similar-hotel-content {
        padding: 20px;
    }

    .similar-hotel-name {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--hotel-details-text-dark);
        margin-bottom: 6px;
        line-height: 1.3;
    }

    .similar-hotel-location {
        font-size: 0.8rem;
        color: var(--hotel-details-text-soft);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .similar-hotel-rating {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fef3e8;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--hotel-details-primary);
        margin-bottom: 10px;
    }

    .similar-hotel-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--hotel-details-primary);
        margin-top: 10px;
    }

    .similar-hotel-room-type {
        font-size: 0.75rem;
        color: var(--hotel-details-text-soft);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Loading State */
    .loading-similar {
        grid-column: 1/-1;
        text-align: center;
        padding: 60px;
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid #f1f5f9;
        border-top-color: var(--hotel-details-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 1000px) {
        .hotel-details-hero {
            grid-template-columns: 1fr;
        }

        .similar-hotels-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .hotel-info-grid {
            grid-template-columns: 1fr;
        }
        
        .amenities-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .hotel-details-hero,
        .hotel-amenities-section,
        .room-types-section,
        .similar-hotels-section {
            width: calc(100% - 20px);
        }

        .hotel-details-hero__image,
        .hotel-details-hero__image img {
            min-height: 320px;
        }

        .hotel-details-hero__content {
            padding: 24px;
        }

        .similar-hotels-grid {
            grid-template-columns: 1fr;
        }

        .hotel-actions {
            flex-direction: column;
        }
        
        .amenities-grid {
            padding: 20px;
        }
        
        .room-types-grid {
            gap: 20px;
        }
    }
</style>
@endpush

@section('content')
<section class="hotel-details-hero">
    <div class="hotel-details-hero__image">
        <img src="{{ $hotel->hotel_image ?? asset('images/default-hotel.jpg') }}" alt="{{ $hotel->hotel_name }}">
    </div>

    <div class="hotel-details-hero__content">
        <a href="/hotels" class="back-link">
            <i class="ri-arrow-left-line"></i> Back to Hotels
        </a>

        <div class="hotel-details-meta">
            @if($hotel->price_tier)
                <span class="hotel-type">{{ ucfirst($hotel->price_tier) }} Hotel</span>
            @endif
            
            <span class="hotel-rating">
                <i class="ri-star-fill"></i> {{ number_format($hotel->rating_score, 1) }}
                @if($hotel->review_count)
                    ({{ $hotel->review_count }} reviews)
                @endif
            </span>
            
            <span class="price-tier">
                {{ $hotel->price_tier }}
            </span>
        </div>

        <h1>{{ $hotel->hotel_name }}</h1>
        
        <p class="hotel-location">
            <i class="ri-map-pin-2-fill"></i> {{ $hotel->address }}
        </p>

        <div class="hotel-info-grid">
            @if($hotel->room_type)
            <div class="info-item">
                <i class="ri-hotel-bed-fill"></i>
                <div>
                    <span class="info-label">Room Type</span>
                    <span class="info-value">{{ $hotel->room_type }}</span>
                </div>
            </div>
            @endif

            @if($hotel->bed_info)
            <div class="info-item">
                <i class="ri-bed-fill"></i>
                <div>
                    <span class="info-label">Bed Configuration</span>
                    <span class="info-value">{{ $hotel->bed_info }}</span>
                </div>
            </div>
            @endif

            @if($hotel->price_per_night)
            <div class="info-item">
                <i class="ri-money-dollar-circle-fill"></i>
                <div>
                    <span class="info-label">Price per Night</span>
                    <span class="info-value">{{ $hotel->price_per_night }}</span>
                </div>
            </div>
            @endif

            @if($hotel->taxes_fees)
            <div class="info-item">
                <i class="ri-receipt-fill"></i>
                <div>
                    <span class="info-label">Taxes & Fees</span>
                    <span class="info-value">{{ $hotel->taxes_fees }}</span>
                </div>
            </div>
            @endif

            @if($hotel->distance_from_center)
            <div class="info-item">
                <i class="ri-map-pin-fill"></i>
                <div>
                    <span class="info-label">Distance from Center</span>
                    <span class="info-value">{{ $hotel->distance_from_center }}</span>
                </div>
            </div>
            @endif

            @if($hotel->distance_from_beach)
            <div class="info-item">
                <i class="ri-umbrella-fill"></i>
                <div>
                    <span class="info-label">Distance from Beach</span>
                    <span class="info-value">{{ $hotel->distance_from_beach }}</span>
                </div>
            </div>
            @endif

            @if($hotel->nearby_landmark)
            <div class="info-item">
                <i class="ri-landmark-fill"></i>
                <div>
                    <span class="info-label">Nearby Landmark</span>
                    <span class="info-value">{{ $hotel->nearby_landmark }}</span>
                </div>
            </div>
            @endif

            @if($hotel->stay_details)
            <div class="info-item">
                <i class="ri-calendar-check-fill"></i>
                <div>
                    <span class="info-label">Stay Details</span>
                    <span class="info-value">{{ $hotel->stay_details }}</span>
                </div>
            </div>
            @endif
        </div>

        @if($hotel->description)
        <p class="hotel-details-description">{{ $hotel->description }}</p>
        @endif

        @if($hotel->review_text)
        <div class="review-quote">
            <i class="ri-chat-quote-fill"></i>
            <span class="info-label">Rating</span>
            <p class="review-text">"{{ $hotel->review_text }}"</p>
        </div>
        @endif

        <div class="hotel-actions">
            @if($hotel->hotel_url)
            <a href="{{ $hotel->hotel_url }}" class="btn-primary" target="_blank">
                <i class="ri-external-link-line"></i> More Details
            </a>
            @endif

        </div>
    </div>
</section>

<!-- Amenities Section -->
@if(isset($amenities) && count($amenities) > 0)
<section class="hotel-amenities-section">
    <div class="amenities-header">
        <h2>Hotel Amenities</h2>
        <p>Everything you need for a comfortable stay</p>
    </div>
    <div class="amenities-grid">
        @foreach($amenities as $amenity)
        <div class="amenity-item">
            <i class="{{ $amenity['icon'] }}"></i>
            <div class="amenity-info">
                <div class="amenity-name">{{ $amenity['name'] }}</div>
                <div class="amenity-description">{{ $amenity['description'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- Room Types Section -->
@if(isset($roomTypes) && count($roomTypes) > 0)
<section class="room-types-section">
    <div class="room-types-header">
        <h2>Available Room Types</h2>
        <p>Choose the perfect room for your stay</p>
    </div>
    <div class="room-types-grid">
        @foreach($roomTypes as $room)
        <div class="room-card">
            <div class="room-image">
                <img src="{{ $room['image'] ?? asset('images/default-room.jpg') }}" alt="{{ $room['name'] }}">
                <div class="room-price">{{ $room['price'] }}</div>
            </div>
            <div class="room-content">
                <h3 class="room-name">{{ $room['name'] }}</h3>
                <div class="room-details">
                    <span><i class="ri-hotel-bed-fill"></i> {{ $room['bed'] }}</span>
                    <span><i class="ri-group-fill"></i> {{ $room['capacity'] }}</span>
                    <span><i class="ri-square-fill"></i> {{ $room['size'] }}</span>
                </div>
                <p class="room-description">{{ $room['description'] }}</p>
                <a href="{{ $room['booking_url'] ?? '#' }}" class="book-now-btn" target="_blank">
                    Book This Room →
                </a>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<!-- Similar Hotels Section -->
<section class="similar-hotels-section">
    <h2>Similar Hotels You Might Like</h2>
    <div class="similar-hotels-grid" id="similarHotelsGrid">
        <div class="loading-similar">
            <div class="loading-spinner"></div>
            <p>Finding similar hotels...</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
window.SIMILARITY_SERVICE_BASE_URL = @json(rtrim((string) config('services.similarity.base_url', 'http://127.0.0.1:5001'), '/'));

document.addEventListener('DOMContentLoaded', function() {
    const hotelId = {{ $hotel->id ?? 0 }};
    
    if (hotelId) {
        loadSimilarHotels(hotelId);
    }
});

async function loadSimilarHotels(hotelId) {
    const grid = document.getElementById('similarHotelsGrid');
    
    try {
        const response = await fetch(`${window.SIMILARITY_SERVICE_BASE_URL}/similar-hotels/${hotelId}?limit=6`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.similar_hotels && data.similar_hotels.length > 0) {
            renderSimilarHotels(data.similar_hotels);
        } else {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <p style="color: var(--hotel-details-text-soft);">No similar hotels found at the moment.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading similar hotels:', error);
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                <p style="color: #ef4444; margin-bottom: 16px;">Unable to load similar hotels. Please try again later.</p>
                <button onclick="location.reload()" style="padding: 10px 24px; background: var(--hotel-details-primary); color: white; border: none; border-radius: 40px; cursor: pointer; font-weight: 600;">
                    Retry
                </button>
            </div>
        `;
    }
}

function renderSimilarHotels(hotels) {
    const grid = document.getElementById('similarHotelsGrid');
    
    if (!grid) return;

    const uniqueHotels = deduplicateHotelsForDisplay(hotels);
    
    grid.innerHTML = uniqueHotels.map(hotel => `
        <a href="/hotels/${hotel.id}" class="similar-hotel-card">
            <div class="similar-hotel-image">
                <img src="${hotel.hotel_image || '{{ asset('images/default-hotel.jpg') }}'}" 
                     alt="${hotel.hotel_name || 'Hotel'}"
                     onerror="this.src='{{ asset('images/default-hotel.jpg') }}'">
            </div>
            <div class="similar-hotel-content">
                <h3 class="similar-hotel-name">${escapeHtml(hotel.hotel_name || 'Unnamed Hotel')}</h3>
                <div class="similar-hotel-location">
                    <i class="ri-map-pin-line"></i> ${escapeHtml(hotel.address || 'Location not specified')}
                </div>
                ${hotel.rating_score ? `
                <div class="similar-hotel-rating">
                    <i class="ri-star-fill"></i> ${hotel.rating_score}
                </div>
                ` : ''}
                ${hotel.price_per_night ? `
                <div class="similar-hotel-price">
                    ${escapeHtml(hotel.price_per_night)} <span style="font-weight: normal;">/ night</span>
                </div>
                ` : ''}
                ${hotel.room_type ? `
                <div class="similar-hotel-room-type">
                    <i class="ri-hotel-bed-line"></i> ${escapeHtml(hotel.room_type)}
                </div>
                ` : ''}
            </div>
        </a>
    `).join('');
}

function deduplicateHotelsForDisplay(hotels) {
    if (!Array.isArray(hotels)) {
        return [];
    }

    const seen = new Set();

    return hotels.filter((hotel) => {
        const name = String(hotel?.hotel_name || '').trim().toLowerCase();
        const address = String(hotel?.address || '').trim().toLowerCase();
        const familyKey = getCuratedHotelFamilyKey(name);
        const fallbackId = hotel?.id != null ? `id:${hotel.id}` : '';
        const key = familyKey || [name, address].filter(Boolean).join('|') || fallbackId;

        if (!key || seen.has(key)) {
            return false;
        }

        seen.add(key);
        return true;
    });
}

function getCuratedHotelFamilyKey(name) {
    if (!name) {
        return '';
    }

    if (/\bsands\b/.test(name)) {
        return 'family:sands-guesthouse';
    }

    if (/\bbeit chams\b/.test(name)) {
        return 'family:beit-chams';
    }

    if (/\barcades\b.*\bbahsa\b/.test(name)) {
        return 'family:arcades-de-bahsa';
    }

    return '';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
