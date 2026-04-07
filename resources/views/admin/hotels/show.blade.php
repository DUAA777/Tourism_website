@extends('layout.admin')

@section('title', $hotel->hotel_name . ' - Hotel Details')

@section('content')
<div class="detail-container">
    <!-- Header Section -->
    <div class="detail-header">
        <div class="header-left">
            <div class="header-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <i class="ri-arrow-right-s-line"></i>
                <a href="{{ route('admin.hotels.index') }}">Hotels</a>
                <i class="ri-arrow-right-s-line"></i>
                <span>{{ $hotel->hotel_name }}</span>
            </div>
            <h1>{{ $hotel->hotel_name }}</h1>
            <div class="detail-meta">
                @if($hotel->rating_score)
                    <div class="rating-badge">
                        <i class="ri-star-fill"></i>
                        <span>{{ number_format($hotel->rating_score, 1) }}</span>
                        @if($hotel->review_count)
                            <small>({{ $hotel->review_count }} reviews)</small>
                        @endif
                    </div>
                @endif
                @if($hotel->room_type)
                    <span class="meta-badge">
                        <i class="ri-hotel-bed-line"></i> {{ $hotel->room_type }}
                    </span>
                @endif
                @if($hotel->price_per_night)
                    <span class="meta-badge price">
                        <i class="ri-money-dollar-circle-line"></i> {{ $hotel->price_per_night }}/night
                    </span>
                @endif
            </div>
        </div>
        <div class="detail-actions">
            <a href="{{ route('admin.hotels.edit', $hotel) }}" class="btn-primary">
                <i class="ri-edit-line"></i> Edit Hotel
            </a>
            <a href="{{ route('admin.hotels.index') }}" class="btn-secondary">
                <i class="ri-arrow-left-line"></i> Back to List
            </a>
            @if($hotel->trashed())
                <form action="{{ route('admin.hotels.restore', $hotel->id) }}" method="POST" class="inline-form">
                    @csrf
                    <button type="submit" class="btn-success">
                        <i class="ri-restart-line"></i> Restore
                    </button>
                </form>
                <form action="{{ route('admin.hotels.force-delete', $hotel->id) }}" method="POST" class="inline-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger" onclick="return confirm('Permanently delete this hotel? This cannot be undone.')">
                        <i class="ri-delete-bin-7-line"></i> Delete Permanently
                    </button>
                </form>
            @else
                <button type="button" class="btn-danger" id="deleteBtn">
                    <i class="ri-delete-bin-line"></i> Move to Trash
                </button>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="detail-content-grid">
        <!-- Left Column - Images -->
        <div class="detail-left">
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-image-line"></i>
                    <h3>Hotel Images</h3>
                </div>
                <div class="hotel-images">
                    @if($hotel->hotel_image)
                        <div class="main-image">
                            <img src="{{ asset($hotel->hotel_image) }}" alt="{{ $hotel->hotel_name }}" id="mainHotelImage">
                        </div>
                    @else
                        <div class="no-image">
                            <i class="ri-image-line"></i>
                            <p>No image available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Location Information -->
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-map-pin-line"></i>
                    <h3>Location & Distance</h3>
                </div>
                <div class="info-list">
                    @if($hotel->address)
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value">{{ $hotel->address }}</span>
                        </div>
                    @endif
                    @if($hotel->distance_from_center)
                        <div class="info-row">
                            <span class="info-label">Distance from Center:</span>
                            <span class="info-value">{{ $hotel->distance_from_center }}</span>
                        </div>
                    @endif
                    @if($hotel->distance_from_beach)
                        <div class="info-row">
                            <span class="info-label">Distance from Beach:</span>
                            <span class="info-value">{{ $hotel->distance_from_beach }}</span>
                        </div>
                    @endif
                    @if($hotel->nearby_landmark)
                        <div class="info-row">
                            <span class="info-label">Nearby Landmark:</span>
                            <span class="info-value">{{ $hotel->nearby_landmark }}</span>
                        </div>
                    @endif
                </div>
                @if($hotel->address)
                    <div class="map-placeholder">
                        <a href="https://maps.google.com/?q={{ urlencode($hotel->address) }}" target="_blank" class="btn-map">
                            <i class="ri-map-2-line"></i> View on Google Maps
                        </a>
                    </div>
                @endif
            </div>

            <!-- Amenities -->
            @if($hotel->getAmenitiesListAttribute() && count($hotel->amenities_list) > 0)
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-star-smile-line"></i>
                    <h3>Amenities</h3>
                </div>
                <div class="amenities-list">
                    @foreach($hotel->amenities_list as $amenity)
                        <span class="amenity-tag">
                            <i class="ri-checkbox-circle-line"></i> {{ $amenity }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Tags -->
            @if(($hotel->vibe_tags && count($hotel->vibe_tags) > 0) || ($hotel->audience_tags && count($hotel->audience_tags) > 0))
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-price-tag-3-line"></i>
                    <h3>Tags</h3>
                </div>
                @if($hotel->vibe_tags && count($hotel->vibe_tags) > 0)
                    <div class="tags-section">
                        <h4>Vibe Tags</h4>
                        <div class="tags-list">
                            @foreach($hotel->vibe_tags as $tag)
                                <span class="tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($hotel->audience_tags && count($hotel->audience_tags) > 0)
                    <div class="tags-section">
                        <h4>Audience Tags</h4>
                        <div class="tags-list">
                            @foreach($hotel->audience_tags as $tag)
                                <span class="tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            @endif
        </div>

        <!-- Right Column - Details -->
        <div class="detail-right">
            <!-- Description -->
            @if($hotel->description)
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-file-text-line"></i>
                    <h3>Description</h3>
                </div>
                <div class="description-content">
                    <p>{{ $hotel->description }}</p>
                </div>
            </div>
            @endif

            <!-- Room Information -->
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-hotel-bed-line"></i>
                    <h3>Room Information</h3>
                </div>
                <div class="info-grid-2cols">
                    @if($hotel->room_type)
                        <div class="info-item">
                            <span class="info-label">Room Type:</span>
                            <span class="info-value">{{ $hotel->room_type }}</span>
                        </div>
                    @endif
                    @if($hotel->bed_info)
                        <div class="info-item">
                            <span class="info-label">Bed Info:</span>
                            <span class="info-value">{{ $hotel->bed_info }}</span>
                        </div>
                    @endif
                    @if($hotel->stay_details)
                        <div class="info-item full-width">
                            <span class="info-label">Stay Details:</span>
                            <span class="info-value">{{ $hotel->stay_details }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pricing Information -->
            @if($hotel->price_per_night || $hotel->taxes_fees)
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-money-dollar-circle-line"></i>
                    <h3>Pricing Information</h3>
                </div>
                <div class="pricing-details">
                    @if($hotel->price_per_night)
                        <div class="price-item">
                            <span class="price-label">Price per Night:</span>
                            <span class="price-value">{{ $hotel->price_per_night }}</span>
                        </div>
                    @endif
                    @if($hotel->taxes_fees)
                        <div class="price-item">
                            <span class="price-label">Taxes & Fees:</span>
                            <span class="price-value">{{ $hotel->taxes_fees }}</span>
                        </div>
                    @endif
                    @if($hotel->price_per_night && $hotel->taxes_fees)
                        <div class="price-item total">
                            <span class="price-label">Total per Night:</span>
                            <span class="price-value">{{ $hotel->total_price }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Review Information -->
            @if($hotel->review_text || $hotel->review_count)
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-chat-1-line"></i>
                    <h3>Reviews</h3>
                </div>
                @if($hotel->review_text)
                    <div class="review-content">
                        <div class="review-quote">
                            <i class="ri-double-quotes-L"></i>
                            <p>{{ $hotel->review_text }}</p>
                            <i class="ri-double-quotes-R"></i>
                        </div>
                        @if($hotel->review_count)
                            <div class="review-stats">
                                <span class="review-count">Based on {{ $hotel->review_count }} reviews</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            @endif

            <!-- External Links -->
            @if($hotel->hotel_url)
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-external-link-line"></i>
                    <h3>External Links</h3>
                </div>
                <div class="links-list">
                    <a href="{{ $hotel->hotel_url }}" target="_blank" class="external-link">
                        <i class="ri-global-line"></i>
                        <span>Visit Hotel Website</span>
                        <i class="ri-external-link-line"></i>
                    </a>
                </div>
            </div>
            @endif

            <!-- Meta Information -->
            <div class="info-card">
                <div class="card-header">
                    <i class="ri-information-line"></i>
                    <h3>Meta Information</h3>
                </div>
                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Created:</span>
                        <span class="meta-value">{{ $hotel->created_at->format('F j, Y, g:i a') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Last Updated:</span>
                        <span class="meta-value">{{ $hotel->updated_at->format('F j, Y, g:i a') }}</span>
                    </div>
                    @if($hotel->deleted_at)
                        <div class="meta-item">
                            <span class="meta-label">Deleted:</span>
                            <span class="meta-value text-warning">{{ $hotel->deleted_at->format('F j, Y, g:i a') }}</span>
                        </div>
                    @endif
                    <div class="meta-item">
                        <span class="meta-label">ID:</span>
                        <span class="meta-value">#{{ $hotel->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Move to Trash</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to move <strong>{{ $hotel->hotel_name }}</strong> to trash?</p>
            <p class="text-warning">You can restore it later from the trash section.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary modal-cancel">Cancel</button>
            <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-warning">Move to Trash</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete modal
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteModal = document.getElementById('deleteModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCancel = document.querySelector('.modal-cancel');
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'flex';
        });
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });
    }
    
    if (modalCancel) {
        modalCancel.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
    });
});
</script>
@endpush