@extends('layout.admin')

@section('title', $restaurant->restaurant_name)

@section('content')
<div class="detail-container">
    <div class="detail-header">
        <div>
            <h1>{{ $restaurant->restaurant_name }}</h1>
            <div class="detail-meta">
                <span class="meta-badge">{{ $restaurant->food_type ?? 'Various' }}</span>
                <span class="meta-badge price">{{ $restaurant->price_tier ?? '$$' }}</span>
                <span class="rating"><i class="ri-star-fill"></i> {{ $restaurant->rating ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="detail-actions">
            <a href="{{ route('admin.restaurants.edit', $restaurant) }}" class="btn-primary">
                <i class="ri-edit-line"></i> Edit
            </a>
            <a href="{{ route('admin.restaurants.index') }}" class="btn-secondary">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>
    </div>
    
    <div class="detail-content">
        <div class="detail-image">
            <img src="{{ $restaurant->image ?? '/images/placeholder-restaurant.jpg' }}" alt="{{ $restaurant->restaurant_name }}">
        </div>
        
        <div class="detail-info">
            <div class="info-section">
                <h3>Location</h3>
                <p><i class="ri-map-pin-line"></i> {{ $restaurant->location }}</p>
            </div>
            
            <div class="info-section">
                <h3>Contact Information</h3>
                @if($restaurant->phone_number)
                    <p><i class="ri-phone-line"></i> {{ $restaurant->phone_number }}</p>
                @endif
                @if($restaurant->website)
                    <p><i class="ri-global-line"></i> <a href="{{ $restaurant->website }}" target="_blank">{{ $restaurant->website }}</a></p>
                @endif
                @if($restaurant->directory_url)
                    <p><i class="ri-menu-line"></i> <a href="{{ $restaurant->directory_url }}" target="_blank">View Menu</a></p>
                @endif
            </div>
            
            <div class="info-section">
                <h3>Opening Hours</h3>
                <p>{{ nl2br($restaurant->opening_hours ?? 'Not specified') }}</p>
            </div>
            
            <div class="info-section">
                <h3>Description</h3>
                <p>{{ $restaurant->description ?? 'No description available.' }}</p>
            </div>
            
            @if($restaurant->tags)
            <div class="info-section">
                <h3>Features</h3>
                <div class="tags-list">
                    @foreach($restaurant->tags_array as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection