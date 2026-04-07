@extends('layout.admin')

@section('title', 'Edit Hotel - ' . $hotel->hotel_name)

@section('content')
<div class="edit-hotel-container">
    <div class="page-header">
        <div class="header-title">
            <h1>
                <i class="ri-hotel-line"></i> 
                Edit Hotel
            </h1>
            <p class="text-muted">Update hotel information and manage details for "{{ $hotel->hotel_name }}"</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.hotels.show', $hotel) }}" class="btn-secondary">
                <i class="ri-eye-line"></i> View
            </a>
            <a href="{{ route('admin.hotels.index') }}" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hotels.update', $hotel) }}" enctype="multipart/form-data" id="hotelForm">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button type="button" class="tab-btn active" data-tab="basic">
                <i class="ri-information-line"></i> Basic Information
            </button>
            <button type="button" class="tab-btn" data-tab="location">
                <i class="ri-map-pin-line"></i> Location & Distance
            </button>
            <button type="button" class="tab-btn" data-tab="rooms">
                <i class="ri-hotel-bed-line"></i> Rooms & Pricing
            </button>
            <button type="button" class="tab-btn" data-tab="media">
                <i class="ri-image-line"></i> Media & Links
            </button>
            <button type="button" class="tab-btn" data-tab="tags">
                <i class="ri-price-tag-3-line"></i> Tags & Metadata
            </button>
        </div>
        
        <!-- Tab 1: Basic Information -->
        <div class="form-card tab-content active" id="tab-basic">
            <div class="card-header">
                <i class="ri-information-line"></i>
                <h3>Basic Information</h3>
            </div>
            
            <div class="card-body">
                <div class="form-group">
                    <label for="hotel_name">Hotel Name <span class="required">*</span></label>
                    <div class="input-icon">
                        <i class="ri-hotel-line"></i>
                        <input type="text" id="hotel_name" name="hotel_name" 
                               value="{{ old('hotel_name', $hotel->hotel_name) }}" 
                               class="@error('hotel_name') is-invalid @enderror" 
                               placeholder="e.g., Grand Plaza Hotel" required>
                    </div>
                    @error('hotel_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6" 
                              placeholder="Describe the hotel, amenities, unique features, and what makes it special...">{{ old('description', $hotel->description) }}</textarea>
                    <small class="char-counter">{{ strlen(old('description', $hotel->description)) }}/1000 characters</small>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rating_score">Rating Score (0-5)</label>
                        <div class="rating-input">
                            <input type="number" id="rating_score" name="rating_score" 
                                   step="0.1" min="0" max="5"
                                   value="{{ old('rating_score', $hotel->rating_score) }}">
                            <div class="star-rating" id="ratingStars">
                                @php
                                    $currentRating = old('rating_score', $hotel->rating_score);
                                @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($currentRating))
                                        <i class="ri-star-fill" data-rating="{{ $i }}"></i>
                                    @elseif($i - 0.5 <= $currentRating)
                                        <i class="ri-star-half-fill" data-rating="{{ $i }}"></i>
                                    @else
                                        <i class="ri-star-line" data-rating="{{ $i }}"></i>
                                    @endif
                                @endfor
                            </div>
                        </div>
                        @error('rating_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="review_count">Number of Reviews</label>
                        <div class="input-icon">
                            <i class="ri-chat-3-line"></i>
                            <input type="number" id="review_count" name="review_count" 
                                   value="{{ old('review_count', $hotel->review_count) }}" 
                                   min="0">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Featured Review</label>
                    <textarea id="review_text" name="review_text" rows="3" 
                              placeholder="A featured guest review that highlights the best aspects of this hotel...">{{ old('review_text', $hotel->review_text) }}</textarea>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Location & Distance -->
        <div class="form-card tab-content" id="tab-location" style="display: none;">
            <div class="card-header">
                <i class="ri-map-pin-line"></i>
                <h3>Location Information</h3>
            </div>
            
            <div class="card-body">
                <div class="form-group">
                    <label for="address">Full Address</label>
                    <div class="input-icon">
                        <i class="ri-map-2-line"></i>
                        <input type="text" id="address" name="address" 
                               value="{{ old('address', $hotel->address) }}" 
                               placeholder="Street address, city, state, postal code">
                    </div>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="distance_from_center">Distance from City Center</label>
                        <div class="input-icon">
                            <i class="ri-navigation-line"></i>
                            <input type="text" id="distance_from_center" name="distance_from_center" 
                                   value="{{ old('distance_from_center', $hotel->distance_from_center) }}" 
                                   placeholder="e.g., 2.5 km">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="distance_from_beach">Distance from Beach</label>
                        <div class="input-icon">
                            <i class="ri-umbrella-line"></i>
                            <input type="text" id="distance_from_beach" name="distance_from_beach" 
                                   value="{{ old('distance_from_beach', $hotel->distance_from_beach) }}" 
                                   placeholder="e.g., 500 m">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nearby_landmark">Nearby Landmark</label>
                    <div class="input-icon">
                        <i class="ri-landmark-line"></i>
                        <input type="text" id="nearby_landmark" name="nearby_landmark" 
                               value="{{ old('nearby_landmark', $hotel->nearby_landmark) }}" 
                               placeholder="e.g., Eiffel Tower, Central Park, Colosseum">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Amenities & Facilities</label>
                    <div class="amenities-grid">
                        @php
                            $amenities = old('amenities', is_array($hotel->amenities) ? $hotel->amenities : json_decode($hotel->amenities ?? '[]', true));
                        @endphp
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Free WiFi" {{ in_array('Free WiFi', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-wifi-line"></i> Free WiFi
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Parking" {{ in_array('Parking', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-parking-line"></i> Free Parking
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Breakfast" {{ in_array('Breakfast', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-restaurant-line"></i> Breakfast Included
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Pool" {{ in_array('Pool', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-swim-line"></i> Swimming Pool
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Spa" {{ in_array('Spa', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-spa-line"></i> Spa & Wellness
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Gym" {{ in_array('Gym', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-run-line"></i> Fitness Center
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Restaurant" {{ in_array('Restaurant', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-restaurant-2-line"></i> On-site Restaurant
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Airport Shuttle" {{ in_array('Airport Shuttle', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-bus-line"></i> Airport Shuttle
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Pet Friendly" {{ in_array('Pet Friendly', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-pet-line"></i> Pet Friendly
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="amenities[]" value="Room Service" {{ in_array('Room Service', $amenities) ? 'checked' : '' }}> 
                            <i class="ri-room-service-line"></i> Room Service
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 3: Rooms & Pricing -->
        <div class="form-card tab-content" id="tab-rooms" style="display: none;">
            <div class="card-header">
                <i class="ri-hotel-bed-line"></i>
                <h3>Room Information</h3>
            </div>
            
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <select id="room_type" name="room_type">
                            <option value="">Select Room Type</option>
                            @foreach($roomTypes ?? ['Standard Room', 'Deluxe Room', 'Suite', 'Executive Suite', 'Presidential Suite', 'Family Room', 'Single Room', 'Double Room', 'Twin Room'] as $type)
                                <option value="{{ $type }}" {{ old('room_type', $hotel->room_type) == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bed_info">Bed Information</label>
                        <select id="bed_info" name="bed_info">
                            <option value="">Select Bed Type</option>
                            @foreach($bedTypes ?? ['Single Bed', 'Double Bed', 'Queen Bed', 'King Bed', 'Twin Beds', 'Bunk Bed', 'Sofa Bed'] as $type)
                                <option value="{{ $type }}" {{ old('bed_info', $hotel->bed_info) == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="stay_details">Stay Details</label>
                    <textarea id="stay_details" name="stay_details" rows="3" 
                              placeholder="Check-in time: 2:00 PM&#10;Check-out time: 11:00 AM&#10;Cancellation policy: Free cancellation up to 24 hours before check-in">{{ old('stay_details', $hotel->stay_details) }}</textarea>
                </div>
                
                <div class="divider"></div>
                
                <div class="card-header" style="margin-top: 20px;">
                    <i class="ri-money-dollar-circle-line"></i>
                    <h3>Pricing Information</h3>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_night">Price per Night</label>
                        <div class="input-icon">
                            <i class="ri-money-dollar-box-line"></i>
                            <input type="text" id="price_per_night" name="price_per_night" 
                                   value="{{ old('price_per_night', $hotel->price_per_night) }}" 
                                   placeholder="e.g., $150">
                        </div>
                        <small>Include currency symbol</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="taxes_fees">Taxes & Fees</label>
                        <div class="input-icon">
                            <i class="ri-receipt-line"></i>
                            <input type="text" id="taxes_fees" name="taxes_fees" 
                                   value="{{ old('taxes_fees', $hotel->taxes_fees) }}" 
                                   placeholder="e.g., $25">
                        </div>
                    </div>
                </div>
                
                <div class="price-calculator" id="priceCalculator" style="{{ old('price_per_night', $hotel->price_per_night) || old('taxes_fees', $hotel->taxes_fees) ? 'display: block;' : 'display: none;' }}">
                    <div class="calculator-content">
                        <i class="ri-calculator-line"></i>
                        <strong>Total per night:</strong>
                        <span id="totalPrice">$0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 4: Media & Links -->
        <div class="form-card tab-content" id="tab-media" style="display: none;">
            <div class="card-header">
                <i class="ri-image-line"></i>
                <h3>Hotel Images</h3>
            </div>
            
            <div class="card-body">
                <div class="form-group">
                    <label>Current Hotel Image</label>
                    @if($hotel->hotel_image)
                        <div class="current-image-container">
                            <div class="current-image">
                                <img src="{{ asset($hotel->hotel_image) }}" alt="{{ $hotel->hotel_name }}">
                                <button type="button" class="remove-image-btn" id="removeImageBtn">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="no-image-text">No image uploaded yet</p>
                    @endif
                </div>
                
                <div class="form-group">
                    <label>Upload New Image</label>
                    <div class="image-uploader" id="imageUploader">
                        <input type="file" id="hotel_image" name="hotel_image" accept="image/*" hidden>
                        <div class="upload-area" id="uploadArea">
                            <i class="ri-image-add-line"></i>
                            <p>Click or drag to upload new image</p>
                            <small>PNG, JPG, WEBP (Max 5MB) • Recommended: 1200x800px</small>
                        </div>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                            <button type="button" class="remove-image" id="cancelPreviewBtn">Cancel</button>
                        </div>
                    </div>
                    @error('hotel_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="divider"></div>
                
                <div class="card-header" style="margin-top: 20px;">
                    <i class="ri-link"></i>
                    <h3>External Links</h3>
                </div>
                
                <div class="form-group">
                    <label for="hotel_url">Hotel Website URL</label>
                    <div class="input-icon">
                        <i class="ri-global-line"></i>
                        <input type="url" id="hotel_url" name="hotel_url" 
                               value="{{ old('hotel_url', $hotel->hotel_url) }}" 
                               placeholder="https://hotel-website.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="booking_url">Booking.com URL</label>
                    <div class="input-icon">
                        <i class="ri-bookmark-line"></i>
                        <input type="url" id="booking_url" name="booking_url" 
                               value="{{ old('booking_url', $hotel->booking_url) }}" 
                               placeholder="https://booking.com/hotel/...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tripadvisor_url">TripAdvisor URL</label>
                    <div class="input-icon">
                        <i class="ri-star-line"></i>
                        <input type="url" id="tripadvisor_url" name="tripadvisor_url" 
                               value="{{ old('tripadvisor_url', $hotel->tripadvisor_url) }}" 
                               placeholder="https://tripadvisor.com/hotel/...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 5: Tags & Metadata -->
        <div class="form-card tab-content" id="tab-tags" style="display: none;">
            <div class="card-header">
                <i class="ri-price-tag-3-line"></i>
                <h3>Vibe Tags</h3>
            </div>
            
            <div class="card-body">
                <div class="form-group">
                    <label>What's the atmosphere like?</label>
                    <div class="tags-input-container">
                        <input type="text" id="vibeTagInput" placeholder="Type a tag and press Enter">
                        <input type="hidden" id="vibeTagsHidden" name="vibe_tags" value='{{ json_encode(old('vibe_tags', is_array($hotel->vibe_tags) ? $hotel->vibe_tags : json_decode($hotel->vibe_tags ?? '[]', true))) }}'>
                        <div class="tags-list" id="vibeTagsList">
                            @php
                                $vibeTags = old('vibe_tags', is_array($hotel->vibe_tags) ? $hotel->vibe_tags : json_decode($hotel->vibe_tags ?? '[]', true));
                            @endphp
                            @if(is_array($vibeTags))
                                @foreach($vibeTags as $tag)
                                    <div class="tag">{{ $tag }} <i class="ri-close-line"></i></div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <small>Examples: Luxury, Boutique, Family-friendly, Romantic, Business, Eco-friendly, Budget, Modern, Vintage</small>
                </div>
                
                <div class="divider"></div>
                
                <div class="card-header" style="margin-top: 20px;">
                    <i class="ri-group-line"></i>
                    <h3>Audience Tags</h3>
                </div>
                
                <div class="form-group">
                    <label>Who is this hotel for?</label>
                    <div class="tags-input-container">
                        <input type="text" id="audienceTagInput" placeholder="Type a tag and press Enter">
                        <input type="hidden" id="audienceTagsHidden" name="audience_tags" value='{{ json_encode(old('audience_tags', is_array($hotel->audience_tags) ? $hotel->audience_tags : json_decode($hotel->audience_tags ?? '[]', true))) }}'>
                        <div class="tags-list" id="audienceTagsList">
                            @php
                                $audienceTags = old('audience_tags', is_array($hotel->audience_tags) ? $hotel->audience_tags : json_decode($hotel->audience_tags ?? '[]', true));
                            @endphp
                            @if(is_array($audienceTags))
                                @foreach($audienceTags as $tag)
                                    <div class="tag">{{ $tag }} <i class="ri-close-line"></i></div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <small>Examples: Couples, Families, Solo Travelers, Business Travelers, Groups, Seniors, Digital Nomads</small>
                </div>
                
                <div class="suggested-tags-section">
                    <div class="suggested-label">Quick add suggested tags:</div>
                    <div class="suggested-tags">
                        <button type="button" class="suggested-tag" data-type="vibe">Luxury</button>
                        <button type="button" class="suggested-tag" data-type="vibe">Boutique</button>
                        <button type="button" class="suggested-tag" data-type="vibe">Family-friendly</button>
                        <button type="button" class="suggested-tag" data-type="vibe">Romantic</button>
                        <button type="button" class="suggested-tag" data-type="vibe">Business</button>
                        <button type="button" class="suggested-tag" data-type="vibe">Eco-friendly</button>
                        <button type="button" class="suggested-tag" data-type="audience">Couples</button>
                        <button type="button" class="suggested-tag" data-type="audience">Families</button>
                        <button type="button" class="suggested-tag" data-type="audience">Solo Travelers</button>
                        <button type="button" class="suggested-tag" data-type="audience">Business Travelers</button>
                        <button type="button" class="suggested-tag" data-type="audience">Groups</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <div class="action-buttons">
                <button type="button" class="btn-danger" id="deleteBtn">
                    <i class="ri-delete-bin-line"></i> Move to Trash
                </button>
                <button type="button" class="btn-secondary" onclick="window.history.back()">
                    <i class="ri-close-line"></i> Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <i class="ri-save-line"></i> Update Hotel
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <i class="ri-delete-bin-line"></i>
            <h3>Move to Trash</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to move <strong>{{ $hotel->hotel_name }}</strong> to trash?</p>
            <p class="warning-text">This action can be undone. You can restore it later from the trash section.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary modal-cancel">Cancel</button>
            <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-warning">
                    <i class="ri-delete-bin-line"></i> Move to Trash
                </button>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .edit-hotel-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }

    .header-title h1 {
        font-size: 28px;
        margin: 0 0 5px 0;
        color: #2c3e50;
    }

    .header-title h1 i {
        color: #e74c3c;
        margin-right: 10px;
    }

    .text-muted {
        color: #6c757d;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 12px;
    }

    .btn-back, .btn-secondary {
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-back:hover, .btn-secondary:hover {
        background: #5a6268;
        transform: translateX(-3px);
        color: white;
    }

    /* Tab Navigation */
    .tab-navigation {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        flex-wrap: wrap;
    }

    .tab-btn {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
    }

    .tab-btn i {
        font-size: 18px;
    }

    .tab-btn:hover {
        background: #f8f9fa;
        color: #667eea;
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    /* Form Cards */
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .card-header {
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header i {
        font-size: 20px;
    }

    .card-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .card-body {
        padding: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #2c3e50;
    }

    .required {
        color: #e74c3c;
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .input-icon input {
        width: 100%;
        padding: 10px 10px 10px 35px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .input-icon input:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    select, textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
    }

    textarea {
        resize: vertical;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .divider {
        height: 1px;
        background: #e9ecef;
        margin: 20px 0;
    }

    /* Rating Input */
    .rating-input {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .rating-input input {
        width: 80px;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .star-rating i {
        color: #ffc107;
        font-size: 20px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .star-rating i:hover {
        transform: scale(1.1);
    }

    /* Amenities Grid */
    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .checkbox-label:hover {
        background: #f8f9fa;
    }

    .checkbox-label i {
        color: #667eea;
        font-size: 18px;
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    /* Current Image */
    .current-image-container {
        margin-bottom: 20px;
    }

    .current-image {
        position: relative;
        display: inline-block;
    }

    .current-image img {
        max-width: 300px;
        max-height: 200px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
    }

    .remove-image-btn {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.3s;
    }

    .remove-image-btn:hover {
        transform: scale(1.1);
    }

    .no-image-text {
        color: #6c757d;
        font-style: italic;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    /* Image Uploader */
    .image-uploader {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s;
    }

    .image-uploader.dragover {
        border-color: #667eea;
        background: #f8f9fa;
    }

    .upload-area {
        cursor: pointer;
    }

    .upload-area i {
        font-size: 48px;
        color: #adb5bd;
        margin-bottom: 10px;
    }

    .upload-area p {
        margin: 5px 0;
        color: #6c757d;
    }

    .upload-area small {
        color: #adb5bd;
        font-size: 12px;
    }

    .image-preview {
        margin-top: 15px;
        position: relative;
        display: inline-block;
    }

    .image-preview img {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
    }

    .remove-image {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    /* Tags Input */
    .tags-input-container {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
    }

    .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }

    .tag {
        background: #667eea;
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .tag i {
        cursor: pointer;
        font-size: 14px;
    }

    .tag i:hover {
        opacity: 0.8;
    }

    .tags-input-container input {
        width: 100%;
        border: none;
        padding: 8px 0;
        outline: none;
    }

    .char-counter {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: #6c757d;
    }

    /* Price Calculator */
    .price-calculator {
        margin-top: 15px;
        padding: 15px;
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        border-radius: 8px;
    }

    .calculator-content {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
    }

    .calculator-content i {
        font-size: 24px;
        color: #667eea;
    }

    .calculator-content strong {
        color: #2c3e50;
    }

    .calculator-content span {
        color: #667eea;
        font-size: 20px;
        font-weight: bold;
    }

    /* Suggested Tags */
    .suggested-tags-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }

    .suggested-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
        color: #6c757d;
        font-size: 13px;
    }

    .suggested-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .suggested-tag {
        padding: 6px 12px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
        color: #6c757d;
    }

    .suggested-tag:hover {
        background: #667eea;
        border-color: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* Form Actions */
    .form-actions {
        margin-top: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        display: flex;
        justify-content: flex-end;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .btn-primary {
        padding: 12px 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }

    .btn-danger {
        padding: 12px 30px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220,53,69,0.3);
    }

    .btn-warning {
        padding: 12px 30px;
        background: #ffc107;
        color: #2c3e50;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-warning:hover {
        background: #e0a800;
        transform: translateY(-2px);
    }

    .invalid-feedback {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
    }

    .is-invalid {
        border-color: #e74c3c !important;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-container {
        background: white;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        overflow: hidden;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
    }

    .modal-header h3 {
        margin: 0;
        flex: 1;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        opacity: 0.8;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-body p {
        margin: 0 0 10px 0;
        color: #2c3e50;
    }

    .warning-text {
        color: #ffc107;
        font-size: 14px;
    }

    .modal-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .amenities-grid {
            grid-template-columns: 1fr;
        }
        
        .tab-navigation {
            flex-direction: column;
        }
        
        .tab-btn {
            justify-content: center;
        }
        
        .action-buttons {
            width: 100%;
            flex-direction: column;
        }
        
        .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
            width: 100%;
            justify-content: center;
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .header-actions {
            width: 100%;
        }
        
        .header-actions a {
            flex: 1;
            text-align: center;
            justify-content: center;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            document.getElementById(`tab-${tabId}`).style.display = 'block';
        });
    });
    
    // Character counter for description
    const description = document.getElementById('description');
    const charCounter = document.querySelector('.char-counter');
    
    function updateCharCount() {
        const count = description.value.length;
        charCounter.textContent = `${count}/1000 characters`;
        charCounter.style.color = count > 1000 ? '#e74c3c' : '#6c757d';
    }
    
    if (description) {
        description.addEventListener('input', updateCharCount);
        updateCharCount();
    }
    
    // Rating stars
    const ratingInput = document.getElementById('rating_score');
    const ratingStars = document.getElementById('ratingStars');
    
    if (ratingStars) {
        const stars = ratingStars.querySelectorAll('i');
        
        function updateStars(value) {
            const rating = parseFloat(value) || 0;
            stars.forEach((star, index) => {
                const starValue = index + 1;
                if (starValue <= Math.floor(rating)) {
                    star.className = 'ri-star-fill';
                } else if (starValue - 0.5 <= rating) {
                    star.className = 'ri-star-half-fill';
                } else {
                    star.className = 'ri-star-line';
                }
            });
        }
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                updateStars(rating);
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.className = 'ri-star-fill';
                    } else {
                        s.className = 'ri-star-line';
                    }
                });
            });
        });
        
        if (ratingStars) {
            ratingStars.addEventListener('mouseleave', function() {
                updateStars(ratingInput.value);
            });
        }
        
        if (ratingInput.value) {
            updateStars(ratingInput.value);
        }
        
        ratingInput.addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (isNaN(value)) value = 0;
            value = Math.min(5, Math.max(0, value));
            this.value = value.toFixed(1);
            updateStars(value);
        });
    }
    
    // Price calculator
    const pricePerNight = document.getElementById('price_per_night');
    const taxesFees = document.getElementById('taxes_fees');
    const priceCalculator = document.getElementById('priceCalculator');
    const totalPriceSpan = document.getElementById('totalPrice');
    
    function calculateTotal() {
        let price = parseFloat(pricePerNight.value.replace(/[^0-9.-]/g, '')) || 0;
        let taxes = parseFloat(taxesFees.value.replace(/[^0-9.-]/g, '')) || 0;
        let total = price + taxes;
        
        if (price > 0 || taxes > 0) {
            priceCalculator.style.display = 'block';
            totalPriceSpan.textContent = '$' + total.toFixed(2);
        } else {
            priceCalculator.style.display = 'none';
        }
    }
    
    if (pricePerNight && taxesFees) {
        pricePerNight.addEventListener('input', calculateTotal);
        taxesFees.addEventListener('input', calculateTotal);
        calculateTotal();
    }
    
    // Image upload handling
    const imageUploader = document.getElementById('imageUploader');
    const imageInput = document.getElementById('hotel_image');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const cancelPreviewBtn = document.getElementById('cancelPreviewBtn');
    
    if (imageUploader) {
        uploadArea.addEventListener('click', () => imageInput.click());
        
        imageUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploader.classList.add('dragover');
        });
        
        imageUploader.addEventListener('dragleave', () => {
            imageUploader.classList.remove('dragover');
        });
        
        imageUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploader.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                handleImageFile(file);
            }
        });
        
        imageInput.addEventListener('change', (e) => {
            if (e.target.files[0]) {
                handleImageFile(e.target.files[0]);
            }
        });
    }
    
    function handleImageFile(file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            uploadArea.style.display = 'none';
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
    
    if (cancelPreviewBtn) {
        cancelPreviewBtn.addEventListener('click', () => {
            imagePreview.style.display = 'none';
            uploadArea.style.display = 'block';
            imageInput.value = '';
        });
    }
    
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', () => {
            if (confirm('Remove current image?')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_image';
                hiddenInput.value = '1';
                document.getElementById('hotelForm').appendChild(hiddenInput);
                removeImageBtn.closest('.current-image').remove();
            }
        });
    }
    
    // Tags management system
    function initTagSystem(inputId, listId, hiddenId) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        const hidden = document.getElementById(hiddenId);
        
        // Get existing tags from hidden input
        let tags = [];
        try {
            const existingValue = hidden.value;
            if (existingValue && existingValue !== '[]') {
                tags = JSON.parse(existingValue);
            }
        } catch(e) {
            tags = [];
        }
        
        function updateDisplay() {
            list.innerHTML = tags.map(tag => `
                <div class="tag">
                    ${escapeHtml(tag)}
                    <i class="ri-close-line"></i>
                </div>
            `).join('');
            
            // Add remove event listeners
            list.querySelectorAll('.tag i').forEach((icon, index) => {
                icon.addEventListener('click', () => {
                    tags.splice(index, 1);
                    updateDisplay();
                    updateHidden();
                });
            });
            
            updateHidden();
        }
        
        function updateHidden() {
            hidden.value = JSON.stringify(tags);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function addTag(tagText) {
            tagText = tagText.trim();
            if (tagText === '') return false;
            if (tags.includes(tagText)) {
                alert('This tag already exists');
                return false;
            }
            if (tags.length >= 15) {
                alert('Maximum 15 tags allowed');
                return false;
            }
            
            tags.push(tagText);
            updateDisplay();
            return true;
        }
        
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (addTag(input.value)) {
                        input.value = '';
                    }
                }
            });
        }
        
        // Initialize display
        updateDisplay();
        
        return { addTag, tags };
    }
    
    // Initialize vibe tags
    const vibeSystem = initTagSystem('vibeTagInput', 'vibeTagsList', 'vibeTagsHidden');
    
    // Initialize audience tags
    const audienceSystem = initTagSystem('audienceTagInput', 'audienceTagsList', 'audienceTagsHidden');
    
    // Suggested tags
    const suggestedTags = document.querySelectorAll('.suggested-tag');
    suggestedTags.forEach(tag => {
        tag.addEventListener('click', () => {
            const type = tag.getAttribute('data-type');
            const tagText = tag.textContent;
            if (type === 'vibe') {
                vibeSystem.addTag(tagText);
            } else if (type === 'audience') {
                audienceSystem.addTag(tagText);
            }
        });
    });
    
    // Delete modal
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteModal = document.getElementById('deleteModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCancel = document.querySelector('.modal-cancel');
    
    function openModal() {
        deleteModal.style.display = 'flex';
    }
    
    function closeModal() {
        deleteModal.style.display = 'none';
    }
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', openModal);
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    
    if (modalCancel) {
        modalCancel.addEventListener('click', closeModal);
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            closeModal();
        }
    });
    
    // Form validation
    const form = document.getElementById('hotelForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const hotelName = document.getElementById('hotel_name').value.trim();
            if (!hotelName) {
                e.preventDefault();
                alert('Please enter the hotel name');
                return false;
            }
            
            if (description && description.value.length > 1000) {
                e.preventDefault();
                alert('Description cannot exceed 1000 characters');
                return false;
            }
            
            return true;
        });
    }
});
</script>
@endpush
@endsection