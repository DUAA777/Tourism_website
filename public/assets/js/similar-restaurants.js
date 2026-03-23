// Similar Restaurants API Client
class SimilarRestaurantsAPI {
    constructor(baseUrl = 'http://127.0.0.1:5000') {
        this.baseUrl = baseUrl;
    }

    /**
     * Get similar restaurants based on restaurant ID
     * @param {number} restaurantId - The ID of the restaurant
     * @param {number} limit - Number of similar restaurants to return (default: 6)
     * @returns {Promise} - Promise with similar restaurants data
     */
    async getSimilarRestaurants(restaurantId, limit = 6) {
        try {
            const response = await fetch(
                `${this.baseUrl}/similar/${restaurantId}?limit=${limit}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching similar restaurants:', error);
            throw error;
        }
    }

    /**
     * Get similar restaurants using criteria-based matching
     * @param {number} restaurantId - The ID of the restaurant
     * @param {number} limit - Number of similar restaurants to return
     * @returns {Promise} - Promise with similar restaurants data
     */
    async getSimilarByCriteria(restaurantId, limit = 6) {
        try {
            const response = await fetch(
                `${this.baseUrl}/similar/${restaurantId}/by-criteria?limit=${limit}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching similar restaurants by criteria:', error);
            throw error;
        }
    }

    /**
     * Render similar restaurants into a container
     * @param {Array} restaurants - Array of restaurant objects
     * @param {string} containerId - ID of the container element
     * @param {string} baseUrl - Base URL for restaurant details page
     */
    renderSimilarRestaurants(restaurants, containerId, baseUrl = '/restaurants') {
        const container = document.getElementById(containerId);
        
        if (!container) {
            console.error(`Container with ID "${containerId}" not found`);
            return;
        }

        if (!restaurants || restaurants.length === 0) {
            container.innerHTML = `
                <div class="no-similar-results">
                    <p>No similar restaurants found at the moment.</p>
                </div>
            `;
            return;
        }

        let html = '';
        
        restaurants.forEach(restaurant => {
            // Format price tier
            const priceSymbol = restaurant.price_tier === 'budget' ? '$' : 
                               restaurant.price_tier === 'mid-range' ? '$$' : 
                               restaurant.price_tier === 'premium' ? '$$$' : '';
            
            // Handle tags
            const tags = restaurant.tags ? restaurant.tags.split(',').slice(0, 3) : [];
            
            html += `
                <a href="${baseUrl}/${restaurant.id}" class="similar-restaurant-card">
                    <div class="similar-restaurant__image">
                        <img src="${restaurant.image || 'https://via.placeholder.com/400x200?text=Restaurant'}" 
                             alt="${restaurant.restaurant_name}"
                             onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
                        <span class="restaurant-rating-badge">
                            <i class="ri-star-fill"></i> ${restaurant.rating || 'N/A'}
                        </span>
                    </div>
                    <div class="similar-restaurant__content">
                        <h3>${restaurant.restaurant_name || 'Unnamed Restaurant'}</h3>
                        <p class="similar-restaurant__location">
                            <i class="ri-map-pin-line"></i> ${restaurant.location || 'Location TBA'}
                        </p>
                        <div class="similar-restaurant__meta">
                            <span class="food-type">${restaurant.food_type || 'Various'}</span>
                            <span class="price-tier">
                                ${priceSymbol}
                            </span>
                        </div>
                        ${tags.length > 0 ? `
                            <div class="similar-restaurant__tags">
                                ${tags.map(tag => `<span class="tag-pill">${tag.trim()}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </a>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * Load and render similar restaurants dynamically
     * @param {number} restaurantId - The ID of the current restaurant
     * @param {string} containerId - ID of the container element
     * @param {number} limit - Number of similar restaurants to show
     */
    async loadAndRenderSimilar(restaurantId, containerId, limit = 6) {
        const container = document.getElementById(containerId);
        
        if (!container) return;

        // Show loading state
        container.innerHTML = `
            <div class="loading-similar">
                <div class="loading-spinner"></div>
                <p>Finding similar restaurants...</p>
            </div>
        `;

        try {
            // Try the main similar endpoint first
            const data = await this.getSimilarRestaurants(restaurantId, limit);
            
            if (data.similar_restaurants && data.similar_restaurants.length > 0) {
                this.renderSimilarRestaurants(data.similar_restaurants, containerId);
            } else {
                // If no results, try the criteria-based endpoint
                const criteriaData = await this.getSimilarByCriteria(restaurantId, limit);
                
                if (criteriaData.similar_restaurants && criteriaData.similar_restaurants.length > 0) {
                    this.renderSimilarRestaurants(criteriaData.similar_restaurants, containerId);
                } else {
                    container.innerHTML = `
                        <div class="no-similar-results">
                            <p>No similar restaurants found.</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Failed to load similar restaurants:', error);
            container.innerHTML = `
                <div class="error-similar">
                    <p>Unable to load similar restaurants at this time.</p>
                    <button onclick="location.reload()" class="retry-btn">Retry</button>
                </div>
            `;
        }
    }
}

// Initialize the API client
const similarRestaurantsAPI = new SimilarRestaurantsAPI();