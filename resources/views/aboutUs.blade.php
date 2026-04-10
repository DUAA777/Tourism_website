@extends('layout.app')

@section('body-class','about-page')

@push('meta')
  <title>About us | Yalla Nemshi</title>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/about.css') }}">
@endpush

@section('content')

<section class="about-hero">
  <div class="section__container">
    <div class="about-hero__grid">

      <div class="about-hero__content">
        <p class="about-kicker">ABOUT US • YALLA NEMSHI</p>

        <h1 class="about-title">
          A smarter way to explore <span>Lebanon</span>
        </h1>

        <p class="about-subtitle">
          Yalla Nemshi helps people discover places, build better plans, and enjoy
          Lebanon with less searching and more exploring.
        </p>

        <div class="about-hero__actions">
          <a href="{{ route('chatbot') }}" class="btn">Plan a Trip Now</a>
          <a href="{{ route('contactUs') }}" class="about-link">Contact us →</a>
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
            <span class="about-stat__label">Suggestions</span>
          </div>
        </div>
      </div>

      <div class="mission-card">
        <p class="mission-kicker">Our mission</p>
        <h3 class="mission-title">
          Make planning in Lebanon feel simple and useful.
        </h3>
        <p class="mission-text">
          We turn random searching into clear recommendations based on your city,
          budget, time, and interests.
        </p>

        <div class="mission-tags">
          <span class="tag tag--red">City-based ideas</span>
          <span class="tag tag--green">Budget filters</span>
          <span class="tag tag--neutral">Smarter planning</span>
        </div>
      </div>

    </div>
  </div>
</section>

<section class="about-story">
  <div class="section__container">
    <div class="about-story__grid">
      <div class="about-story__text">
        <p class="section-kicker">OUR STORY</p>
        <h2 class="section-title">Built for people who want better local experiences</h2>
        <p class="section-text">
          Yalla Nemshi was created to make discovering Lebanon easier. Instead of
          jumping between random sources, users can explore places and get cleaner
          ideas in one platform.
        </p>
      </div>

      <div class="about-story__image">
        <img src="{{ asset('images/destination-3.jpg') }}" alt="Beirut">
      </div>
    </div>
  </div>
</section>

<section class="about-features">
  <div class="section__container">
    <div class="section-heading">
      <p class="section-kicker">WHY YALLA NEMSHI</p>
      <h2 class="section-title section-title--center">What makes us different</h2>
    </div>

    <div class="feature-grid">
      <article class="feature-card">
        <div class="feature-icon"><i class="ri-compass-3-line"></i></div>
        <h3>Focused recommendations</h3>
        <p>We suggest places that match real moods and preferences.</p>
      </article>

      <article class="feature-card">
        <div class="feature-icon"><i class="ri-equalizer-3-line"></i></div>
        <h3>Useful filters</h3>
        <p>City, budget, time, and category all help shape better plans.</p>
      </article>

      <article class="feature-card">
        <div class="feature-icon"><i class="ri-map-pin-user-line"></i></div>
        <h3>Made for Lebanon</h3>
        <p>The platform is designed around local destinations and real outings.</p>
      </article>
    </div>
  </div>
</section>

<section class="about-final">
  <div class="section__container">
    <div class="final-inner">
      <p class="final-kicker">MADE FOR LEBANON</p>
      <h2 class="final-title">Built locally. Designed for explorers.</h2>
      <p class="final-text">
        Whether it’s a sunset in Batroun or a history day in Baalbek, Yalla Nemshi
        helps people explore Lebanon with more confidence.
      </p>

      <div class="final-actions">
        <a href="{{ route('chatbot') }}" class="btn">Plan a Trip Now</a>
        <a href="{{ route('places.index') }}" class="btn btn-outline-light">Browse Places</a>
      </div>
    </div>
  </div>
</section>

@endsection