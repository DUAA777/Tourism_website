@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
<style>
    :root {
        --hotel-bg: #f6f7fb;
        --hotel-card-bg: #ffffff;
        --hotel-text-dark: #1b1b1f;
        --hotel-text-soft: #6f7380;
        --hotel-primary: #ff6b2c;
        --hotel-primary-dark: #e85d22;
        --hotel-success: #10b981;
        --hotel-warning: #f59e0b;
        --hotel-info: #3b82f6;
        --hotel-purple: #8b5cf6;
        --hotel-shadow: 0 20px 50px rgba(16, 24, 40, 0.08);
        --hotel-shadow-hover: 0 30px 60px rgba(16, 24, 40, 0.12);
        --hotel-radius: 30px;
        --hotel-radius-lg: 22px;
        --hotel-radius-md: 16px;
        --hotel-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body { 
        background-color: var(--hotel-bg); 
        font-family: 'DM Sans', sans-serif; 
    }

    /* Hero Section */
    .hotel-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        padding: 80px 20px;
        text-align: center;
        color: white;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }

    .hotel-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="rgba(255,255,255,0.03)" d="M50 0L61.8 38.2L100 50L61.8 61.8L50 100L38.2 61.8L0 50L38.2 38.2L50 0z"/></svg>') repeat;
        opacity: 0.3;
    }

    .hotel-hero h1 {
        font-size: 56px;
        font-weight: 800;
        margin-bottom: 16px;
        position: relative;
        letter-spacing: -0.02em;
    }

    .hotel-hero p {
        font-size: 18px;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        position: relative;
        line-height: 1.6;
    }

    /* Filter Section */
    .filter-grid-container {
        max-width: 1280px;
        margin: 40px auto 40px;
        padding: 40px;
        background: var(--hotel-card-bg);
        border-radius: var(--hotel-radius);
        box-shadow: var(--hotel-shadow);
        position: relative;
        z-index: 10;
    }

    .filter-header { 
        margin-bottom: 32px; 
        text-align: center; 
    }
    
    .filter-header h2 { 
        font-size: 32px; 
        font-weight: 800; 
        color: var(--hotel-text-dark); 
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }
    
    .filter-header p {
        color: var(--hotel-text-soft);
        font-size: 16px;
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
    }

    .filter-group { 
        display: flex; 
        flex-direction: column; 
    }
    
    .filter-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--hotel-text-dark);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: var(--hotel-radius-md);
        background-color: #fafbfc;
        font-size: 14px;
        color: var(--hotel-text-dark);
        transition: var(--hotel-transition);
        font-family: inherit;
    }

    .filter-group select:focus, 
    .filter-group input:focus {
        border-color: var(--hotel-primary);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(255, 107, 44, 0.1);
        outline: none;
    }

    .filter-group select:hover,
    .filter-group input:hover {
        border-color: #d1d5db;
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
        gap: 16px;
        justify-content: center;
        margin: 20px 0 10px;
        flex-wrap: wrap;
    }

    .rec-type-btn {
        padding: 12px 28px;
        border: 2px solid #e5e7eb;
        border-radius: 40px;
        background: white;
        color: var(--hotel-text-dark);
        font-weight: 600;
        cursor: pointer;
        transition: var(--hotel-transition);
        flex: 1;
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
    }

    .rec-type-btn:hover {
        border-color: var(--hotel-primary);
        transform: translateY(-2px);
    }

    .rec-type-btn.active {
        background: var(--hotel-primary);
        color: white;
        border-color: var(--hotel-primary);
    }

    .rec-type-btn.smart.active { background: var(--hotel-primary); border-color: var(--hotel-primary); }
    .rec-type-btn.popular.active { background: var(--hotel-warning); border-color: var(--hotel-warning); }
    .rec-type-btn.rated.active { background: var(--hotel-success); border-color: var(--hotel-success); }
    .rec-type-btn.diverse.active { background: var(--hotel-purple); border-color: var(--hotel-purple); }

    .submit-btn {
        grid-column: span 4;
        background: linear-gradient(135deg, var(--hotel-primary) 0%, var(--hotel-primary-dark) 100%);
        color: white;
        padding: 16px 24px;
        border: none;
        border-radius: var(--hotel-radius-md);
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        transition: var(--hotel-transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .submit-btn:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 10px 25px rgba(255, 107, 44, 0.3);
    }
    
    .submit-btn:disabled { 
        opacity: 0.7; 
        cursor: not-allowed; 
        transform: none;
    }

    /* Results Section */
    .results-container {
        max-width: 1280px;
        margin: 48px auto 80px;
        padding: 0 20px;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .results-header h3 {
        font-size: 28px;
        font-weight: 800;
        color: var(--hotel-text-dark);
        margin: 0;
        letter-spacing: -0.01em;
    }

    .results-stats {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .results-count {
        background: #f1f5f9;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: var(--hotel-text-dark);
    }

    .similarity-info {
        background: #fef3e8;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 12px;
        color: var(--hotel-primary);
        font-weight: 500;
    }

    /* Hotel Grid */
    .hotel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 32px;
    }

    .hotel-card {
        background: white;
        border-radius: var(--hotel-radius-lg);
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: var(--hotel-transition);
        position: relative;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .hotel-card:hover { 
        transform: translateY(-8px); 
        box-shadow: var(--hotel-shadow-hover); 
        border-color: transparent;
    }

    .hotel-image-container {
        position: relative;
        overflow: hidden;
        height: 240px;
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
        top: 16px;
        right: 16px;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 700;
        z-index: 2;
        backdrop-filter: blur(8px);
        background: rgba(0, 0, 0, 0.8);
        color: white;
    }

    .hotel-badge.luxury {
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
    }

    .hotel-badge.premium {
        background: linear-gradient(135deg, #f59e0b, #ef4444);
    }

    .hotel-badge.popular {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .hotel-badge.top-rated {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
    }

    .hotel-content { 
        padding: 24px; 
    }
    
    .hotel-title { 
        font-size: 20px; 
        font-weight: 800; 
        color: var(--hotel-text-dark); 
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .hotel-location { 
        font-size: 14px; 
        color: var(--hotel-text-soft); 
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .rating-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fef3e8;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 13px;
        color: var(--hotel-primary);
        margin-bottom: 16px;
    }

    .hotel-details {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .detail-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #f8f9fc;
        border-radius: 8px;
        font-size: 12px;
        color: var(--hotel-text-soft);
        font-weight: 500;
    }

    .hotel-description {
        color: var(--hotel-text-soft);
        font-size: 13px;
        margin: 12px 0;
        line-height: 1.5;
    }

    .price-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
    }

    .price {
        font-size: 24px;
        font-weight: 800;
        color: var(--hotel-primary);
    }

    .price small {
        font-size: 13px;
        font-weight: 500;
        color: var(--hotel-text-soft);
    }

    .view-details {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--hotel-primary);
        font-weight: 600;
        font-size: 14px;
        transition: var(--hotel-transition);
    }

    .hotel-card:hover .view-details {
        gap: 12px;
    }

    .match-percentage {
        font-size: 11px;
        color: var(--hotel-text-soft);
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        text-align: right;
    }

    /* Loading State */
    .skeleton-card {
        background: white;
        border-radius: var(--hotel-radius-lg);
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }

    .skeleton-image {
        height: 240px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    .skeleton-content {
        padding: 24px;
    }

    .skeleton-title {
        height: 24px;
        background: #f0f0f0;
        margin-bottom: 12px;
        border-radius: 4px;
        width: 70%;
    }

    .skeleton-location {
        height: 16px;
        background: #f0f0f0;
        margin-bottom: 16px;
        border-radius: 4px;
        width: 50%;
    }

    .skeleton-rating {
        height: 28px;
        background: #f0f0f0;
        margin-bottom: 16px;
        border-radius: 20px;
        width: 30%;
    }

    .skeleton-details {
        height: 32px;
        background: #f0f0f0;
        margin-bottom: 16px;
        border-radius: 8px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Empty State */
    .empty-state {
        grid-column: 1/-1;
        text-align: center;
        padding: 80px 20px;
    }

    .empty-icon {
        font-size: 64px;
        margin-bottom: 24px;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--hotel-text-dark);
        margin-bottom: 12px;
    }

    .empty-message {
        color: var(--hotel-text-soft);
        margin-bottom: 24px;
    }

    .reset-btn {
        padding: 12px 28px;
        background: var(--hotel-primary);
        color: white;
        border: none;
        border-radius: var(--hotel-radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: var(--hotel-transition);
    }

    .reset-btn:hover {
        background: var(--hotel-primary-dark);
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 1000px) {
        .filter-form { grid-template-columns: repeat(2, 1fr); }
        .rec-type-selector, .submit-btn { grid-column: span 2; }
        .hotel-hero h1 { font-size: 40px; }
        .hotel-hero { padding: 60px 20px; }
    }

    @media (max-width: 640px) {
        .filter-grid-container { padding: 24px; }
        .filter-form { grid-template-columns: 1fr; }
        .rec-type-selector, .submit-btn { grid-column: span 1; }
        .rec-type-selector { flex-direction: column; }
        .rec-type-btn { width: 100%; }
        .hotel-hero h1 { font-size: 32px; }
        .hotel-hero p { font-size: 14px; }
        .results-header { flex-direction: column; align-items: flex-start; }
        .results-header h3 { font-size: 24px; }
        .hotel-grid { gap: 20px; }
        .hotel-title { font-size: 18px; }
        .price { font-size: 20px; }
    }
</style>
@endpush

@section('content')


<!-- Filter Section -->
<div class="filter-grid-container">
    <div class="filter-header">
        <h2>Smart Hotel Recommendations</h2>
        <p>Tell us what you're looking for and let us find your ideal stay</p>
    </div>
    
    <form id="hotelFilterForm" class="filter-form">
        <div class="filter-group">
            <label>Room Type</label>
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
            <label>Bed Type</label>
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
            <label>Minimum Rating</label>
            <select name="rating_score">
                <option value="0">Any Rating</option>
                <option value="4.5">4.5+ Stars</option>
                <option value="4.0">4.0+ Stars</option>
                <option value="3.5">3.5+ Stars</option>
                <option value="3.0">3.0+ Stars</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Nearby Landmark</label>
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
            <label>Beach Distance</label>
            <select name="distance_from_beach">
                <option value="">Any Distance</option>
                <option value="beachfront">Beachfront</option>
                <option value="100m">Less than 100m</option>
                <option value="500m">Less than 500m</option>
                <option value="1km">Less than 1km</option>
            </select>
        </div>

        <div class="filter-group">
            <label>City or Area</label>
<select name="address">
    <option value="">All Locations</option>
    
    <optgroup label="Beirut">
        <option value="Beirut">Beirut City</option>
    </optgroup>

    <optgroup label="Mount Lebanon">
        <option value="Baabda">Baabda</option>
        <option value="Aley">Aley</option>
        <option value="Chouf">Chouf</option>
        <option value="Matn">Matn</option>
    </optgroup>

    <optgroup label="Keserwan-Jbeil">
        <option value="Jounieh">Jounieh</option>
        <option value="Byblos">Byblos (Jbeil)</option>
    </optgroup>

    <optgroup label="North Lebanon">
        <option value="Tripoli">Tripoli</option>
        <option value="Batroun">Batroun</option>
        <option value="Zgharta">Zgharta</option>
        <option value="Bcharre">Bcharre</option>
        <option value="Koura">Koura</option>
        <option value="Minieh-Danniyeh">Minieh-Danniyeh</option>
    </optgroup>

    <optgroup label="Akkar">
        <option value="Halba">Halba</option>
        <option value="Akkar">Akkar District</option>
    </optgroup>

    <optgroup label="South Lebanon">
        <option value="Sidon">Sidon (Saida)</option>
        <option value="Tyre">Tyre (Sour)</option>
        <option value="Jezzine">Jezzine</option>
    </optgroup>

    <optgroup label="Nabatieh">
        <option value="Nabatieh">Nabatieh City</option>
        <option value="Bint Jbeil">Bint Jbeil</option>
        <option value="Hasbaya">Hasbaya</option>
        <option value="Marjeyoun">Marjeyoun</option>
    </optgroup>

    <optgroup label="Beqaa">
        <option value="Zahle">Zahle</option>
        <option value="West Beqaa">West Beqaa</option>
        <option value="Rashaya">Rashaya</option>
    </optgroup>

    <optgroup label="Baalbek-Hermel">
        <option value="Baalbek">Baalbek</option>
        <option value="Hermel">Hermel</option>
    </optgroup>
</select>
        </div>

        <div class="filter-group">
            <label>Price per Night</label>
            <div class="price-range-input">
                <input type="number" name="price_per_night" placeholder="Max price (e.g., 150)" step="10">
            </div>
        </div>

        <div class="filter-group">
            <label>Distance from Center</label>
            <select name="distance_from_center">
                <option value="">Any Distance</option>
                <option value="1km">Less than 1km</option>
                <option value="3km">Less than 3km</option>
                <option value="5km">Less than 5km</option>
                <option value="10km">Less than 10km</option>
            </select>
        </div>


        <input type="hidden" name="recommendation_type" id="recommendationType" value="smart">

        <button type="submit" class="submit-btn" id="submitBtn">
            <span class="btn-text">Find Hotels</span>
            <span class="btn-loading" style="display:none;">Finding the best stays...</span>
        </button>
    </form>
</div>

<!-- Results Section -->
<div class="results-container">
    <div class="results-header">
        <h3>Recommended Stays</h3>
        <div class="results-stats">
            <span class="results-count" id="resultsCount">0 hotels</span>
            <span class="similarity-info" id="similarityInfo" style="display: none;"></span>
        </div>
    </div>
    <div id="hotelGrid" class="hotel-grid">
        <!-- Results will be loaded here -->
        <div class="empty-state">
            <div class="empty-icon">🏨</div>
            <div class="empty-title">Ready to find your perfect stay?</div>
            <div class="empty-message">Select your preferences above and click "Find Hotels" to see personalized recommendations</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '{{ url("/hotels") }}';
    
    // Handle recommendation type selection
    const recTypeBtns = document.querySelectorAll('.rec-type-btn');
    const recTypeInput = document.getElementById('recommendationType');
    
    recTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            recTypeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            recTypeInput.value = this.dataset.type;
        });
    });

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
        
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        similarityInfo.style.display = 'none';
        
        // Show skeleton loaders
        grid.innerHTML = `
            ${Array(6).fill(0).map(() => `
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title"></div>
                        <div class="skeleton-location"></div>
                        <div class="skeleton-rating"></div>
                        <div class="skeleton-details"></div>
                    </div>
                </div>
            `).join('')}
        `;

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        if (data.price_per_night && data.price_per_night.trim()) {
            data.price_per_night = parseFloat(data.price_per_night);
        } else {
            delete data.price_per_night;
        }
        
        if (data.rating_score) {
            data.rating_score = parseFloat(data.rating_score);
        }

        try {
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
            
            if (responseData.recommendations) {
                renderHotels(responseData.recommendations);
                resultsCount.textContent = `${responseData.recommendations.length} hotels`;
                
                if (responseData.metadata && responseData.metadata.avg_similarity) {
                    similarityInfo.textContent = `Average Match: ${responseData.metadata.avg_similarity} (Threshold: ${responseData.metadata.similarity_threshold_used})`;
                    similarityInfo.style.display = 'inline-block';
                }
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error("Hotel Recommendation Error:", error);
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <div class="empty-title">Connection Error</div>
                    <div class="empty-message">Unable to fetch hotel recommendations at the moment. Please check if the recommendation server is running and try again.</div>
                    <button onclick="location.reload()" class="reset-btn">Retry</button>
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
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <div class="empty-title">No hotels found</div>
                    <div class="empty-message">Try adjusting your filters for more results.</div>
                    <button onclick="document.getElementById('hotelFilterForm').reset()" class="reset-btn">Reset Filters</button>
                </div>
            `;
            return;
        }

        grid.innerHTML = hotels.map(hotel => {
            let badgeText = '';
            let badgeClass = '';
            
            if (hotel.rating_score >= 4.5) {
                badgeText = 'Top Rated';
                badgeClass = 'top-rated';
            } else if (hotel.price_tier === 'luxury') {
                badgeText = 'Luxury';
                badgeClass = 'luxury';
            } else if (hotel.price_tier === 'premium') {
                badgeText = 'Premium';
                badgeClass = 'premium';
            } else if (hotel.popularity >= 4.0) {
                badgeText = 'Popular';
                badgeClass = 'popular';
            }
            
            const priceDisplay = hotel.price_per_night ? 
                `${hotel.price_per_night}` : 
                'Contact for price';
            
            const hotelId = hotel.id || '';
            
            return `
                <div class="hotel-card" onclick="navigateToHotel('${hotelId}')" data-hotel-id="${hotelId}">
                    <div class="hotel-image-container">
                        ${badgeText ? `<div class="hotel-badge ${badgeClass}">${badgeText}</div>` : ''}
                        <img src="${hotel.hotel_image || 'https://via.placeholder.com/400x240?text=Hotel+Image'}" 
                             class="hotel-image" 
                             alt="${hotel.hotel_name || 'Hotel'}"
                             onerror="this.src='https://via.placeholder.com/400x240?text=Image+Not+Available'">
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
                        ${hotel.description ? `<div class="hotel-description">${hotel.description.substring(0, 100)}${hotel.description.length > 100 ? '...' : ''}</div>` : ''}
                        <div class="price-section">
                            <div class="price">
                                ${priceDisplay}
                                <small>/night</small>
                            </div>
                            <div class="view-details">
                                View Details →
                            </div>
                        </div>
                        ${hotel.similarity_percentage ? `<div class="match-percentage">Match: ${hotel.similarity_percentage}%</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    window.navigateToHotel = navigateToHotel;

    document.addEventListener('click', function(e) {
        const card = e.target.closest('.hotel-card');
        if (card && !e.target.closest('a')) {
            const hotelId = card.dataset.hotelId;
            if (hotelId) {
                window.location.href = `${baseUrl}/${hotelId}`;
            }
        }
    });
});
</script>
@endsection