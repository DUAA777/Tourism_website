@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
<style>
    :root {
        --resto-bg: #f6f7fb;
        --resto-card-bg: #ffffff;
        --resto-text-dark: #1b1b1f;
        --resto-text-soft: #6f7380;
        --resto-primary: #ff6b2c;
        --resto-primary-dark: #e85d22;
        --resto-success: #10b981;
        --resto-warning: #f59e0b;
        --resto-info: #3b82f6;
        --resto-purple: #8b5cf6;
        --resto-shadow: 0 20px 50px rgba(16, 24, 40, 0.08);
        --resto-shadow-hover: 0 30px 60px rgba(16, 24, 40, 0.12);
        --resto-radius: 30px;
        --resto-radius-lg: 22px;
        --resto-radius-md: 16px;
        --resto-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body { 
        background-color: var(--resto-bg); 
        font-family: 'DM Sans', sans-serif; 
    }

    /* Hero Section */
    .resto-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        padding: 80px 20px;
        text-align: center;
        color: white;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }

    .resto-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="rgba(255,255,255,0.03)" d="M50 0L61.8 38.2L100 50L61.8 61.8L50 100L38.2 61.8L0 50L38.2 38.2L50 0z"/></svg>') repeat;
        opacity: 0.3;
    }

    .resto-hero h1 {
        font-size: 56px;
        font-weight: 800;
        margin-bottom: 16px;
        position: relative;
        letter-spacing: -0.02em;
    }

    .resto-hero p {
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
        background: var(--resto-card-bg);
        border-radius: var(--resto-radius);
        box-shadow: var(--resto-shadow);
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
        color: var(--resto-text-dark); 
        margin-bottom: 12px;
        letter-spacing: -0.01em;
    }
    
    .filter-header p {
        color: var(--resto-text-soft);
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
        color: var(--resto-text-dark);
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: var(--resto-radius-md);
        background-color: #fafbfc;
        font-size: 14px;
        color: var(--resto-text-dark);
        transition: var(--resto-transition);
        font-family: inherit;
        cursor: pointer;
    }

    .filter-group select:focus, 
    .filter-group input:focus {
        border-color: var(--resto-primary);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(255, 107, 44, 0.1);
        outline: none;
    }

    .filter-group select:hover,
    .filter-group input:hover {
        border-color: #d1d5db;
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
        color: var(--resto-text-dark);
        font-weight: 600;
        cursor: pointer;
        transition: var(--resto-transition);
        flex: 1;
        min-width: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
    }

    .rec-type-btn:hover {
        border-color: var(--resto-primary);
        transform: translateY(-2px);
    }

    .rec-type-btn.active {
        background: var(--resto-primary);
        color: white;
        border-color: var(--resto-primary);
    }

    .rec-type-btn.smart.active { background: var(--resto-primary); border-color: var(--resto-primary); }
    .rec-type-btn.popular.active { background: var(--resto-warning); border-color: var(--resto-warning); }
    .rec-type-btn.rated.active { background: var(--resto-success); border-color: var(--resto-success); }
    .rec-type-btn.diverse.active { background: var(--resto-purple); border-color: var(--resto-purple); }

    .submit-btn {
        grid-column: span 4;
        background: linear-gradient(135deg, var(--resto-primary) 0%, var(--resto-primary-dark) 100%);
        color: white;
        padding: 16px 24px;
        border: none;
        border-radius: var(--resto-radius-md);
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        transition: var(--resto-transition);
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

    /* RESULTS GRID */
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
        color: var(--resto-text-dark);
        margin: 0;
        letter-spacing: -0.01em;
    }

    .results-count {
        background: #f1f5f9;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
        color: var(--resto-text-dark);
    }

    .similarity-info {
        background: #fef3e8;
        padding: 6px 14px;
        border-radius: 40px;
        font-size: 12px;
        color: var(--resto-primary);
        font-weight: 500;
    }

    .restaurant-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 32px;
    }

    .res-card {
        background: white;
        border-radius: var(--resto-radius-lg);
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: var(--resto-transition);
        position: relative;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .res-card:hover { 
        transform: translateY(-8px); 
        box-shadow: var(--resto-shadow-hover); 
        border-color: transparent;
    }

    .res-image-container {
        position: relative;
        overflow: hidden;
        height: 240px;
    }

    .res-image { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        transition: transform 0.5s ease;
    }

    .res-card:hover .res-image {
        transform: scale(1.08);
    }

    .res-badge {
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

    .res-badge.top-rated {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
    }

    .res-badge.popular {
        background: linear-gradient(135deg, var(--resto-info), #2563eb);
    }

    .res-content { 
        padding: 24px; 
    }
    
    .res-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .res-title { 
        font-size: 20px; 
        font-weight: 800; 
        color: var(--resto-text-dark); 
        margin: 0;
        line-height: 1.3;
        flex: 1;
    }
    
    .rating-score {
        font-weight: 800;
        background: #fef3e8;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 13px;
        color: var(--resto-primary);
        white-space: nowrap;
        margin-left: 12px;
    }
    
    .res-meta { 
        font-size: 14px; 
        color: var(--resto-text-soft); 
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .price-indicator {
        display: inline-block;
        padding: 2px 8px;
        background: #f1f5f9;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        color: var(--resto-text-dark);
    }

    .match-percentage {
        display: inline-block;
        padding: 2px 8px;
        background: #fef3e8;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        color: var(--resto-primary);
    }

    .tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }

    .tag-pill {
        display: inline-block;
        padding: 6px 12px;
        background: #f8f9fc;
        color: var(--resto-text-soft);
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        transition: var(--resto-transition);
    }

    .tag-pill:hover {
        background: #fef3e8;
        color: var(--resto-primary);
    }

    .food-type-pill {
        background: #fef3e8;
        color: var(--resto-primary);
        font-weight: 600;
    }

    .res-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
    }

    .view-details-hint {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--resto-primary);
        font-weight: 600;
        font-size: 14px;
        transition: var(--resto-transition);
    }

    .res-card:hover .view-details-hint {
        gap: 12px;
    }

    .contact-info {
        color: var(--resto-text-soft);
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Loading State */
    .loading-container {
        grid-column: 1/-1;
        padding: 60px 20px;
        text-align: center;
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid #f1f5f9;
        border-top-color: var(--resto-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .loading-text {
        color: var(--resto-text-soft);
        font-size: 14px;
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
        color: var(--resto-text-dark);
        margin-bottom: 12px;
    }

    .empty-message {
        color: var(--resto-text-soft);
        margin-bottom: 24px;
    }

    .reset-btn {
        padding: 12px 28px;
        background: var(--resto-primary);
        color: white;
        border: none;
        border-radius: var(--resto-radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: var(--resto-transition);
    }

    .reset-btn:hover {
        background: var(--resto-primary-dark);
        transform: translateY(-2px);
    }

    /* Error State */
    .error-state {
        grid-column: 1/-1;
        text-align: center;
        padding: 80px 20px;
    }

    .error-icon {
        font-size: 64px;
        margin-bottom: 24px;
    }

    .error-title {
        font-size: 24px;
        font-weight: 700;
        color: #ef4444;
        margin-bottom: 12px;
    }

    .error-message {
        color: var(--resto-text-soft);
        margin-bottom: 24px;
    }

    /* Responsive Design */
    @media (max-width: 1000px) {
        .filter-form { grid-template-columns: repeat(2, 1fr); }
        .rec-type-selector, .submit-btn { grid-column: span 2; }
        .resto-hero h1 { font-size: 40px; }
        .resto-hero { padding: 60px 20px; }
        .restaurant-grid { gap: 24px; }
    }

    @media (max-width: 640px) {
        .filter-grid-container { padding: 24px; }
        .filter-form { grid-template-columns: 1fr; }
        .rec-type-selector, .submit-btn { grid-column: span 1; }
        .rec-type-selector { flex-direction: column; }
        .rec-type-btn { width: 100%; }
        .resto-hero h1 { font-size: 32px; }
        .resto-hero p { font-size: 14px; }
        .results-header { flex-direction: column; align-items: flex-start; }
        .results-header h3 { font-size: 24px; }
        .restaurant-grid { gap: 20px; }
        .res-title { font-size: 18px; }
        .res-header { flex-direction: column; gap: 8px; }
        .rating-score { margin-left: 0; align-self: flex-start; }
    }
</style>
@endpush

@section('content')

<!-- Filter Section -->
<div class="filter-grid-container">
    <div class="filter-header">
        <h2>Smart Restaurant Recommendations</h2>
        <p>Tell us what you're looking for and let us find your ideal dining experience</p>
    </div>
    
    <form id="aiFilterForm" class="filter-form">
        <div class="filter-group">
            <label>Cuisine Type</label>
            <select name="food_type">
                <option value="">All Cuisines</option>
                <option value="lebanese">Lebanese</option>
                <option value="italian">Italian</option>
                <option value="japanese">Japanese</option>
                <option value="seafood">Seafood</option>
                <option value="bbq-grill">BBQ & Grill</option>
                <option value="french">French</option>
                <option value="asian">Asian Fusion</option>
                <option value="mexican">Mexican</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Location</label>
            <select name="location">
                <option value="">All Locations</option>
                <option value="beirut">Beirut</option>
                <option value="batroun">Batroun</option>
                <option value="byblos">Byblos</option>
                <option value="jounieh">Jounieh</option>
                <option value="tripoli">Tripoli</option>
                <option value="tyre">Tyre</option>
                <option value="sidon">Sidon</option>
                <option value="zahleh">Zahle</option>
                <option value="deir el qamar">Deir El Qamar</option>
                <option value="faraya">Faraya</option>
                
            </select>
        </div>

        <div class="filter-group">
            <label>Price Range</label>
            <select name="price_tier">
                <option value="">Any Price</option>
                <option value="budget">Budget</option>
                <option value="mid-range">Mid-range</option>
                <option value="premium">Premium</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Minimum Rating</label>
            <select name="rating">
                <option value="0">Any Rating</option>
                <option value="4.5">4.5+ Stars</option>
                <option value="4.0">4.0+ Stars</option>
                <option value="3.5">3.5+ Stars</option>
                <option value="3.0">3.0+ Stars</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Dining Setting</label>
            <select name="restaurant_type">
                <option value="">Any Setting</option>
                <option value="outdoor">Outdoor</option>
                <option value="indoor">Indoor</option>
                <option value="rooftop">Rooftop</option>
                <option value="garden">Garden</option>
                <option value="beachfront">Beachfront</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Atmosphere</label>
            <select name="tags">
                <option value="">All Atmospheres</option>
                <option value="romantic">Romantic</option>
                <option value="beach">Beach & Sea</option>
                <option value="family-friendly">Family Friendly</option>
                <option value="live-music">Live Music</option>
                <option value="upscale">Upscale</option>
                <option value="casual">Casual Dining</option>
                <option value="business">Business Lunch</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Search Keywords</label>
            <input type="text" name="custom_query" placeholder="e.g., sushi, terrace, wifi..." />
        </div>

        <div class="filter-group">
            <label>Sort By</label>
            <select name="sort_by">
                <option value="smart">Smart (Recommended)</option>
                <option value="rating">Highest Rated</option>
                <option value="popular">Most Popular</option>
                <option value="diverse">Diverse Selection</option>
            </select>
        </div>

        <!-- Hidden field for recommendation type -->
        <input type="hidden" name="recommendation_type" id="recommendationType" value="smart">

        <button type="submit" class="submit-btn" id="submitBtn">
            <span class="btn-text">Find Restaurants</span>
            <span class="btn-loading" style="display:none;">Finding the best spots...</span>
        </button>
    </form>
</div>

<!-- Results Section -->
<div class="results-container">
    <div class="results-header">
        <h3>Recommended for You</h3>
        <div class="results-stats">
            <span class="results-count" id="resultsCount">0 restaurants</span>
            <span class="similarity-info" id="similarityInfo" style="display: none;"></span>
        </div>
    </div>
    <div id="restaurantGrid" class="restaurant-grid">
        <!-- Results will be loaded here -->
        <div class="empty-state">
            <div class="empty-icon">🍽️</div>
            <div class="empty-title">Ready to find your perfect dining experience?</div>
            <div class="empty-message">Select your preferences above and click "Find Restaurants" to see personalized recommendations</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '{{ url("/restaurants") }}';
    
    // Map sort_by to recommendation_type
    const sortBySelect = document.querySelector('select[name="sort_by"]');
    const recTypeInput = document.getElementById('recommendationType');
    
    if (sortBySelect && recTypeInput) {
        sortBySelect.addEventListener('change', function() {
            const mapping = {
                'smart': 'smart',
                'rating': 'highly_rated',
                'popular': 'popular',
                'diverse': 'diverse'
            };
            recTypeInput.value = mapping[this.value] || 'smart';
        });
    }

    function navigateToRestaurant(restaurantId) {
        if (restaurantId) {
            window.location.href = `${baseUrl}/${restaurantId}`;
        }
    }

    // Form submission
    const form = document.getElementById('aiFilterForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            const grid = document.getElementById('restaurantGrid');
            const resultsCount = document.getElementById('resultsCount');
            const similarityInfo = document.getElementById('similarityInfo');
            
            // UI Loading State
            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            similarityInfo.style.display = 'none';
            
            grid.innerHTML = `
                <div class="loading-container">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">Analyzing restaurants matching your preferences...</div>
                </div>
            `;

            // Build Payload
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            // Add custom query to tags if present
            if (data.custom_query && data.custom_query.trim()) {
                data.tags = data.tags ? data.tags + ',' + data.custom_query : data.custom_query;
            }

            try {
                const response = await fetch('http://127.0.0.1:5000/recommend', {
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
                    renderResults(responseData.recommendations);
                    resultsCount.textContent = `${responseData.recommendations.length} restaurants`;
                    
                    if (responseData.metadata && responseData.metadata.avg_similarity) {
                        similarityInfo.textContent = `Average Match: ${responseData.metadata.avg_similarity}`;
                        similarityInfo.style.display = 'inline-block';
                    }
                } else if (Array.isArray(responseData)) {
                    renderResults(responseData);
                    resultsCount.textContent = `${responseData.length} restaurants`;
                } else {
                    throw new Error('Invalid response format');
                }
            } catch (error) {
                console.error("AI Service Error:", error);
                grid.innerHTML = `
                    <div class="error-state">
                        <div class="error-icon">⚠️</div>
                        <div class="error-title">Connection Error</div>
                        <div class="error-message">Unable to fetch restaurant recommendations at the moment. Please check if the recommendation server is running and try again.</div>
                        <button onclick="location.reload()" class="reset-btn">Try Again</button>
                    </div>
                `;
                resultsCount.textContent = `0 restaurants`;
                similarityInfo.style.display = 'none';
            } finally {
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        });
    }

    function renderResults(restaurants) {
        const grid = document.getElementById('restaurantGrid');
        
        if (!restaurants || restaurants.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <div class="empty-title">No matches found</div>
                    <div class="empty-message">Try adjusting your filters for more results.</div>
                    <button onclick="document.getElementById('aiFilterForm').reset()" class="reset-btn">Reset Filters</button>
                </div>
            `;
            return;
        }

        grid.innerHTML = restaurants.map(item => {
            const tags = item.tags ? item.tags.split(',').slice(0, 4) : [];
            
            const priceSymbol = item.price_tier === 'budget' ? 'Budget' : 
                               item.price_tier === 'mid-range' ? 'Mid-range' : 
                               item.price_tier === 'premium' ? 'Premium' : '';
            
            const restaurantId = item.id || '';
            const isTopRated = item.rating >= 4.5;
            const isPopular = item.is_fallback;
            
            return `
                <div class="res-card" onclick="navigateToRestaurant('${restaurantId}')" data-restaurant-id="${restaurantId}">
                    <div class="res-image-container">
                        ${isTopRated ? '<div class="res-badge top-rated">Top Rated</div>' : ''}
                        ${isPopular && !isTopRated ? '<div class="res-badge popular">Popular</div>' : ''}
                        <img src="${item.image || 'https://via.placeholder.com/400x240?text=Restaurant'}" 
                             class="res-image" 
                             alt="${item.restaurant_name || 'Restaurant'}"
                             onerror="this.src='https://via.placeholder.com/400x240?text=Image+Not+Available'">
                    </div>
                    <div class="res-content">
                        <div class="res-header">
                            <h3 class="res-title">${item.restaurant_name || 'Unnamed Restaurant'}</h3>
                            <span class="rating-score">${item.rating || 'N/A'} ★</span>
                        </div>
                        <div class="res-meta">
                            <span>📍 ${item.location || 'Location not specified'}</span>
                            ${priceSymbol ? `<span class="price-indicator">${priceSymbol}</span>` : ''}
                            ${item.similarity_percentage ? `<span class="match-percentage">Match: ${item.similarity_percentage}%</span>` : ''}
                        </div>
                        <div class="tags-container">
                            ${tags.map(tag => `<span class="tag-pill">${tag.trim()}</span>`).join('')}
                            ${item.food_type ? `<span class="tag-pill food-type-pill">${item.food_type}</span>` : ''}
                        </div>
                        <div class="res-footer">
                            <span class="view-details-hint">
                                View Details →
                            </span>
                            ${item.phone_number ? `<span class="contact-info">📞 ${item.phone_number}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.navigateToRestaurant = navigateToRestaurant;

    // Delegate click events for restaurant cards
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.res-card');
        if (card && !e.target.closest('a')) {
            const restaurantId = card.dataset.restaurantId;
            if (restaurantId) {
                window.location.href = `${baseUrl}/${restaurantId}`;
            }
        }
    });
});
</script>
@endsection