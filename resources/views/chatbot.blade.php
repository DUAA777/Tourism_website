@extends('layout.app')

@push('meta')
<title>Plan | Yalla Nemshi</title>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
@endpush

@section('content')

<section class="planner">

<div class="planner__logo">
    <a href="{{ route('home') }}" class="planner__logo-text">
        YALLA NEMSHI
    </a>
</div>
  <div class="planner__container">

    <div class="planner__header">
      <p class="planner__kicker">AI TRIP PLANNER</p>
      <h1 class="planner__title">Plan your perfect day in Lebanon</h1>
      <p class="planner__subtitle">
        Tell Yalla Nemshi your mood, city, budget and time — and we'll recommend the best places for your day.
      </p>
    </div>

    <div class="planner__card">
      <div class="planner__grid">
        <div class="planner__field">
          <label>City</label>
          <select>
            <option>Select city</option>
            <option>Beirut</option>
            <option>Batroun</option>
            <option>Byblos</option>
            <option>Baalbek</option>
          </select>
        </div>

        <div class="planner__field">
          <label>Budget</label>
          <select>
            <option>Select budget</option>
            <option>Under $20</option>
            <option>$20 - $50</option>
            <option>$50 - $100</option>
            <option>$100+</option>
          </select>
        </div>

        <div class="planner__field">
          <label>Available Time</label>
          <select>
            <option>Select duration</option>
            <option>2 Hours</option>
            <option>Half Day</option>
            <option>Full Day</option>
            <option>Weekend</option>
          </select>
        </div>

        <div class="planner__field">
          <label>Category</label>
          <select>
            <option>Select category</option>
            <option>Beach</option>
            <option>Nature</option>
            <option>Historic</option>
            <option>Food</option>
            <option>City</option>
          </select>
        </div>
      </div>

      <div class="planner__suggestions">
        <p>Suggestions</p>
        <div class="planner__chips">
          <button class="planner__chip">Batroun sunset + coffee</button>
          <button class="planner__chip">Beirut night plan under $50</button>
          <button class="planner__chip">Byblos history + lunch</button>
          <button class="planner__chip">Nature day in Chouf</button>
        </div>
      </div>

      <div class="planner__input">
        <input type="text" placeholder="Ask anything about your trip...">
        <button>Plan Trip</button>
      </div>
    </div>

    <div class="planner__preview">
      <h3>Example Day Plan</h3>

      <div class="preview__step">
        <span>1</span>
        <div>
          <h4>Morning coffee</h4>
          <p>Start your day at a cozy café and walk through the old streets.</p>
        </div>
      </div>

      <div class="preview__step">
        <span>2</span>
        <div>
          <h4>Main destination</h4>
          <p>Visit a place that matches your vibe and travel style.</p>
        </div>
      </div>

      <div class="preview__step">
        <span>3</span>
        <div>
          <h4>Lunch spot</h4>
          <p>Finish your day with a restaurant recommendation nearby.</p>
        </div>
      </div>
    </div>

  </div>
</section>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/chatbot.js') }}"></script>
@endpush