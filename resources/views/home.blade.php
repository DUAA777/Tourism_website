@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
@endpush

@section('content')

<section class="home-hero">
    <div class="home-hero__overlay"></div>

    <div class="home-hero__content">
        <div class="home-hero__text">
            <p class="home-hero__eyebrow">Discover Lebanon</p>
            <h1>Unlock Your Travel Dreams With Us!!!</h1>
            <p class="home-hero__desc">
                Discover destinations, build smart itineraries, and explore Lebanon
                with a cleaner, easier, and more inspiring planning experience.
            </p>
            <div class="home-hero__actions">
                <a href="{{ route('chatbot') }}" class="hero-btn hero-btn--primary">Plan My Trip</a>
            </div>
        </div>

        <div class="home-hero__thumbs">
            <div class="home-hero__thumb-title">Popular Places</div>
            <div class="home-hero__thumb-slider">
                <div class="home-hero__thumb-track">
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-1.jpg') }}" alt="Baalbek">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-2.jpg') }}" alt="Sidon">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-3.jpg') }}" alt="Beirut">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/showcase-bg.jpg') }}" alt="Lebanon">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-1.jpg') }}" alt="Baalbek">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-2.jpg') }}" alt="Sidon">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/destination-3.jpg') }}" alt="Beirut">
                    </div>
                    <div class="hero-thumb">
                        <img src="{{ asset('images/showcase-bg.jpg') }}" alt="Lebanon">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-strip">
    <div class="stats-strip__inner">
        <div class="stat-pill">
            <h3 class="stat-number" data-target="10" data-suffix="+">0</h3>
            <p>Years Experience</p>
        </div>
        <div class="stat-pill">
            <h3 class="stat-number" data-target="2000" data-suffix="+">0</h3>
            <p>Happy Travelers</p>
        </div>
        <div class="stat-pill">
            <h3 class="stat-number" data-target="10000" data-suffix="+">0</h3>
            <p>Trips Planned</p>
        </div>
        <div class="stat-pill">
            <h3 class="stat-number" data-target="4.8" data-decimals="1">0</h3>
            <p>Overall Rating</p>
        </div>
    </div>
</section>

<section class="finder-section">
    <div class="finder-section__heading">
        <h2>Find Your Dream Destination</h2>
        <p>
            Discover amazing restaurants and hotels across Lebanon.
        </p>
    </div>

    <div class="finder-toolbar">
        <div class="finder-search">
            <span><i class="ri-map-pin-line"></i></span>
            <input type="text" id="finderSearch" placeholder="Search by name, location, or cuisine...">
        </div>

        <div class="finder-chips">
            <button class="chip chip--active" data-filter="all">All</button>
            <button class="chip" data-filter="restaurants">Restaurants</button>
            <button class="chip" data-filter="hotels">Hotels</button>
        </div>
    </div>

    <div class="finder-grid" id="finderGrid">
        @if(isset($places) && count($places) > 0)
            @foreach($places as $place)
                @php
                    // Determine if it's a restaurant or hotel
                    $isRestaurant = isset($place->restaurant_name);
                    $name = $isRestaurant ? $place->restaurant_name : $place->hotel_name;
                    $image = $isRestaurant ? $place->image : $place->hotel_image;
                    $location = $isRestaurant ? $place->location : $place->address;
                    $type = $isRestaurant ? ($place->restaurant_type ?? 'Restaurant') : 'Hotel';
                    $rating = $isRestaurant ? $place->rating : $place->rating_score;
                    $priceTier = $isRestaurant ? $place->price_tier : $place->price_per_night;
                    $foodType = $isRestaurant ? $place->food_type : null;
                    
                    // Generate route based on type
                    $route = $isRestaurant ? route('restaurants.show', $place->id) : route('hotels.show', $place->id);
                @endphp
                
                <article class="place-card" data-type="{{ $isRestaurant ? 'restaurant' : 'hotel' }}">
                    <a href="{{ $route }}" class="place-card__link">
                        <img src="{{ $image }}" alt="{{ $name }}">
                        <div class="place-card__overlay"></div>
                        <div class="place-card__content">
                            <h4>{{ $name }}</h4>
                            <p><i class="ri-map-pin-2-fill"></i> {{ $location }}</p>
                        </div>
                    </a>
                </article>
            @endforeach
        @else
            <div class="no-results">No destinations available. Please add some restaurants or hotels to the database.</div>
        @endif
    </div>
</section>

<section class="why-us-section">
    <div class="why-us__text">
        <p class="why-us__eyebrow">Why Choose Us</p>
        <h2>Why Should You Choose Us</h2>
        <p class="why-us__desc">
            We combine local insight, clean planning tools, and curated experiences
            to help you travel smarter and discover more of Lebanon.
        </p>

        <div class="why-us__list">
            <div class="why-item">
                <span><i class="ri-checkbox-circle-fill"></i></span>
                <div>
                    <h4>Smart trip planning</h4>
                    <p>Easy route discovery, destination inspiration, and cleaner travel decisions.</p>
                </div>
            </div>

            <div class="why-item">
                <span><i class="ri-checkbox-circle-fill"></i></span>
                <div>
                    <h4>Local-first recommendations</h4>
                    <p>Explore real places, authentic experiences, and memorable local gems.</p>
                </div>
            </div>

            <div class="why-item">
                <span><i class="ri-checkbox-circle-fill"></i></span>
                <div>
                    <h4>Beautiful user experience</h4>
                    <p>A lightweight and modern interface built to make planning enjoyable.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="why-us__image-wrap">
        <img src="{{ asset('images/showcase-bg.jpg') }}" alt="Why choose us">
    </div>
</section>

<section class="plan-cta-section">
    <div class="plan-cta__content">
        <p class="plan-cta__kicker">READY TO GO?</p>
        <h2>Let Yalla Nemshi build your perfect Lebanon day</h2>
        <p>
            Pick your city, mood, budget, and available time — then get a smart plan
            tailored to your vibe.
        </p>

        <div class="plan-cta__actions">
            <a href="{{ route('chatbot') }}" class="hero-btn hero-btn--primary">Plan My Trip</a>
        </div>
    </div>
</section>

<section class="reviews-section">
    <div class="reviews-section__heading">
        <p class="reviews-section__kicker">TRAVELER REVIEWS</p>
        <h2>What people are saying</h2>
        <p>
            See how Yalla Nemshi helps travelers discover better places and plan smoother days out.
        </p>
    </div>

    <div class="reviews-grid">
        <article class="review-card">
            <div class="review-stars">
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
            </div>
            <p class="review-text">
                Yalla Nemshi made planning my Batroun day so much easier. The suggestions actually matched
                my vibe and budget.
            </p>
            <div class="review-user">
                <img src="{{ asset('images/client-1.jpg') }}" alt="Reviewer">
                <div>
                    <h4>Rana K.</h4>
                    <span>Weekend Traveler</span>
                </div>
            </div>
        </article>

        <article class="review-card">
            <div class="review-stars">
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
            </div>
            <p class="review-text">
                I liked how clean everything felt. Instead of wasting time deciding where to go, I got a
                full idea in minutes.
            </p>
            <div class="review-user">
                <img src="{{ asset('images/client-2.jpg') }}" alt="Reviewer">
                <div>
                    <h4>Karim M.</h4>
                    <span>City Explorer</span>
                </div>
            </div>
        </article>

        <article class="review-card">
            <div class="review-stars">
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
                <i class="ri-star-fill"></i>
            </div>
            <p class="review-text">
                Super helpful for finding places I didn’t know about before. It feels modern, simple, and
                actually useful.
            </p>
            <div class="review-user">
                <img src="{{ asset('images/client-3.jpg') }}" alt="Reviewer">
                <div>
                    <h4>Lina S.</h4>
                    <span>Food & Nature Lover</span>
                </div>
            </div>
        </article>
    </div>
</section>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chips = document.querySelectorAll('.chip');
    const searchInput = document.getElementById('finderSearch');
    const grid = document.getElementById('finderGrid');
    
    let activeFilter = 'all';
    let searchTimeout;

    function filterCards() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        
        // Show loading state
        grid.innerHTML = '<div class="loading-spinner"><i class="ri-loader-4-line"></i> Loading destinations...</div>';
        
        // Make AJAX request to search
        fetch(`{{ route('search.destinations') }}?search=${encodeURIComponent(searchTerm)}&filter=${activeFilter}`)
            .then(response => response.json())
            .then(places => {
                if (places.length === 0) {
                    grid.innerHTML = '<div class="no-results"><i class="ri-emotion-sad-line"></i> No destinations found. Try a different search!</div>';
                    return;
                }
                
                // Render the results
                grid.innerHTML = places.map(place => {
                    const isRestaurant = place.restaurant_name !== undefined;
                    const name = isRestaurant ? place.title : place.title;
                    const image = isRestaurant ? place.image : place.image;
                    console.log(place)
                    const route = isRestaurant ? 
                        `/restaurants/${place.id}` : 
                        `/hotels/${place.id}`;
                    return `
                        <article class="place-card" data-type="${isRestaurant ? 'restaurant' : 'hotel'}">
                            <a href="${route}" class="place-card__link">
                                <img src="${image || 'images/default-place.jpg'}" alt="${name}">
                                <div class="place-card__overlay"></div>
                                <div class="place-card__content">
                                    <h4>${name}</h4>
                                </div>
                            </a>
                        </article>
                    `;
                }).join('');
            })
            .catch(error => {
                console.error('Error:', error);
                grid.innerHTML = '<div class="error-message"><i class="ri-error-warning-line"></i> Something went wrong. Please try again.</div>';
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            chips.forEach(btn => btn.classList.remove('chip--active'));
            this.classList.add('chip--active');
            activeFilter = this.dataset.filter;
            filterCards();
        });
    });
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterCards, 300); // Debounce search
    });
});
</script>


<script src="{{ asset('assets/js/home.js') }}"></script>
@endpush

