@extends('layout.app')

@push('meta')
  <title>Plan | Yalla Nemshi</title>
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
@endpush

@section('content')
<section class="askai">
  <div class="askai__inner">
<div class="askai__icon" aria-hidden="true">
  <svg viewBox="0 0 64 64" fill="none">
    <rect x="30" y="40" width="4" height="10" fill="currentColor"/>
    <path d="M12 40 L32 26 L52 40 Z" fill="currentColor"/>
    <path d="M18 32 L32 18 L46 32 Z" fill="currentColor"/>
    <path d="M24 24 L32 12 L40 24 Z" fill="currentColor"/>
  </svg>
</div>
    <h1 class="askai__title">Ask Yalla Nemshi anything</h1>

    <p class="askai__sub">
      Tell us your mood, budget, time — we’ll build a perfect Lebanon day plan.
    </p>

    <div class="askai__label">Suggestions on what to ask</div>

    <div class="askai__chips">
      <button class="askai__chip" type="button">Batroun sunset + coffee</button>
      <button class="askai__chip" type="button">Beirut night plan 50$</button>
      <button class="askai__chip" type="button">Byblos history + lunch</button>
    </div>

    <div class="askai__bar">
      <input class="askai__input" type="text" placeholder="Ask me anything about your trip" />
      <button class="askai__send" type="button" aria-label="Send">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M4 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M14 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
  </div>
</section>
<script>

@endsection

@push('scripts')
  <script src="{{ asset('assets/js/chatbot.js') }}"></script>
@endpush