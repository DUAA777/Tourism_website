@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
<style>
    :root {
        --primary: #3b82f6;
        --primary-hover: #2563eb;
        --bg-main: #f8fafc;
        --bg-card: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --accent: #f59e0b;
        --success: #10b981;
        --info: #06b6d4;
        --luxury: #8b5cf6;
    }

    body { 
        background-color: var(--bg-main); 
        font-family: 'Inter', sans-serif; 
    }

    /* Hero Section */
    .hotel-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 20px;
        text-align: center;
        color: white;
        margin-bottom: 40px;
    }

    .hotel-hero h1 {
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .hotel-hero p {
        font-size: 18px;
        opacity: 0.95;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Filter Section */
    .filter-grid-container {
        max-width: 1200px;
        margin: -30px auto 40px;
        padding: 35px;
        background: var(--bg-card);
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        position: relative;
        z-index: 10;
    }

    .filter-header { 
        margin-bottom: 25px; 
        text-align: center; 
    }
    
    .filter-header h2 { 
        font-size: 28px; 
        font-weight: 800; 
        color: var(--text-main); 
        margin-bottom: 8px;
    }
    
    .filter-header p {
        color: var(--text-muted);
        font-size: 16px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .filter-group { 
        display: flex; 
        flex-direction: column; 
    }
    
    .filter-group label {
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--text-main);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-group select, .filter-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        background-color: #f8fafc;
        font-size: 14px;
        color: var(--text-main);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-group select:focus, .filter-group input:focus {
        border-color: var(--primary);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .price-range-input {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .price-range-input input {
        flex: 1;
    }

    /* Recommendation Type Selector */
    .rec-type-selector {
        grid-column: span 4;
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 15px 0;
        flex-wrap: wrap;
    }

    .rec-type-btn {
        padding: 12px 24px;
        border: 2px solid #e2e8f0;
        border-radius: 40px;
        background: white;
        color: var(--text-main);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .rec-type-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .rec-type-btn.smart.active { background: var(--primary); border-color: var(--primary); }
    .rec-type-btn.popular.active { background: #f59e0b; border-color: #f59e0b; }
    .rec-type-btn.rated.active { background: #10b981; border-color: #10b981; }
    .rec-type-btn.diverse.active { background: #8b5cf6; border-color: #8b5cf6; }

    .submit-btn {
        grid-column: span 4;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
        color: white;
        padding: 16px;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .submit-btn:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    }
    
    .submit-btn:disabled { 
        opacity: 0.7; 
        cursor: not-allowed; 
        transform: none;
    }

    /* Results Section */
    .results-container {
        max-width: 1200px;
        margin: 40px auto 60px;
        padding: 0 20px;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .results-header h3 {
        font-size: 24px;
        font-weight: 800;
        color: var(--text-main);
        margin: 0;
    }

    .results-stats {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .results-count {
        background: #e2e8f0;
        padding: 6px 12px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
    }

    .similarity-info {
        background: #fef3c7;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: #92400e;
        font-weight: 500;
    }

    /* Hotel Grid */
    .hotel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
    }

    .hotel-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .hotel-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.12); 
    }

    .hotel-image-container {
        position: relative;
        overflow: hidden;
        height: 220px;
    }

    .hotel-image { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: transform 0.5s ease;
    }

    .hotel-card:hover .hotel-image {
        transform: scale(1.08);
    }

    .hotel-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 6px 12px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 800;
        z-index: 2;
        backdrop-filter: blur(8px);
    }

    .hotel-badge.luxury {
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        color: white;
    }

    .hotel-badge.premium {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white;
    }

    .hotel-badge.popular {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .hotel-badge.top-rated {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
    }

    .hotel-content { 
        padding: 20px; 
    }
    
    .hotel-title { 
        font-size: 18px; 
        font-weight: 800; 
        color: var(--text-main); 
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .hotel-location { 
        font-size: 13px; 
        color: var(--text-muted); 
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .rating-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #fef3c7;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 13px;
        color: #92400e;
        margin-bottom: 12px;
    }

    .hotel-details {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }

    .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        background: #f1f5f9;
        border-radius: 8px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .price-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f1f5f9;
    }

    .price {
        font-size: 20px;
        font-weight: 800;
        color: var(--primary);
    }

    .price small {
        font-size: 12px;
        font-weight: 400;
        color: var(--text-muted);
    }

    .view-details {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--primary);
        font-weight: 600;
        font-size: 13px;
        transition: gap 0.2s ease;
    }

    .hotel-card:hover .view-details {
        gap: 10px;
    }

    /* Loading State */
    .loading-skeleton {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }

    .skeleton-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }

    .skeleton-image {
        height: 220px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Responsive Design */
    @media (max-width: 1000px) {
        .filter-form { grid-template-columns: repeat(2, 1fr); }
        .rec-type-selector, .submit-btn { grid-column: span 2; }
        .hotel-hero h1 { font-size: 36px; }
    }

    @media (max-width: 600px) {
        .filter-form { grid-template-columns: 1fr; }
        .rec-type-selector, .submit-btn { grid-column: span 1; }
        .rec-type-selector { flex-direction: column; }
        .hotel-hero h1 { font-size: 28px; }
        .hotel-hero p { font-size: 14px; }
        .results-header { flex-direction: column; align-items: flex-start; }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<div class="hotel-hero">
    <h1>🏨 Find Your Perfect Stay</h1>
    <p>Discover the best hotels, resorts, and accommodations tailored to your preferences</p>
</div>

<!-- Filter Section -->
<div class="filter-grid-container">
    <div class="filter-header">
        <h2>✨ Smart Hotel Recommendations</h2>
        <p>Tell us what you're looking for and let AI find your ideal stay</p>
    </div>
    
    <form id="hotelFilterForm" class="filter-form">
        <div class="filter-group">
            <label>🛏️ Room Type</label>
            <select name="room_type">
                <option value="">Any Room Type</option>
                <option value="standard">Standard Room</option>
                <option value="deluxe">Deluxe Room</option>
                <option value="suite">Suite</option>
                <option value="family">Family Room</option>
                <option value="presidential">Presidential Suite</option>
                <option value="villa">Villa</option>
            </select>
        </div>

        <div class="filter-group">
            <label>🛌 Bed Type</label>
            <select name="bed_info">
                <option value="">Any Bed Type</option>
                <option value="single">Single Bed</option>
                <option value="double">Double Bed</option>
                <option value="queen">Queen Bed</option>
                <option value="king">King Bed</option>
                <option value="twin">Twin Beds</option>
            </select>
        </div>

        <div class="filter-group">
            <label>⭐ Minimum Rating</label>
            <select name="rating_score">
                <option value="0">Any Rating</option>
                <option value="4.5">4.5+ Stars</option>
                <option value="4.0">4.0+ Stars</option>
                <option value="3.5">3.5+ Stars</option>
                <option value="3.0">3.0+ Stars</option>
            </select>
        </div>

        <div class="filter-group">
            <label>📍 Landmark</label>
            <select name="nearby_landmark">
                <option value="">Any Landmark</option>
                <option value="beach">Near Beach</option>
                <option value="city center">City Center</option>
                <option value="airport">Near Airport</option>
                <option value="mall">Shopping Mall</option>
                <option value="historical">Historical Sites</option>
            </select>
        </div>

        <div class="filter-group">
            <label>🏖️ Beach Distance</label>
            <select name="distance_from_beach">
                <option value="">Any Distance</option>
                <option value="beachfront">Beachfront</option>
                <option value="100m">Less than 100m</option>
                <option value="500m">Less than 500m</option>
                <option value="1km">Less than 1km</option>
            </select>
        </div>

        <div class="filter-group">
            <label>🏙️ City/Area</label>
            <select name="address">
                <option value="">All Locations</option>
                <option value="Colombo">Colombo</option>
                <option value="Galle">Galle</option>
                <option value="Kandy">Kandy</option>
                <option value="Negombo">Negombo</option>
                <option value="Bentota">Bentota</option>
                <option value="Hikkaduwa">Hikkaduwa</option>
                <option value="Mirissa">Mirissa</option>
                <option value="Ella">Ella</option>
                <option value="Nuwara Eliya">Nuwara Eliya</option>
            </select>
        </div>

        <div class="filter-group">
            <label>💰 Price per Night</label>
            <div class="price-range-input">
                <input type="number" name="price_per_night" placeholder="Max price (e.g., 150)" step="10">
            </div>
        </div>

        <div class="filter-group">
            <label>🚗 Distance from Center</label>
            <select name="distance_from_center">
                <option value="">Any Distance</option>
                <option value="1km">Less than 1km</option>
                <option value="3km">Less than 3km</option>
                <option value="5km">Less than 5km</option>
                <option value="10km">Less than 10km</option>
            </select>
        </div>

        <!-- Recommendation Type Selector -->
        <div class="rec-type-selector">
            <button type="button" class="rec-type-btn smart active" data-type="smart">
                🎯 Smart Match
            </button>
            <button type="button" class="rec-type-btn popular" data-type="popular">
                🔥 Most Popular
            </button>
            <button type="button" class="rec-type-btn rated" data-type="highly_rated">
                ⭐ Top Rated
            </button>
            <button type="button" class="rec-type-btn diverse" data-type="diverse">
                🎨 Diverse Picks
            </button>
        </div>

        <input type="hidden" name="recommendation_type" id="recommendationType" value="smart">

        <button type="submit" class="submit-btn" id="submitBtn">
            <span class="btn-text">🔍 Find Hotels</span>
            <span class="btn-loading" style="display:none;">⏳ Finding the best stays...</span>
        </button>
    </form>
</div>

<!-- Results Section -->
<div class="results-container">
    <div class="results-header">
        <h3>🏨 Recommended Stays</h3>
        <div class="results-stats">
            <span class="results-count" id="resultsCount">0 hotels</span>
            <span class="similarity-info" id="similarityInfo" style="display: none;"></span>
        </div>
    </div>
    <div id="hotelGrid" class="hotel-grid">
        <!-- Results will be loaded here -->
        <div style="grid-column: 1/-1; text-align:center; padding: 80px 20px; color: var(--text-muted);">
            <div style="font-size: 64px; margin-bottom: 20px;">🏨</div>
            <h3 style="color: var(--text-main); margin-bottom: 10px;">Ready to find your perfect stay?</h3>
            <p>Select your preferences above and click "Find Hotels" to see personalized recommendations</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the base URL for hotel details
    const baseUrl = '{{ url("/hotels") }}';
    
    // Handle recommendation type selection
    const recTypeBtns = document.querySelectorAll('.rec-type-btn');
    const recTypeInput = document.getElementById('recommendationType');
    
    recTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            recTypeBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            // Update hidden input value
            recTypeInput.value = this.dataset.type;
        });
    });

    // Function to navigate to hotel details
    function navigateToHotel(hotelId) {
        if (hotelId) {
            window.location.href = `${baseUrl}/${hotelId}`;
        }
    }

    // Form submission
    document.getElementById('hotelFilterForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const btnText = btn.querySelector('.btn-text');
        const btnLoading = btn.querySelector('.btn-loading');
        const grid = document.getElementById('hotelGrid');
        const resultsCount = document.getElementById('resultsCount');
        const similarityInfo = document.getElementById('similarityInfo');
        
        // UI Loading State
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        similarityInfo.style.display = 'none';
        
        // Show skeleton loaders
        grid.innerHTML = `
            ${Array(6).fill(0).map(() => `
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div style="padding: 20px;">
                        <div style="height: 20px; background: #f0f0f0; margin-bottom: 10px; border-radius: 4px;"></div>
                        <div style="height: 15px; background: #f0f0f0; width: 70%; margin-bottom: 15px; border-radius: 4px;"></div>
                        <div style="height: 40px; background: #f0f0f0; border-radius: 4px;"></div>
                    </div>
                </div>
            `).join('')}
        `;

        // Build Payload
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        // Convert price to number if provided
        if (data.price_per_night && data.price_per_night.trim()) {
            data.price_per_night = parseFloat(data.price_per_night);
        } else {
            delete data.price_per_night;
        }
        
        // Convert rating to number
        if (data.rating_score) {
            data.rating_score = parseFloat(data.rating_score);
        }

        try {
            // Call Flask hotel recommendation engine
            const response = await fetch('http://127.0.0.1:5000/recommend-hotels', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseData = await response.json();
            
            // Check response format
            if (responseData.recommendations) {
                renderHotels(responseData.recommendations);
                resultsCount.textContent = `${responseData.recommendations.length} hotels`;
                
                // Show similarity metadata if available
                if (responseData.metadata && responseData.metadata.avg_similarity) {
                    similarityInfo.textContent = `🎯 Avg. Match: ${responseData.metadata.avg_similarity} (Threshold: ${responseData.metadata.similarity_threshold_used})`;
                    similarityInfo.style.display = 'inline-block';
                }
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error("Hotel Recommendation Error:", error);
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">😕</div>
                    <h3 style="color: #ef4444; margin-bottom: 10px;">Connection Error</h3>
                    <p style="color: var(--text-muted);">Unable to fetch hotel recommendations at the moment.</p>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 15px;">Please check if the recommendation server is running and try again.</p>
                    <button onclick="location.reload()" style="margin-top: 20px; padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">🔄 Retry</button>
                </div>
            `;
            resultsCount.textContent = `0 hotels`;
            similarityInfo.style.display = 'none';
        } finally {
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    });

    function renderHotels(hotels) {
        const grid = document.getElementById('hotelGrid');
        
        if (!hotels || hotels.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🔍</div>
                    <h3 style="color: var(--text-main); margin-bottom: 10px;">No hotels found</h3>
                    <p style="color: var(--text-muted);">Try adjusting your filters for more results.</p>
                    <button onclick="document.getElementById('hotelFilterForm').reset()" style="margin-top: 20px; padding: 10px 20px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer;">🔄 Reset Filters</button>
                </div>
            `;
            return;
        }

        grid.innerHTML = hotels.map(hotel => {
            // Determine badge based on rating and price tier
            let badgeText = '';
            let badgeClass = '';
            
            if (hotel.rating_score >= 4.5) {
                badgeText = '⭐ Top Rated';
                badgeClass = 'top-rated';
            } else if (hotel.price_tier === 'luxury') {
                badgeText = '💎 Luxury';
                badgeClass = 'luxury';
            } else if (hotel.price_tier === 'premium') {
                badgeText = '✨ Premium';
                badgeClass = 'premium';
            } else if (hotel.popularity >= 4.0) {
                badgeText = '🔥 Popular';
                badgeClass = 'popular';
            }
            
            // Format price display
            const priceDisplay = hotel.price_per_night ? 
                `${hotel.price_per_night}` : 
                'Contact for price';
            
            // Get hotel ID
            const hotelId = hotel.id || '';
            
            return `
                <div class="hotel-card" onclick="navigateToHotel('${hotelId}')" data-hotel-id="${hotelId}">
                    <div class="hotel-image-container">
                        ${badgeText ? `<div class="hotel-badge ${badgeClass}">${badgeText}</div>` : ''}
                        <img src="${hotel.hotel_image || 'https://via.placeholder.com/400x220?text=Hotel+Image'}" 
                             class="hotel-image" 
                             alt="${hotel.hotel_name || 'Hotel'}"
                             onerror="this.src='https://via.placeholder.com/400x220?text=Image+Not+Available'">
                    </div>
                    <div class="hotel-content">
                        <h3 class="hotel-title">${hotel.hotel_name || 'Unnamed Hotel'}</h3>
                        <div class="hotel-location">
                            📍 ${hotel.address || 'Location not specified'}
                        </div>
                        <div class="rating-badge">
                            ⭐ ${hotel.rating_score || 'N/A'} 
                            ${hotel.review_count ? `(${hotel.review_count} reviews)` : ''}
                        </div>
                        <div class="hotel-details">
                            ${hotel.room_type ? `<span class="detail-chip">🛏️ ${hotel.room_type}</span>` : ''}
                            ${hotel.bed_info ? `<span class="detail-chip">🛌 ${hotel.bed_info}</span>` : ''}
                            ${hotel.distance_from_beach ? `<span class="detail-chip">🏖️ ${hotel.distance_from_beach}</span>` : ''}
                            ${hotel.distance_from_center ? `<span class="detail-chip">🚗 ${hotel.distance_from_center}</span>` : ''}
                            ${hotel.nearby_landmark ? `<span class="detail-chip">📍 ${hotel.nearby_landmark}</span>` : ''}
                        </div>
                        ${hotel.description ? `<p style="color: var(--text-muted); font-size: 13px; margin: 10px 0; line-height: 1.4;">${hotel.description.substring(0, 100)}${hotel.description.length > 100 ? '...' : ''}</p>` : ''}
                        <div class="price-section">
                            <div class="price">
                                ${priceDisplay}
                                <small>/night</small>
                            </div>
                            <div class="view-details">
                                View Details →
                            </div>
                        </div>
                        ${hotel.similarity_percentage ? `<div style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Match: ${hotel.similarity_percentage}%</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Make navigateToHotel globally available
    window.navigateToHotel = function(hotelId) {
        if (hotelId) {
            window.location.href = `{{ url("/hotels") }}/${hotelId}`;
        }
    };

    // Add click event listeners for hotel cards
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.hotel-card');
        if (card && !e.target.closest('a')) {
            const hotelId = card.dataset.hotelId;
            if (hotelId) {
                window.location.href = `{{ url("/hotels") }}/${hotelId}`;
            }
        }
    });

    // Optional: Load initial recommendations on page load
    async function loadInitialHotels() {
        try {
            const response = await fetch('http://127.0.0.1:5000/recommend-hotels', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({}) // Empty params for default recommendations
            });
            
            const responseData = await response.json();
            
            if (responseData.recommendations && responseData.recommendations.length > 0) {
                renderHotels(responseData.recommendations);
                document.getElementById('resultsCount').textContent = `${responseData.recommendations.length} hotels`;
                
                if (responseData.metadata && responseData.metadata.avg_similarity) {
                    const similarityInfo = document.getElementById('similarityInfo');
                    similarityInfo.textContent = `🎯 Avg. Match: ${responseData.metadata.avg_similarity}`;
                    similarityInfo.style.display = 'inline-block';
                }
            }
        } catch (error) {
            console.log('Using default empty state');
        }
    }

    // Uncomment to load initial hotels on page load
    // loadInitialHotels();
});
</script>
@endsection