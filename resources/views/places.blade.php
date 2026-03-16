@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/places.css') }}">
@endpush

@section('content')
<section class="places-hero">
    <div class="places-hero__content">
        <p class="places-hero__eyebrow">Explore Lebanon</p>
        <h1>Discover Hotels, Restaurants, and Attractions</h1>
        <p>
            Browse curated places across Lebanon and open each one for more details,
            images, and travel inspiration.
        </p>
    </div>
</section>

<section class="places-page">
    <div class="places-toolbar">
        <form action="{{ route('places.index') }}" method="GET" class="places-search">
            <span><i class="ri-search-line"></i></span>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search places or cities">
            @if($activeType)
                <input type="hidden" name="type" value="{{ $activeType }}">
            @endif
        </form>

        <div class="places-filters">
            <a href="{{ route('places.index') }}" class="filter-chip {{ !$activeType ? 'active' : '' }}">All</a>
            <a href="{{ route('places.index', ['type' => 'hotel']) }}" class="filter-chip {{ $activeType === 'hotel' ? 'active' : '' }}">Hotels</a>
            <a href="{{ route('places.index', ['type' => 'restaurant']) }}" class="filter-chip {{ $activeType === 'restaurant' ? 'active' : '' }}">Restaurants</a>
            <a href="{{ route('places.index', ['type' => 'attraction']) }}" class="filter-chip {{ $activeType === 'attraction' ? 'active' : '' }}">Attractions</a>
        </div>
    </div>

    <div class="places-grid">
        @foreach($places as $place)
            <article class="place-list-card">
                <a href="{{ route('places.show', $place['slug']) }}" class="place-list-card__image-wrap">
                    <img src="{{ asset($place['cover']) }}" alt="{{ $place['name'] }}">
                </a>

                <div class="place-list-card__body">
                    <div class="place-list-card__meta">
                        <span class="place-type">{{ ucfirst($place['type']) }}</span>
                        <span class="place-rating"><i class="ri-star-fill"></i> {{ $place['rating'] }}</span>
                    </div>

                    <h3>{{ $place['name'] }}</h3>
                    <p class="place-location"><i class="ri-map-pin-2-fill"></i> {{ $place['location'] }}</p>
                    <p class="place-description">{{ $place['short_description'] }}</p>

                    <div class="place-list-card__footer">
                        <span class="place-price">{{ $place['price'] }}</span>
                        <a href="{{ route('places.show', $place['slug']) }}" class="place-view-btn">
                            View Details
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection