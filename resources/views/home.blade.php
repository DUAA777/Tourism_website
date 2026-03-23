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
            <h1>Unlock Your Travel Dreams With Us</h1>
            <p class="home-hero__desc">
                Discover destinations, build smart itineraries, and explore Lebanon
                with a cleaner, easier, and more inspiring planning experience.
            </p>
<div class="home-hero__actions">
    <a href="{{ route('chatbot') }}" class="hero-btn hero-btn--primary">Plan My Trip</a>

    <a href="{{ route('places.index') }}" class="hero-btn hero-btn--ghost">
        Browse Destinations
    </a>
<!-- 
    <a href="{{ route('aboutUs') }}" class="hero-btn hero-btn--ghost">
        Learn More
    </a> -->
</div>
        </div>

        <div class="home-hero__thumbs">
            <!-- <div class="home-hero__thumb-title">Popular Places</div> -->

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
            Browse by places, mood, and travel style to discover your next Lebanese escape.
        </p>
    </div>

    <div class="finder-toolbar">
        <div class="finder-search">
            <span><i class="ri-map-pin-line"></i></span>
            <input type="text" id="finderSearch" placeholder="Search destinations, cities, or vibes">
        </div>

        <div class="finder-chips">
            <button class="chip chip--active" data-filter="all">All</button>
            <button class="chip" data-filter="beach">Beach</button>
            <button class="chip" data-filter="nature">Nature</button>
            <button class="chip" data-filter="historic">Historic</button>
            <button class="chip" data-filter="food">Food</button>
            <button class="chip" data-filter="city">City</button>
        </div>
    </div>

    <div class="finder-grid" id="finderGrid">

        <article class="place-card" data-category="historic" data-search="baalbek historic temple">
            <a href="{{ route('places.show', 'roman-temple-baalbek') }}" class="place-card__link">
                <img src="{{ asset('images/destination-1.jpg') }}" alt="Baalbek">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Baalbek</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Baalbek, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="beach" data-search="sidon sea castle beach coast">
            <a href="{{ route('places.show', 'sidon-sea-castle') }}" class="place-card__link">
                <img src="{{ asset('images/destination-2.jpg') }}" alt="Sidon">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Sidon</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Sidon, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="city" data-search="downtown beirut city">
            <a href="{{ route('places.show', 'downtown-beirut') }}" class="place-card__link">
                <img src="{{ asset('images/destination-3.jpg') }}" alt="Beirut">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Downtown Beirut</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Beirut, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="nature" data-search="mountain escape north lebanon nature">
            <a href="{{ route('places.show', 'mountain-escape') }}" class="place-card__link">
                <img src="{{ asset('images/showcase-bg.jpg') }}" alt="Mountain escape">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Mountain Escape</h4>
                    <p><i class="ri-map-pin-2-fill"></i> North Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="beach" data-search="coastal sunset batroun beach">
            <a href="{{ route('places.show', 'coastal-sunset-batroun') }}" class="place-card__link">
                <img src="{{ asset('images/destination-2.jpg') }}" alt="Coastal sunset">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Coastal Sunset</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Batroun, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="historic" data-search="temple view beqaa historic">
            <a href="{{ route('places.show', 'temple-view-beqaa') }}" class="place-card__link">
                <img src="{{ asset('images/destination-1.jpg') }}" alt="Temple view">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Temple View</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Beqaa, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="city" data-search="city lights beirut nightlife city">
            <a href="{{ route('places.show', 'city-lights-beirut') }}" class="place-card__link">
                <img src="{{ asset('images/destination-3.jpg') }}" alt="City lights">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>City Lights</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Beirut, Lebanon</p>
                </div>
            </a>
        </article>

        <article class="place-card" data-category="nature" data-search="nature retreat chouf nature forest">
            <a href="{{ route('places.show', 'nature-retreat-chouf') }}" class="place-card__link">
                <img src="{{ asset('images/showcase-bg.jpg') }}" alt="Nature retreat">
                <div class="place-card__overlay"></div>
                <div class="place-card__content">
                    <h4>Nature Retreat</h4>
                    <p><i class="ri-map-pin-2-fill"></i> Chouf, Lebanon</p>
                </div>
            </a>
        </article>

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
            <a href="{{ route('places.index') }}" class="hero-btn hero-btn--ghost">Browse Places</a>
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chips = document.querySelectorAll('.chip');
    const cards = document.querySelectorAll('.place-card');
    const searchInput = document.getElementById('finderSearch');

    let activeFilter = 'all';

    function filterCards() {
        const searchTerm = searchInput.value.trim().toLowerCase();

        cards.forEach(card => {
            const category = card.dataset.category;
            const searchText = card.dataset.search;

            const matchesFilter = activeFilter === 'all' || category === activeFilter;
            const matchesSearch = !searchTerm || searchText.includes(searchTerm);

            card.style.display = (matchesFilter && matchesSearch) ? 'block' : 'none';
        });
    }

    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            chips.forEach(btn => btn.classList.remove('chip--active'));
            this.classList.add('chip--active');
            activeFilter = this.dataset.filter;
            filterCards();
        });
    });

    searchInput.addEventListener('input', filterCards);
});

@push('scripts')
<script src="{{ asset('assets/js/home.js') }}"></script>
@endpush
</script>
@endpush