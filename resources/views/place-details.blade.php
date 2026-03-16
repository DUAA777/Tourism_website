@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/places.css') }}">
@endpush

@section('content')
<section class="place-details-hero">
    <div class="place-details-hero__image">
        <img src="{{ asset($place['cover']) }}" alt="{{ $place['name'] }}">
    </div>

    <div class="place-details-hero__content">
        <a href="{{ route('places.index') }}" class="back-link"><i class="ri-arrow-left-line"></i> Back to Places</a>

        <div class="place-details-meta">
            <span class="place-type">{{ ucfirst($place['type']) }}</span>
            <span class="place-rating"><i class="ri-star-fill"></i> {{ $place['rating'] }}</span>
        </div>

        <h1>{{ $place['name'] }}</h1>
        <p class="place-location"><i class="ri-map-pin-2-fill"></i> {{ $place['location'] }}</p>
        <p class="place-details-description">{{ $place['description'] }}</p>

        <div class="place-feature-list">
            @foreach($place['features'] as $feature)
                <span class="feature-pill">{{ $feature }}</span>
            @endforeach
        </div>

        <div class="place-price-box">
            <span>Starting from</span>
            <strong>{{ $place['price'] }}</strong>
        </div>
    </div>
</section>

<section class="place-gallery-section">
    <h2>Gallery</h2>
    <div class="place-gallery-grid">
        @foreach($place['gallery'] as $image)
            <div class="place-gallery-item">
                <img src="{{ asset($image) }}" alt="{{ $place['name'] }}">
            </div>
        @endforeach
    </div>
</section>
@endsection