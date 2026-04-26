@extends('layout.admin')

@section('title', 'Add New Restaurant')

@section('content')
<div class="create-restaurant-container">
    <div class="page-header">
        <div class="header-title">
            <h1>
                <i class="ri-restaurant-line"></i> 
                Add New Restaurant
            </h1>
            <p class="text-muted">Fill in the details to add a new restaurant to your platform</p>
        </div>
        <a href="{{ route('admin.restaurants.index') }}" class="btn-back">
            <i class="ri-arrow-left-line"></i> Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('admin.restaurants.store') }}" enctype="multipart/form-data" id="restaurantForm">
        @csrf
        
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
                                   value="{{ old('restaurant_name') }}" 
                                   placeholder="e.g., The Golden Spoon"
                                   class="@error('restaurant_name') is-invalid @enderror" required>
                        </div>
                        @error('restaurant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <div class="input-icon">
                            <i class="ri-map-pin-line"></i>
                            <input type="text" id="location" name="location" 
                                   value="{{ old('location') }}"
                                   placeholder="e.g., 123 Main Street, Downtown"
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
                                    <option value="{{ $type }}" {{ old('food_type') == $type ? 'selected' : '' }}>
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
                                    <option value="{{ $type }}" {{ old('restaurant_type') == $type ? 'selected' : '' }}>
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
                                        <option value="{{ $tier }}" {{ old('price_tier') == $tier ? 'selected' : '' }}>
                                            {{ $tier === $label ? $tier : $tier . ' - ' . $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="price-indicator" id="priceIndicator"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating (0-5)</label>
                            <div class="rating-input">
                                <input type="number" id="rating" name="rating" step="0.1" min="0" max="5"
                                       value="{{ old('rating') }}">
                                <div class="star-rating" id="starRating">
                                    <i class="ri-star-line"></i>
                                    <i class="ri-star-line"></i>
                                    <i class="ri-star-line"></i>
                                    <i class="ri-star-line"></i>
                                    <i class="ri-star-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" 
                                  placeholder="Describe the restaurant's ambiance, specialties, and unique features...">{{ old('description') }}</textarea>
                        <small class="char-counter">0/500 characters</small>
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
                            <div class="upload-area">
                                <i class="ri-image-add-line"></i>
                                <p>Click or drag to upload</p>
                                <small>PNG, JPG, WEBP (Max 5MB)</small>
                            </div>
                            <div class="image-preview" id="imagePreview"></div>
                        </div>
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <div class="input-icon">
                            <i class="ri-phone-line"></i>
                            <input type="tel" id="phone_number" name="phone_number" 
                                   value="{{ old('phone_number') }}"
                                   placeholder="e.g., +1 234 567 8900">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="opening_hours">Opening Hours</label>
                        <textarea id="opening_hours" name="opening_hours" rows="3" 
                                  placeholder="Mon-Fri: 11:00 AM - 10:00 PM&#10;Sat-Sun: 10:00 AM - 11:00 PM">{{ old('opening_hours') }}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website URL</label>
                        <div class="input-icon">
                            <i class="ri-global-line"></i>
                            <input type="url" id="website" name="website" 
                                   value="{{ old('website') }}" 
                                   placeholder="https://restaurant-website.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="directory_url">Menu URL</label>
                        <div class="input-icon">
                            <i class="ri-file-pdf-line"></i>
                            <input type="url" id="directory_url" name="directory_url" 
                                   value="{{ old('directory_url') }}"
                                   placeholder="https://restaurant.com/menu.pdf">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <div class="tags-input-container">
                            <input type="text" id="tagsInput" placeholder="Type tag and press Enter">
                            <input type="hidden" id="tags" name="tags">
                            <div class="tags-list" id="tagsList"></div>
                        </div>
                        <small>Press Enter to add tags like "Outdoor Seating", "Free WiFi", "Parking"</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="window.history.back()">
                <i class="ri-close-line"></i> Cancel
            </button>
            <button type="submit" class="btn-primary">
                <i class="ri-add-line"></i> Create Restaurant
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Professional Styling */
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

    .btn-back {
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-back:hover {
        background: #5a6268;
        color: white;
        transform: translateX(-3px);
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

    .input-icon input:focus {
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
        cursor: pointer;
        transition: all 0.3s;
    }

    .image-uploader:hover {
        border-color: #667eea;
        background: #f8f9fa;
    }

    .upload-area i {
        font-size: 48px;
        color: #adb5bd;
        margin-bottom: 10px;
    }

    .image-preview {
        margin-top: 15px;
    }

    .image-preview img {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
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
        margin-top: 10px;
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

    .rating-input {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .rating-input input {
        width: 80px;
    }

    .star-rating i {
        color: #ffc107;
        font-size: 20px;
        cursor: pointer;
    }

    .price-indicator {
        height: 4px;
        background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
        margin-top: 8px;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-actions {
        margin-top: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }

    .btn-primary, .btn-secondary {
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
    }

    .invalid-feedback {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
    }

    .is-invalid {
        border-color: #e74c3c !important;
    }

    @media (max-width: 768px) {
        .form-layout {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
// Image Upload Preview
document.getElementById('imageUploader').addEventListener('click', function() {
    document.getElementById('image').click();
});

document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Tags Input System
let tags = [];

function updateTagsInput() {
    const tagsList = document.getElementById('tagsList');
    const tagsHidden = document.getElementById('tags');
    
    tagsList.innerHTML = tags.map(tag => `
        <div class="tag">
            ${tag}
            <i class="ri-close-line" onclick="removeTag('${tag}')"></i>
        </div>
    `).join('');
    
    tagsHidden.value = tags.join(',');
}

function removeTag(tag) {
    tags = tags.filter(t => t !== tag);
    updateTagsInput();
}

document.getElementById('tagsInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        let tag = this.value.trim();
        
        if (tag && !tags.includes(tag) && tags.length < 10) {
            tags.push(tag);
            updateTagsInput();
            this.value = '';
        }
    }
});

// Character Counter for Description
const description = document.getElementById('description');
const charCounter = document.querySelector('.char-counter');

description.addEventListener('input', function() {
    const count = this.value.length;
    charCounter.textContent = `${count}/500 characters`;
    
    if (count > 500) {
        charCounter.style.color = '#e74c3c';
    } else {
        charCounter.style.color = '#6c757d';
    }
});

// Star Rating System
const ratingInput = document.getElementById('rating');
const stars = document.querySelectorAll('.star-rating i');

function updateStars(value) {
    const rating = parseFloat(value);
    stars.forEach((star, index) => {
        if (index < Math.floor(rating)) {
            star.className = 'ri-star-fill';
        } else if (index < Math.ceil(rating) && rating % 1 !== 0) {
            star.className = 'ri-star-half-fill';
        } else {
            star.className = 'ri-star-line';
        }
    });
}

ratingInput.addEventListener('input', function() {
    let value = parseFloat(this.value);
    if (isNaN(value)) value = 0;
    value = Math.min(5, Math.max(0, value));
    this.value = value.toFixed(1);
    updateStars(value);
});

stars.forEach((star, index) => {
    star.addEventListener('click', () => {
        ratingInput.value = (index + 1).toFixed(1);
        updateStars(index + 1);
    });
});

// Price Tier Indicator
const priceTier = document.getElementById('price_tier');
const priceIndicator = document.getElementById('priceIndicator');

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

// Form Validation
document.getElementById('restaurantForm').addEventListener('submit', function(e) {
    const name = document.getElementById('restaurant_name').value.trim();
    const location = document.getElementById('location').value.trim();
    
    if (!name || !location) {
        e.preventDefault();
        alert('Please fill in all required fields (*)');
        return false;
    }
    
    if (description.value.length > 500) {
        e.preventDefault();
        alert('Description must be less than 500 characters');
        return false;
    }
    
    return true;
});

// Initialize
updateStars(ratingInput.value || 0);
</script>
@endpush
@endsection
