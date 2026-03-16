@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
<style>
    :root {
        --primary: #6366f1;
        --primary-hover: #4f46e5;
        --bg-main: #f8fafc;
        --bg-card: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --accent: #f59e0b;
        --success: #10b981;
        --info: #3b82f6;
    }

    body { 
        background-color: var(--bg-main); 
        font-family: 'Inter', sans-serif; 
    }

    .filter-grid-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 35px;
        background: var(--bg-card);
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
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
    }

    .filter-group select, .filter-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        background-color: #f8fafc;
        font-size: 14px;
        color: var(--text-main);
        appearance: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-group select:focus, .filter-group input:focus {
        border-color: var(--primary);
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }

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
    }

    .rec-type-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .rec-type-btn.smart.active { background: var(--primary); }
    .rec-type-btn.popular.active { background: #f59e0b; border-color: #f59e0b; }
    .rec-type-btn.rated.active { background: #10b981; border-color: #10b981; }
    .rec-type-btn.diverse.active { background: #8b5cf6; border-color: #8b5cf6; }

    .submit-btn {
        grid-column: span 4;
        background: var(--primary);
        color: white;
        padding: 16px;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .submit-btn:hover { 
        background: var(--primary-hover); 
        transform: translateY(-2px); 
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    }
    
    .submit-btn:disabled { 
        opacity: 0.7; 
        cursor: not-allowed; 
        transform: none;
    }

    /* RESULTS GRID */
    .results-container {
        max-width: 1200px;
        margin: 40px auto 60px;
        padding: 0 20px;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .results-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-main);
    }

    .results-count {
        background: #e2e8f0;
        padding: 6px 12px;
        border-radius: 40px;
        font-size: 14px;
        font-weight: 600;
    }

    .restaurant-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
    }

    .res-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }

    .res-card:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
    }

    .res-image { 
        width: 100%; 
        height: 200px; 
        object-fit: cover; 
        background: #eee; 
    }

    .res-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: var(--accent);
        color: white;
        padding: 5px 12px;
        border-radius: 40px;
        font-size: 12px;
        font-weight: 700;
        z-index: 2;
    }

    .res-content { 
        padding: 20px; 
    }
    
    .res-title { 
        font-size: 18px; 
        font-weight: 800; 
        color: var(--text-main); 
        margin-bottom: 5px; 
    }
    
    .res-meta { 
        font-size: 13px; 
        color: var(--text-muted); 
        margin-bottom: 15px; 
    }
    
    .tag-pill {
        display: inline-block;
        padding: 4px 10px;
        background: #eef2ff;
        color: var(--primary);
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        margin-right: 5px;
        margin-bottom: 5px;
    }

    .price-indicator {
        display: inline-block;
        padding: 2px 8px;
        background: #f1f5f9;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-main);
    }

    .res-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f1f5f9;
    }

    .view-btn {
        display: inline-block;
        padding: 8px 16px;
        background: #1e293b;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: background 0.2s ease;
    }

    .view-btn:hover {
        background: #0f172a;
    }

    .loading-skeleton {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }

    @media (max-width: 1000px) {
        .filter-form { grid-template-columns: repeat(2, 1fr); }
        .rec-type-selector, .submit-btn { grid-column: span 2; }
    }

    @media (max-width: 600px) {
        .filter-form { grid-template-columns: 1fr; }
        .rec-type-selector, .submit-btn { grid-column: span 1; }
        .rec-type-selector { flex-direction: column; }
    }
</style>
@endpush

@section('content')
<div class="filter-grid-container">
    <div class="filter-header">
        <h2>🍽️ Find Your Perfect Spot</h2>
        <p>40+ smart recommendations tailored to your taste</p>
    </div>
    
    <form id="aiFilterForm" class="filter-form">
        <div class="filter-group">
            <label>🍕 Food Type</label>
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
            <label>📍 Location</label>
            <select name="location">
                <option value="">All Locations</option>
                <option value="beirut">Beirut</option>
                <option value="batroun">Batroun</option>
                <option value="byblos">Byblos</option>
                <option value="jounieh">Jounieh</option>
                <option value="tripoli">Tripoli</option>
                <option value="tyre">Tyre</option>
            </select>
        </div>

        <div class="filter-group">
            <label>💰 Price Range</label>
            <select name="price_tier">
                <option value="">Any Price</option>
                <option value="budget">Budget ($)</option>
                <option value="mid-range">Mid-range ($$)</option>
                <option value="premium">Premium ($$$)</option>
            </select>
        </div>

        <div class="filter-group">
            <label>⭐ Minimum Rating</label>
            <select name="rating">
                <option value="0">Any Rating</option>
                <option value="4.5">4.5+ Stars</option>
                <option value="4.0">4.0+ Stars</option>
                <option value="3.5">3.5+ Stars</option>
                <option value="3.0">3.0+ Stars</option>
            </select>
        </div>

        <div class="filter-group">
            <label>🏠 Setting</label>
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
            <label>🎭 Vibe & Tags</label>
            <select name="tags">
                <option value="">All Vibes</option>
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
            <label>🔍 Search (optional)</label>
            <input type="text" name="custom_query" placeholder="e.g., sushi, terrace, wifi..." />
        </div>

        <div class="filter-group">
            <label>🎯 Sort By</label>
            <select name="sort_by">
                <option value="smart">Smart (Recommended)</option>
                <option value="rating">Highest Rated</option>
                <option value="popular">Most Popular</option>
                <option value="diverse">Diverse Selection</option>
            </select>
        </div>

        <!-- Hidden field for recommendation type (mapped from sort_by) -->
        <input type="hidden" name="recommendation_type" id="recommendationType" value="smart">

        <button type="submit" class="submit-btn" id="submitBtn">
            <span class="btn-text">🔍 Generate AI Recommendations</span>
            <span class="btn-loading" style="display:none;">⏳ Finding perfect spots...</span>
        </button>
    </form>
</div>

<div class="results-container">
    <div class="results-header">
        <h3>✨ Top Picks For You</h3>
        <span class="results-count" id="resultsCount">0 restaurants</span>
    </div>
    <div id="restaurantGrid" class="restaurant-grid">
        <!-- Results will be loaded here -->
        <div style="grid-column: 1/-1; text-align:center; padding: 60px; color: var(--text-muted);">
            Select your preferences and click "Discover" to see personalized recommendations
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Map sort_by to recommendation_type
    const sortBySelect = document.querySelector('select[name="sort_by"]');
    const recTypeInput = document.getElementById('recommendationType');
    
    sortBySelect.addEventListener('change', function() {
        const mapping = {
            'smart': 'smart',
            'rating': 'highly_rated',
            'popular': 'popular',
            'diverse': 'diverse'
        };
        recTypeInput.value = mapping[this.value] || 'smart';
    });

    // Form submission
    document.getElementById('aiFilterForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const btnText = btn.querySelector('.btn-text');
        const btnLoading = btn.querySelector('.btn-loading');
        const grid = document.getElementById('restaurantGrid');
        const resultsCount = document.getElementById('resultsCount');
        
        // UI Loading State
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        grid.innerHTML = `
            <div style="grid-column: 1/-1; padding: 40px;">
                <div style="text-align:center; color: var(--text-muted);">
                    <div style="font-size: 48px; margin-bottom: 20px;">🔍</div>
                    <div class="loading-skeleton">Analyzing restaurants matching your preferences...</div>
                </div>
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
            // Call Flask recommendation engine
            const response = await fetch('http://127.0.0.1:5000/recommend', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const results = await response.json();
            renderResults(results);
            resultsCount.textContent = `${results.length} restaurants`;
        } catch (error) {
            console.error("AI Service Error:", error);
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding: 60px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">😕</div>
                    <h3 style="color: #ef4444; margin-bottom: 10px;">Connection Error</h3>
                    <p style="color: var(--text-muted);">AI recommendation engine is temporarily unavailable.</p>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 20px;">Please try again in a few moments.</p>
                </div>
            `;
            resultsCount.textContent = `0 restaurants`;
        } finally {
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    });

    function renderResults(list) {
        const grid = document.getElementById('restaurantGrid');
        
        if (!list || list.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding: 60px;">
                    <div style="font-size: 48px; margin-bottom: 20px;">🔍</div>
                    <h3 style="color: var(--text-main); margin-bottom: 10px;">No matches found</h3>
                    <p style="color: var(--text-muted);">Try adjusting your filters for more results.</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = list.map(item => {
            // Handle tags safely
            const tags = item.tags ? item.tags.split(',').slice(0, 4) : [];
            
            // Format price tier
            const priceSymbol = item.price_tier === 'budget' ? '$' : 
                               item.price_tier === 'mid-range' ? '$$' : 
                               item.price_tier === 'premium' ? '$$$' : '';
            
            return `
            <div class="res-card">
                ${item.rating >= 4.5 ? '<div class="res-badge">⭐ Top Rated</div>' : ''}
                <img src="${item.image || 'https://via.placeholder.com/400x200?text=Restaurant'}" 
                     class="res-image" 
                     alt="${item.restaurant_name}"
                     onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
                <div class="res-content">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <h3 class="res-title">${item.restaurant_name || 'Unnamed Restaurant'}</h3>
                        <span style="font-weight:800; color:var(--accent); background: #fef3c7; padding: 4px 8px; border-radius: 20px; font-size: 13px;">
                            ⭐ ${item.rating || 'N/A'}
                        </span>
                    </div>
                    <p class="res-meta">
                        ${item.location || 'Location TBA'} 
                        ${priceSymbol ? '• ' + priceSymbol : ''}
                    </p>
                    <div style="margin-bottom: 15px;">
                        ${tags.map(tag => `<span class="tag-pill">${tag.trim()}</span>`).join('')}
                        ${item.food_type ? `<span class="tag-pill" style="background: #fef3c7; color: #92400e;">${item.food_type}</span>` : ''}
                    </div>
                    <div class="res-footer">
                        <a href="${item.directory_url || '#'}" target="_blank" class="view-btn">
                            View Details →
                        </a>
                        ${item.phone_number ? `<span style="color: var(--text-muted); font-size: 12px;">📞 ${item.phone_number}</span>` : ''}
                    </div>
                </div>
            </div>
        `}).join('');
    }

    // Load initial recommendations
    async function loadInitialRecommendations() {
        try {
            const response = await fetch('http://127.0.0.1:5000/recommend', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({}) // Empty params for default recommendations
            });
            const results = await response.json();
            if (results.length > 0) {
                renderResults(results);
                document.getElementById('resultsCount').textContent = `${results.length} restaurants`;
            }
        } catch (error) {
            console.log('Using default empty state');
        }
    }

    // Uncomment to load initial recommendations on page load
    // loadInitialRecommendations();
});
</script>
@endsection