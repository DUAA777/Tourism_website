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

<header class="about-hero">
  <div class="section__container">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-6">
        <p class="about-kicker">ABOUT US • YALLA NEMSHI</p>

        <h1 class="about-title">
          Plan Lebanon like a local — fast, simple, and fun.
        </h1>

        <p class="about-subtitle">
          Yalla Nemshi helps you build a perfect day out in Lebanon based on your city, budget, time, and interests —
          then suggests places you’ll actually enjoy.
        </p>

        <div class="about-hero__actions">
          <a href="{{ route('chatbot') }}" class="btn">Plan a Trip Now</a>
          <a href="#different" class="about-link">What makes us different →</a>
        </div>

        <div class="about-stats">
          <div class="about-stat">
            <span class="about-stat__num">3+</span>
            <span class="about-stat__label">Cities covered</span>
          </div>
          <div class="about-stat">
            <span class="about-stat__num">10+</span>
            <span class="about-stat__label">Categories</span>
          </div>
          <div class="about-stat">
            <span class="about-stat__num">Smart</span>
            <span class="about-stat__label">Recommendations</span>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="mission-card">
  <p class="mission-kicker">Our mission</p>

  <h3 class="mission-title">
    Make discovering activities in Lebanon effortless.
  </h3>

  <p class="mission-text">
    So anyone can turn “I don’t know what to do today” into a clear plan in minutes.
  </p>

  <div class="mission-tags">
    <span class="tag tag--red">City-based suggestions</span>
    <span class="tag tag--green">Budget filtering</span>
    <span class="tag tag--neutral">Tags &amp; interests</span>
  </div>
</div>
      </div>

    </div>
  </div>
</header>

{{-- WHAT WE OFFER --}}
<section class="py-5 bg-light section-animate">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">What We Offer</h2>
            <p class="text-muted">Everything you need to plan your visit to Lebanon</p>
        </div>

<section id="different" class="about-section about-section--alt">
  <div class="section__container">
    <h2 class="section__header">What makes us different</h2>
    <p class="section__description">
      Simple, focused, and built around how people actually plan a day out in Lebanon.
    </p>

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