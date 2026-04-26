<footer class="site-footer" id="contact">
  @php
    $footerContactEmail = config('mail.contact.address', 'hello@yallanemshi.com');
    $footerContactPhone = '+961 70 000 000';
    $footerContactPhoneHref = '+96170000000';
    $footerContactLocation = 'Lebanon';
  @endphp
  <div class="section__container footer__container">
    <div class="footer__top">
      <div class="footer__brand">
        <a href="{{ route('home') }}" class="footer__brand-link" aria-label="Yalla Nemshi home">
          <span class="footer__brand-mark">
            <img src="{{ asset('images/navbar-mark.png') }}" alt="" class="footer__brand-image">
          </span>
          <span class="footer__brand-copy">
            <strong>Yalla Nemshi</strong>
            <small>Lebanon travel planner</small>
          </span>
        </a>

        <p class="footer__brand-text">
          Discover hotels, restaurants, and smart trip ideas across Lebanon with a planning experience built for real travel decisions.
        </p>
      </div>

      <div class="footer__column">
        <p class="footer__eyebrow">Explore</p>
        <h4>Plan with confidence</h4>
        <ul class="footer__nav">
          <li><a href="{{ route('chatbot') }}">AI trip planner</a></li>
          <li><a href="{{ route('hotels.index') }}">Browse hotels</a></li>
          <li><a href="{{ route('restaurants.index') }}">Browse restaurants</a></li>
          <li><a href="{{ route('aboutUs') }}">About Yalla Nemshi</a></li>
        </ul>
      </div>

      <div class="footer__column">
        <p class="footer__eyebrow">Contact</p>
        <h4>Reach our team</h4>
        <ul class="footer__links">
          <li>
            <a href="tel:{{ $footerContactPhoneHref }}">
              <span><i class="ri-phone-fill"></i></span>
              <span>{{ $footerContactPhone }}</span>
            </a>
          </li>
          <li>
            <a href="mailto:{{ $footerContactEmail }}">
              <span><i class="ri-mail-send-line"></i></span>
              <span>{{ $footerContactEmail }}</span>
            </a>
          </li>
          <li>
            <a href="{{ route('contactUs') }}">
              <span><i class="ri-map-pin-2-fill"></i></span>
              <span>{{ $footerContactLocation }}</span>
            </a>
          </li>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <span>Copyright &copy; 2026 Yalla Nemshi. All rights reserved.</span>
      <div class="footer__bottom-links">
        <a href="{{ route('contactUs') }}">Contact</a>
        <a href="{{ route('aboutUs') }}">About</a>
        <a href="{{ route('chatbot') }}">Plan a trip</a>
      </div>
    </div>
  </div>
</footer>
