@extends('layout.admin')

@section('title', 'Edit Restaurant - ' . $restaurant->restaurant_name)

@section('content')
<div class="create-restaurant-container">
    <div class="page-header">
        <div class="header-title">
            <h1>
                <i class="ri-restaurant-line"></i> 
                Edit Restaurant
            </h1>
            <p class="text-muted">Update restaurant information and manage details for "{{ $restaurant->restaurant_name }}"</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.restaurants.show', $restaurant) }}" class="btn-secondary">
                <i class="ri-eye-line"></i> View
            </a>
            <a href="{{ route('admin.restaurants.index') }}" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}" 
          enctype="multipart/form-data" id="restaurantForm">
        @csrf
        @method('PUT')
        
        <div class="form-layout">
            <!-- Main Information -->
            <div class="form-card">
                <div class="card-header">
                    <i class="ri-information-line"></i>
                    <h3>Basic Information</h3>
                </div>
                
                <div class="card-body">
                    <div class="form-group">
                        <label for="restaurant_name">Restaurant Name <span class="required">*</span></label>
                        <div class="input-icon">
                            <i class="ri-store-line"></i>
                            <input type="text" id="restaurant_name" name="restaurant_name" 
                                   value="{{ old('restaurant_name', $restaurant->restaurant_name) }}" 
                                   class="@error('restaurant_name') is-invalid @enderror" required>
                        </div>
                        @error('restaurant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <div class="input-icon">
                            <i class="ri-map-pin-line"></i>
                            <input type="text" id="location" name="location" 
                                   value="{{ old('location', $restaurant->location) }}"
                                   class="@error('location') is-invalid @enderror" required>
                        </div>
                        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="food_type">Cuisine Type</label>
                            <select id="food_type" name="food_type" class="@error('food_type') is-invalid @enderror">
                                <option value="">Select Cuisine</option>
                                @foreach($foodTypes as $type)
                                    <option value="{{ $type }}" {{ old('food_type', $restaurant->food_type) == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('food_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="restaurant_type">Restaurant Type</label>
                            <select id="restaurant_type" name="restaurant_type" class="@error('restaurant_type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                @foreach($restaurantTypes as $type)
                                    <option value="{{ $type }}" {{ old('restaurant_type', $restaurant->restaurant_type) == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                            @error('restaurant_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price_tier">Price Tier</label>
                            <div class="price-selector">
                                <select id="price_tier" name="price_tier">
                                    <option value="">Select Price Range</option>
                                    @foreach($priceTiers as $tier => $label)
                                        <option value="{{ $tier }}" {{ old('price_tier', $restaurant->price_tier) == $tier ? 'selected' : '' }}>
                                            {{ $tier === $label ? $tier : $tier . ' - ' . $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @php
                                    $selectedPriceTier = old('price_tier', $restaurant->price_tier);
                                    $priceIndicatorWidth = [
                                        '$' => '25%',
                                        '$$' => '50%',
                                        '$$$' => '75%',
                                        '$$$$' => '100%',
                                        'Budget' => '25%',
                                        'Mid-range' => '50%',
                                        'Premium' => '75%',
                                        'Luxury' => '100%',
                                    ][$selectedPriceTier] ?? '0%';
                                @endphp
                                <div class="price-indicator" id="priceIndicator" style="width: {{ $priceIndicatorWidth }}"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating (0-5)</label>
                            <div class="rating-input">
                                <input type="number" id="rating" name="rating" step="0.1" min="0" max="5"
                                       value="{{ old('rating', $restaurant->rating) }}">
                                <div class="star-rating" id="starRating">
                                    @php
                                        $rating = old('rating', $restaurant->rating);
                                    @endphp
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($rating))
                                            <i class="ri-star-fill" data-rating="{{ $i }}"></i>
                                        @elseif($i - 0.5 <= $rating)
                                            <i class="ri-star-half-fill" data-rating="{{ $i }}"></i>
                                        @else
                                            <i class="ri-star-line" data-rating="{{ $i }}"></i>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" 
                                  placeholder="Describe the restaurant's ambiance, specialties, and unique features...">{{ old('description', $restaurant->description) }}</textarea>
                        <small class="char-counter">{{ strlen(old('description', $restaurant->description)) }}/500 characters</small>
                    </div>
                </div>
            </div>
            
            <!-- Contact & Media -->
            <div class="form-card">
                <div class="card-header">
                    <i class="ri-image-line"></i>
                    <h3>Media & Contact</h3>
                </div>
                
                <div class="card-body">
                    <div class="form-group">
                        <label for="image">Restaurant Image</label>
                        <div class="image-uploader" id="imageUploader">
                            <input type="file" id="image" name="image" accept="image/*" hidden>
                            <div class="upload-area" id="uploadArea">
                                <i class="ri-image-add-line"></i>
                                <p>Click or drag to upload new image</p>
                                <small>PNG, JPG, WEBP (Max 5MB)</small>
                            </div>
                            <div class="image-preview restaurant-image-preview" id="imagePreview" style="{{ $restaurant->image ? 'display: flex;' : 'display: none;' }}">
                                <img src="{{ $restaurant->image ? asset($restaurant->image) : '' }}" alt="Current Image" id="previewImg">
                                <button type="button" class="remove-image" id="removeImageBtn">
                                    <i class="ri-close-line"></i>
                                    Remove image
                                </button>
                            </div>
                        </div>
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <div class="input-icon">
                            <i class="ri-phone-line"></i>
                            <input type="tel" id="phone_number" name="phone_number" 
                                   value="{{ old('phone_number', $restaurant->phone_number) }}"
                                   placeholder="e.g., +1 234 567 8900">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="opening_hours">Opening Hours</label>
                        <textarea id="opening_hours" name="opening_hours" rows="3" 
                                  placeholder="Mon-Fri: 11:00 AM - 10:00 PM&#10;Sat-Sun: 10:00 AM - 11:00 PM">{{ old('opening_hours', $restaurant->opening_hours) }}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website URL</label>
                        <div class="input-icon">
                            <i class="ri-global-line"></i>
                            <input type="url" id="website" name="website" 
                                   value="{{ old('website', $restaurant->website) }}" 
                                   placeholder="https://restaurant-website.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="directory_url">Menu URL</label>
                        <div class="input-icon">
                            <i class="ri-file-pdf-line"></i>
                            <input type="url" id="directory_url" name="directory_url" 
                                   value="{{ old('directory_url', $restaurant->directory_url) }}"
                                   placeholder="https://restaurant.com/menu.pdf">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <div class="tags-input-container">
                            @php
                                $rawTags = old('tags');

                                if ($rawTags === null) {
                                    $rawTags = $tagsArray ?? $restaurant->tags_array ?? [];
                                }

                                if (is_array($rawTags)) {
                                    $normalizedTags = $rawTags;
                                } else {
                                    $decodedTags = json_decode((string) $rawTags, true);
                                    $normalizedTags = is_array($decodedTags) ? $decodedTags : explode(',', (string) $rawTags);
                                }

                                $normalizedTags = collect($normalizedTags)
                                    ->map(fn ($tag) => trim((string) $tag))
                                    ->filter()
                                    ->values()
                                    ->all();

                                $tagsValue = implode(',', $normalizedTags);
                            @endphp
                            <input type="text" id="tagsInput" placeholder="Type tag and press Enter">
                            <input type="hidden" id="tags" name="tags" value="{{ $tagsValue }}">
                            <div class="tags-list" id="tagsList">
                                @foreach($normalizedTags as $tag)
                                    <div class="tag">
                                        {{ $tag }}
                                        <i class="ri-close-line"></i>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <small>Press Enter to add tags like "Outdoor Seating", "Free WiFi", "Parking"</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn-danger"
                    data-delete-target="deleteRestaurantForm"
                    data-delete-title="Delete restaurant?"
                    data-delete-message="Delete {{ $restaurant->restaurant_name }}? This removes it from the restaurant listings."
                    data-delete-confirm="Delete Restaurant">
                <i class="ri-delete-bin-line"></i> Delete Restaurant
            </button>
            <div class="action-buttons">
                <button type="button" class="btn-secondary" onclick="window.history.back()">
                    <i class="ri-close-line"></i> Cancel
                </button>
                <button type="submit" class="btn-primary">
                    <i class="ri-save-line"></i> Update Restaurant
                </button>
            </div>
        </div>
    </form>
    <form id="deleteRestaurantForm" action="{{ route('admin.restaurants.destroy', $restaurant) }}" method="POST" class="js-delete-form" style="display: none;"
          data-delete-title="Delete restaurant?"
          data-delete-message="Delete {{ $restaurant->restaurant_name }}? This removes it from the restaurant listings."
          data-delete-confirm="Delete Restaurant">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('styles')
<style>
    /* Same styles as create page, plus additional modal styles */
    .create-restaurant-container {
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

    .form-layout {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }

    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
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

    .price-selector {
        position: relative;
    }

    .price-indicator {
        height: 4px;
        background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
        margin-top: 8px;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .char-counter {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: #6c757d;
    }

    .form-actions {
        margin-top: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .btn-primary, .btn-secondary, .btn-danger {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220,53,69,0.3);
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
        color: #dc3545;
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
        .form-layout {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column-reverse;
        }
        
        .action-buttons {
            width: 100%;
        }
        
        .btn-primary, .btn-secondary, .btn-danger {
            flex: 1;
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
    // Character counter for description
    const description = document.getElementById('description');
    const charCounter = document.querySelector('.char-counter');
    
    function updateCharCount() {
        const count = description.value.length;
        charCounter.textContent = `${count}/500 characters`;
        charCounter.style.color = count > 500 ? '#e74c3c' : '#6c757d';
    }
    
    if (description) {
        description.addEventListener('input', updateCharCount);
        updateCharCount();
    }
    
    // Star Rating System
    const ratingInput = document.getElementById('rating');
    const stars = document.querySelectorAll('.star-rating i');
    
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
    
    if (ratingInput) {
        ratingInput.addEventListener('input', function() {
            let value = parseFloat(this.value);
            if (isNaN(value)) value = 0;
            value = Math.min(5, Math.max(0, value));
            this.value = value.toFixed(1);
            updateStars(value);
        });
        
        const ratingStars = document.querySelector('.star-rating');
        if (ratingStars) {
            ratingStars.addEventListener('mouseleave', function() {
                updateStars(ratingInput.value);
            });
        }
        
        updateStars(ratingInput.value);
    }
    
    // Price Tier Indicator
    const priceTier = document.getElementById('price_tier');
    const priceIndicator = document.getElementById('priceIndicator');
    
    if (priceTier) {
        priceTier.addEventListener('change', function() {
            const value = this.value;
            const priceWidths = {
                '$': '25%',
                '$$': '50%',
                '$$$': '75%',
                '$$$$': '100%',
                'Budget': '25%',
                'Mid-range': '50%',
                'Premium': '75%',
                'Luxury': '100%',
            };
            
            priceIndicator.style.width = priceWidths[value] || '0%';
        });
    }
    
    // Image Upload Handling
    const imageUploader = document.getElementById('imageUploader');
    const imageInput = document.getElementById('image');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const removeImageBtn = document.getElementById('removeImageBtn');
    
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
            imagePreview.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    }
    
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', () => {
            if (confirm('Remove current image?')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_image';
                hiddenInput.value = '1';
                document.getElementById('restaurantForm').appendChild(hiddenInput);
                
                imagePreview.style.display = 'none';
                previewImg.src = '';
                imageInput.value = '';
            }
        });
    }
    
    // Tags Input System
    let tags = [];
    const tagsList = document.getElementById('tagsList');
    const tagsHidden = document.getElementById('tags');
    const tagsInput = document.getElementById('tagsInput');
    
    // Initialize tags from existing
    if (tagsList) {
        document.querySelectorAll('#tagsList .tag').forEach(tagElement => {
            const tagText = tagElement.childNodes[0].textContent.trim();
            tags.push(tagText);
            
            const closeBtn = tagElement.querySelector('.ri-close-line');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    const tagToRemove = tagElement.childNodes[0].textContent.trim();
                    tags = tags.filter(t => t !== tagToRemove);
                    tagElement.remove();
                    updateTagsHidden();
                });
            }
        });
    }
    
    function updateTagsHidden() {
        tagsHidden.value = tags.join(',');
    }
    
    function addTag(tagText) {
        tagText = tagText.trim();
        if (tagText === '') return false;
        
        if (tags.includes(tagText)) {
            alert('This tag already exists');
            return false;
        }
        
        if (tags.length >= 10) {
            alert('Maximum 10 tags allowed');
            return false;
        }
        
        tags.push(tagText);
        
        const tagElement = document.createElement('div');
        tagElement.className = 'tag';
        tagElement.innerHTML = `${tagText} <i class="ri-close-line"></i>`;
        
        tagElement.querySelector('.ri-close-line').addEventListener('click', function() {
            tags = tags.filter(t => t !== tagText);
            tagElement.remove();
            updateTagsHidden();
        });
        
        tagsList.appendChild(tagElement);
        updateTagsHidden();
        return true;
    }
    
    if (tagsInput) {
        tagsInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (addTag(tagsInput.value)) {
                    tagsInput.value = '';
                }
            }
        });
    }
    
    // Delete Modal
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteModal = document.getElementById('deleteModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCancel = document.querySelector('.modal-cancel');
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            deleteModal.style.display = 'flex';
        });
    }
    
    function closeModal() {
        deleteModal.style.display = 'none';
    }
    
    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalCancel) modalCancel.addEventListener('click', closeModal);
    
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            closeModal();
        }
    });
    
    // Form Validation
    const form = document.getElementById('restaurantForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('restaurant_name').value.trim();
            const location = document.getElementById('location').value.trim();
            
            if (!name || !location) {
                e.preventDefault();
                alert('Please fill in all required fields (*)');
                return false;
            }
            
            if (description && description.value.length > 500) {
                e.preventDefault();
                alert('Description cannot exceed 500 characters');
                return false;
            }
            
            return true;
        });
    }
});
</script>
@endpush
@endsection
