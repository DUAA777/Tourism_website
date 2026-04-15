@extends('layout.app')

@push('meta')
  <title>Contact us | Yalla Nemshi</title>
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/contactUs.css') }}">
@endpush

@section('bodyClass', 'contact-page')

@section('content')

<section class="contact">
  <div class="section__container contact__wrap">
    <aside class="contact__info">
      <h1 class="contact__title">Contact Us</h1>
      <p class="contact__subtitle">
        We are here to help you plan better days out in Lebanon. Send us a message and we will get back to you.
      </p>

      <div class="contact__list">
        <div class="contact__item">
          <span class="contact__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 6h16v12H4V6Z" stroke="currentColor" stroke-width="1.6" />
              <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6" />
            </svg>
          </span>
          <div class="contact__text">
            <div class="contact__label">Email</div>
            <a class="contact__value" href="mailto:{{ config('mail.contact.address', 'hello@yallanemshi.com') }}">
              {{ config('mail.contact.address', 'hello@yallanemshi.com') }}
            </a>
          </div>
        </div>

        <div class="contact__item">
          <span class="contact__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M12 10.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.6"/>
            </svg>
          </span>
          <div class="contact__text">
            <div class="contact__label">Location</div>
            <div class="contact__value">Lebanon</div>
          </div>
        </div>

        <div class="contact__item">
          <span class="contact__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M7 4h3l1 5-2 1c1 3 3 5 6 6l1-2 5 1v3c0 1-1 2-2 2C10 20 4 14 4 6c0-1 1-2 3-2Z"
                stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            </svg>
          </span>
          <div class="contact__text">
            <div class="contact__label">Phone</div>
            <a class="contact__value" href="tel:+96170000000">+961 70 000 000</a>
          </div>
        </div>
      </div>
    </aside>

    <div class="contact__card">
      <form class="contact__form" action="{{ route('contactUs.send') }}" method="POST" novalidate>
        @csrf

        @if (session('contact_success'))
          <div class="contact__alert contact__alert--success">
            {{ session('contact_success') }}
          </div>
        @endif

        @if (session('contact_error'))
          <div class="contact__alert contact__alert--error">
            {{ session('contact_error') }}
          </div>
        @endif

        <div class="field">
          <label class="field__label" for="name">Name</label>
          <input class="field__input @error('name') field__input--error @enderror" id="name" name="name" type="text" placeholder="Your name" autocomplete="name" value="{{ old('name') }}">
          @error('name')
            <p class="field__error">{{ $message }}</p>
          @enderror
        </div>

        <div class="field">
          <label class="field__label" for="email">Email</label>
          <input class="field__input @error('email') field__input--error @enderror" id="email" name="email" type="email" placeholder="you@email.com" autocomplete="email" value="{{ old('email') }}">
          @error('email')
            <p class="field__error">{{ $message }}</p>
          @enderror
        </div>

        <div class="field">
          <label class="field__label" for="subject">Subject</label>
          <input class="field__input @error('subject') field__input--error @enderror" id="subject" name="subject" type="text" placeholder="Trip planner / Feedback / Bug" value="{{ old('subject') }}">
          @error('subject')
            <p class="field__error">{{ $message }}</p>
          @enderror
        </div>

        <div class="field">
          <label class="field__label" for="message">Message</label>
          <textarea class="field__input field__textarea @error('message') field__input--error @enderror" id="message" name="message" placeholder="Tell us what you need...">{{ old('message') }}</textarea>
          @error('message')
            <p class="field__error">{{ $message }}</p>
          @enderror
        </div>

        <button class="contact__submit" type="submit">
          Submit
        </button>

        <p class="contact__small">
          By sending, you agree that we can reply to your email.
        </p>
      </form>
    </div>
  </div>
</section>

@endsection
