@section('body-class','about-page')
@extends('layout.app')
@push('styles')
<style>
body.about-page .nav__links a{
  color:#000 !important;
}

body.about-page .nav__links a:hover{
  color:var(--primary) !important;
}
</style>
@endpush

@push('meta')
  <title>About us | Yalla Nemshi</title>
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/about.css') }}">
@endpush

@section('content')

    
{{-- HERO SECTION --}}
<!-- <section class="hero-section section-animate text-white d-flex align-items-center"
    style="
    min-height: 70vh;
    background:
    linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.55)),
    url('{{ asset('assets/images/png/hero.jpg') }}') center/cover;
">
    <div class="container text-center">
        <h1 class="fw-bold display-5">About Lebanon Tourism Guide</h1>
        <p class="lead mt-3">
            Your smart companion to explore Lebanon’s regions, hotels, restaurants, and attractions
        </p>
        <a href="#who-we-are" class="btn btn-warning btn-lg mt-4">Discover More</a>
    </div>
</section> -->

{{-- WHO WE ARE --}}
<section id="who-we-are" class="py-5 section-animate">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Who We Are</h2>
                <p>
                    Lebanon Tourism Guide is a modern digital platform designed to help tourists and locals
                    discover the beauty of Lebanon. From historical landmarks to hotels and restaurants,
                    our goal is to make trip planning simple and enjoyable.
                </p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-2">✔ Explore all Lebanese regions</li>
                    <li class="mb-2">✔ Find and book hotels & restaurants</li>
                    <li class="mb-2">✔ Smart AI-powered recommendations</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <img src="{{ asset('assets/images/png/byblos.jpg') }}"
                     class="img-fluid rounded-4 shadow"
                     alt="Byblos">
            </div>
        </div>
    </div>
</section>

{{-- WHAT WE OFFER --}}
<section class="py-5 bg-light section-animate">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">What We Offer</h2>
            <p class="text-muted">Everything you need to plan your visit to Lebanon</p>
        </div>

<<<<<<< HEAD
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm text-center p-4">
                    <i class="bi bi-geo-alt fs-1 text-primary"></i>
                    <h5 class="mt-3">Tourist Regions</h5>
                    <p>Explore Lebanon’s regions from Beirut to the Cedars.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm text-center p-4">
                    <i class="bi bi-building fs-1 text-primary"></i>
                    <h5 class="mt-3">Hotels</h5>
                    <p>Browse and book hotels that fit your needs and budget.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm text-center p-4">
                    <i class="bi bi-cup-hot fs-1 text-primary"></i>
                    <h5 class="mt-3">Restaurants</h5>
                    <p>Discover the best Lebanese and international cuisine.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- OUR VISION --}}
<section class="py-5 section-animate">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <h2 class="fw-bold mb-4">Our Vision</h2>
                <p>
                    We aim to support tourism in Lebanon by providing accurate, organized,
                    and smart information that helps visitors discover both popular destinations
                    and hidden gems.
                </p>
                <ul>
                    <li>Promote local tourism</li>
                    <li>Make trip planning easier</li>
                    <li>Encourage cultural exploration</li>
                </ul>
            </div>
            <div class="col-lg-6 order-lg-1">
                <img src="{{ asset('assets/images/png/baalbek.jpg') }}"
                     class="img-fluid rounded-4 shadow"
                     alt="Baalbek">
            </div>
        </div>
    </div>
</section>

{{-- WHY CHOOSE US --}}
<section class="py-5 bg-light section-animate">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose Us</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 text-center shadow-sm p-3">
                    <i class="bi bi-lightning fs-1 text-primary"></i>
                    <h6 class="mt-3">Easy to Use</h6>
                    <p>Simple and intuitive platform.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 text-center shadow-sm p-3">
                    <i class="bi bi-search fs-1 text-primary"></i>
                    <h6 class="mt-3">Comprehensive</h6>
                    <p>Detailed and reliable information.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 text-center shadow-sm p-3">
                    <i class="bi bi-cpu fs-1 text-primary"></i>
                    <h6 class="mt-3">Smart AI</h6>
                    <p>Personalized recommendations.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 text-center shadow-sm p-3">
                    <i class="bi bi-people fs-1 text-primary"></i>
                    <h6 class="mt-3">Local Expertise</h6>
                    <p>Built with local knowledge.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="py-5 text-white text-center section-animate" style="background:#0d6efd;">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h2 class="fw-bold">20+</h2>
                <p>Regions & Attractions</p>
            </div>
            <div class="col-md-4">
                <h2 class="fw-bold">15+</h2>
                <p>Top Hotels</p>
            </div>
            <div class="col-md-4">
                <h2 class="fw-bold">25+</h2>
                <p>Recommended Restaurants</p>
            </div>
        </div>
    </div>
</section>
=======
<section id="different" class="about-section about-section--alt">
  <div class="section__container">
    <h2 class="section__header">What makes us different</h2>
    <p class="section__description">
      Simple, focused, and built around how people actually plan a day out in Lebanon.
    </p>
>>>>>>> 60aaf51669052e95574285667333597a9773e021

    <div class="row g-4 mt-4">
      <div class="col-12 col-md-6 col-lg-4">
        <div class="feature-card">
          <h3>Focused recommendations</h3>
          <p>We don’t overwhelm you. We suggest the best matches based on what you chose.</p>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-4">
        <div class="feature-card">
          <h3>Real filters that matter</h3>
          <p>City, budget, duration, category, and tags — so your plan fits your day.</p>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-4">
        <div class="feature-card">
          <h3>Built to grow</h3>
          <p>Organized structure makes it easy to add more cities and smarter logic later.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="about-final">
  <div class="section__container">
    <div class="final-inner">
      <p class="final-kicker">MADE FOR LEBANON</p>

      <h2 class="final-title">Built in Lebanon. Designed for explorers.</h2>

      <p class="final-text">
        Whether it’s a quick Batroun sunset, a Byblos morning walk, or a Baalbek history day —
        we help you plan it with confidence.
      </p>

      <div class="final-actions">
        <a href="{{ route('chatbot') }}" class="btn">Plan a Trip Now</a>
        <a href="{{ route('contactUs') }}" class="btn btn-outline-light">Contact us</a>
      </div>
    </div>
  </div>
</section>

@endsection

@push('scripts')
  <script src="{{ asset('assets/js/about.js') }}"></script>
@endpush