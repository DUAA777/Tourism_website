@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}">
@endpush

@section('content')

<section class="home-hero">
    <div class="home-hero__overlay"></div>

    <div class="home-hero__content">
        <div class="home-hero__text">
            <h2 class="home-hero__brand-top">Yalla Nemshi</h2>
            <p class="home-hero__eyebrow">Discover Lebanon</p>
            <h1>Unlock Your Travel Dreams With Us!</h1>
            <p class="home-hero__desc">
                Discover destinations, build smart itineraries, and explore Lebanon
                with a cleaner, easier, and more inspiring planning experience.
            </p>
            <div class="home-hero__actions">
                <a href="{{ route('chatbot') }}" class="hero-btn hero-btn--primary">Plan My Trip</a>
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

<section class="finder-section" data-search-url="{{ route('search.destinations') }}">
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

    @if(session('review_success'))
        <div class="review-alert review-alert--success">{{ session('review_success') }}</div>
    @endif

    @if(session('review_error'))
        <div class="review-alert review-alert--error">{{ session('review_error') }}</div>
    @endif

    @php
        $shouldOpenReviewForm = $errors->has('rating') || $errors->has('review_text');
    @endphp

    @auth
        @if(!$userReview)
            <details class="review-compose" {{ $shouldOpenReviewForm ? 'open' : '' }}>
                <summary class="review-compose__toggle">
                    <span class="review-compose__left">
                        <i class="ri-edit-2-line"></i>
                        Add Your Review
                    </span>
                    <i class="ri-arrow-down-s-line review-compose__chevron"></i>
                </summary>
                <div class="review-compose__panel">
                    <form action="{{ route('reviews.store') }}" method="POST" class="review-form">
                        @csrf
                        <div class="review-form__field">
                            <label for="rating">Your Rating</label>
                            <select id="rating" name="rating" required>
                                <option value="">Select rating</option>
                                @for($score = 5; $score >= 1; $score--)
                                    <option value="{{ $score }}" {{ old('rating') == $score ? 'selected' : '' }}>
                                        {{ $score }} Star{{ $score > 1 ? 's' : '' }}
                                    </option>
                                @endfor
                            </select>
                            @error('rating')
                                <p class="review-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="review-form__field">
                            <label for="review_text">Your Review</label>
                            <textarea
                                id="review_text"
                                name="review_text"
                                rows="4"
                                placeholder="Share your experience..."
                                required>{{ old('review_text') }}</textarea>
                            @error('review_text')
                                <p class="review-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="hero-btn hero-btn--primary review-submit-btn">Submit Review</button>
                    </form>
                </div>
            </details>
        @else
            <p class="review-login-hint">
                You already shared a review. Thank you for your feedback.
            </p>
        @endif
    @else
        <p class="review-login-hint">
            Want to share your experience?
            <a href="{{ route('login') }}">Log in</a>
            to add a review.
        </p>
    @endauth

    <div class="reviews-grid">
        @forelse($reviews as $review)
            @php
                $reviewerName = $review->user->name ?? 'Traveler';
                $reviewerInitial = strtoupper(substr($reviewerName, 0, 1));
            @endphp
            <article class="review-card">
                <div class="review-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="{{ $i <= $review->rating ? 'ri-star-fill' : 'ri-star-line' }}"></i>
                    @endfor
                </div>
                <p class="review-text">{{ $review->review_text }}</p>
                <div class="review-user">
                    <div class="review-avatar">{{ $reviewerInitial }}</div>
                    <div>
                        <h4>{{ $reviewerName }}</h4>
                        <span>Traveler</span>
                    </div>
                </div>
            </article>
        @empty
            <article class="review-card review-card--empty">
                <p class="review-text">No reviews yet. Be the first to share one.</p>
            </article>
        @endforelse
    </div>
</section>

@push('scripts')
<script src="{{ asset('assets/js/home.js') }}"></script>
@endpush



@endsection
