@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/restaurant-details.css') }}">
<style>
    /* Hotel-specific styles */
    .hotel-details-hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 600px;
        background: var(--bg-main);
        position: relative;
        overflow: hidden;
    }

    .hotel-details-hero__image {
        position: relative;
        overflow: hidden;
        background: #f0f0f0;
    }

    .hotel-details-hero__image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .hotel-details-hero__image:hover img {
        transform: scale(1.05);
    }

    .hotel-details-hero__content {
        padding: 48px;
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        text-decoration: none;
        margin-bottom: 24px;
        font-weight: 500;
        transition: color 0.2s ease;
        width: fit-content;
    }

    .back-link:hover {
        color: var(--primary);
    }

    .hotel-details-meta {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .hotel-type {
        background: var(--primary);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .hotel-rating {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fef3c7;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 14px;
        color: #92400e;
    }

    .hotel-rating i {
        color: #f59e0b;
    }

    .price-tier {
        display: inline-flex;
        gap: 2px;
        background: #f1f5f9;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: var(--text-muted);
    }

    h1 {
        font-size: 36px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 16px;
        line-height: 1.2;
    }

    .hotel-location {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        margin-bottom: 24px;
        font-size: 16px;
    }

    .hotel-location i {
        color: var(--primary);
        font-size: 18px;
    }

    .hotel-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
        padding: 24px 0;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-item i {
        font-size: 24px;
        color: var(--primary);
        background: #eff6ff;
        padding: 10px;
        border-radius: 12px;
    }

    .info-item div {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
    }

    .info-value a {
        color: var(--primary);
        text-decoration: none;
    }

    .info-value a:hover {
        text-decoration: underline;
    }

    .hotel-details-description {
        color: var(--text-muted);
        line-height: 1.6;
        margin-bottom: 24px;
        font-size: 15px;
    }

    .hotel-feature-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 32px;
    }

    .feature-pill {
        padding: 6px 14px;
        background: #f1f5f9;
        border-radius: 20px;
        font-size: 13px;
        color: var(--text-main);
        font-weight: 500;
    }

    .hotel-actions {
        display: flex;
        gap: 16px;
        margin-top: 16px;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--primary);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        color: var(--primary);
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        border: 2px solid var(--primary);
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }

    /* Amenities Section */
    .hotel-amenities-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .amenities-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .amenities-header h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .amenities-header p {
        color: var(--text-muted);
        font-size: 16px;
    }

    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        background: white;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .amenity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        transition: background 0.2s ease;
    }

    .amenity-item:hover {
        background: #f8fafc;
    }

    .amenity-item i {
        font-size: 24px;
        color: var(--primary);
        width: 40px;
        text-align: center;
    }

    .amenity-info {
        flex: 1;
    }

    .amenity-name {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 4px;
    }

    .amenity-description {
        font-size: 12px;
        color: var(--text-muted);
    }

    /* Room Types Section */
    .room-types-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .room-types-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .room-types-header h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .room-types-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
    }

    .room-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }

    .room-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .room-image {
        height: 200px;
        background: #f0f0f0;
        position: relative;
    }

    .room-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .room-price {
        position: absolute;
        bottom: 16px;
        right: 16px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
    }

    .room-content {
        padding: 20px;
    }

    .room-name {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 8px;
    }

    .room-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin: 12px 0;
        font-size: 13px;
        color: var(--text-muted);
    }

    .room-details span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .room-description {
        color: var(--text-muted);
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .book-now-btn {
        display: block;
        text-align: center;
        background: var(--primary);
        color: white;
        padding: 10px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .book-now-btn:hover {
        background: var(--primary-hover);
    }

    /* Similar Hotels Section */
    .similar-hotels-section {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px 60px;
    }

    .similar-hotels-section h2 {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 32px;
        text-align: center;
    }

    .similar-hotels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
    }

    .similar-hotel-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .similar-hotel-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }

    .similar-hotel-image {
        height: 180px;
        overflow: hidden;
    }

    .similar-hotel-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .similar-hotel-card:hover .similar-hotel-image img {
        transform: scale(1.05);
    }

    .similar-hotel-content {
        padding: 16px;
    }

    .similar-hotel-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 6px;
    }

    .similar-hotel-location {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .similar-hotel-rating {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fef3c7;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        color: #92400e;
        margin-bottom: 8px;
    }

    .similar-hotel-price {
        font-size: 14px;
        font-weight: 700;
        color: var(--primary);
        margin-top: 8px;
    }

    .loading-similar {
        grid-column: 1/-1;
        text-align: center;
        padding: 60px;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .hotel-details-hero {
            grid-template-columns: 1fr;
        }
        
        .hotel-details-hero__image {
            height: 300px;
        }
        
        .hotel-details-hero__content {
            padding: 32px;
        }
        
        h1 {
            font-size: 28px;
        }
        
        .hotel-info-grid {
            grid-template-columns: 1fr;
        }
        
        .amenities-grid {
            grid-template-columns: 1fr;
        }
        
        .room-types-grid {
            grid-template-columns: 1fr;
        }
        
        .similar-hotels-grid {
            grid-template-columns: 1fr;
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
                <span class="hotel-type">
                    @if($hotel->price_tier == 'luxury')
                        💎 Luxury Hotel
                    @elseif($hotel->price_tier == 'premium')
                        ✨ Premium Hotel
                    @elseif($hotel->price_tier == 'mid-range')
                        🏨 Mid-Range Hotel
                    @else
                        💰 Budget Hotel
                    @endif
                </span>
            @endif
            
            <span class="hotel-rating">
                <i class="ri-star-fill"></i> {{ number_format($hotel->rating_score, 1) }}
                @if($hotel->review_count)
                    <span style="font-weight: normal;">({{ $hotel->review_count }} reviews)</span>
                @endif
            </span>
            
            <span class="price-tier">
                @for($i = 1; $i <= 4; $i++)
                    @php
                        $priceLevel = '';
                        if($hotel->price_tier == 'budget') $priceLevel = 1;
                        elseif($hotel->price_tier == 'mid-range') $priceLevel = 2;
                        elseif($hotel->price_tier == 'premium') $priceLevel = 3;
                        elseif($hotel->price_tier == 'luxury') $priceLevel = 4;
                        else $priceLevel = 0;
                    @endphp
                    @if($i <= $priceLevel)
                        <i class="ri-money-dollar-circle-fill"></i>
                    @else
                        <i class="ri-money-dollar-circle-line"></i>
                    @endif
                @endfor
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
                    <span class="info-label">Bed Info</span>
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
        <div class="hotel-feature-list">
            <div class="info-item" style="margin-bottom: 16px;">
                <i class="ri-chat-quote-fill"></i>
                <div>
                    <span class="info-label">Guest Review</span>
                    <span class="info-value" style="font-style: italic;">"{{ $hotel->review_text }}"</span>
                </div>
            </div>
        </div>
        @endif

        <div class="hotel-actions">
            @if($hotel->hotel_url)
            <a href="{{ $hotel->hotel_url }}" class="btn-primary" target="_blank">
                <i class="ri-external-link-line"></i> Book Now
            </a>
            @endif
            <a href="#" class="btn-secondary" onclick="window.print(); return false;">
                <i class="ri-printer-line"></i> Print Details
            </a>
        </div>
    </div>
</section>

<!-- Amenities Section (if you have amenities data) -->
@if(isset($amenities) && count($amenities) > 0)
<section class="hotel-amenities-section">
    <div class="amenities-header">
        <h2>🏨 Hotel Amenities</h2>
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
        <h2>🛏️ Available Room Types</h2>
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
document.addEventListener('DOMContentLoaded', function() {
    // Get the current hotel ID from Laravel
    const hotelId = {{ $hotel->id }};
    
    // Load and render similar hotels
    loadSimilarHotels(hotelId);
});

async function loadSimilarHotels(hotelId) {
    const grid = document.getElementById('similarHotelsGrid');
    
    try {
        const response = await fetch(`http://127.0.0.1:5000/similar-hotels/${hotelId}?limit=6`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.similar_hotels && data.similar_hotels.length > 0) {
            renderSimilarHotels(data.similar_hotels);
        } else {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <p style="color: var(--text-muted);">No similar hotels found at the moment.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading similar hotels:', error);
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                <p style="color: #ef4444;">Unable to load similar hotels. Please try again later.</p>
                <button onclick="location.reload()" style="margin-top: 16px; padding: 8px 16px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Retry
                </button>
            </div>
        `;
    }
}

function renderSimilarHotels(hotels) {
    const grid = document.getElementById('similarHotelsGrid');
    
    grid.innerHTML = hotels.map(hotel => `
        <a href="/hotels/${hotel.id}" class="similar-hotel-card">
            <div class="similar-hotel-image">
                <img src="${hotel.hotel_image || '{{ asset('images/default-hotel.jpg') }}'}" 
                     alt="${hotel.hotel_name}"
                     onerror="this.src='{{ asset('images/default-hotel.jpg') }}'">
            </div>
            <div class="similar-hotel-content">
                <h3 class="similar-hotel-name">${hotel.hotel_name || 'Unnamed Hotel'}</h3>
                <div class="similar-hotel-location">
                    <i class="ri-map-pin-line"></i> ${hotel.address || 'Location not specified'}
                </div>
                ${hotel.rating_score ? `
                <div class="similar-hotel-rating">
                    <i class="ri-star-fill"></i> ${hotel.rating_score}
                </div>
                ` : ''}
                ${hotel.price_per_night ? `
                <div class="similar-hotel-price">
                    ${hotel.price_per_night} <span style="font-weight: normal;">/ night</span>
                </div>
                ` : ''}
                ${hotel.room_type ? `
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                    🛏️ ${hotel.room_type}
                </div>
                ` : ''}
            </div>
        </a>
    `).join('');
}
</script>
@endpush