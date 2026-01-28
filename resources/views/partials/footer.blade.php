<div class="footer__col">
          <h4>Quick Links</h4>
          <ul class="footer__links">
            <li><a href="#">Home</a></li> 
            <li><a href="#">Plan</a></li>
            <li><a href="#">Hotels</a></li>
            <li><a href="#">Restaurants</a></li>
          </ul>
        </div>
        <div class="footer__col">
          <h4>Contact Us</h4>
          <ul class="footer__links">
            <li>
              <a href="#">
                <span><i class="ri-phone-fill"></i></span> +961 82847272
              </a>
            </li>
            <li>
              <a href="#">
                <span><i class="ri-record-mail-line"></i></span> info@yallanemshi
              </a>
            </li>
            <li>
              <a href="#">
                <span><i class="ri-map-pin-2-fill"></i></span> Beirut, Lebanon
              </a>
            </li>
          </ul>
        </div>
        <div class="footer__col">
          <h4>Subscribe</h4>
          <form action="/">
            <input type="text" placeholder="Enter your email" />
            <button class="btn">Subscribe</button>
          </form>
        </div>
      </div>
      <div class="footer__bar">
        Copyright © 
      </div>
    </footer>

    <style>
        footer {
  background-color: var(--extra-light);
}

.footer__container {
  display: grid;
  gap: 4rem 2rem;
}

.footer__col p {
  max-width: 300px;
  margin-block: 2rem;
  color: var(--text-light);
}

.footer__socials {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.footer__socials a {
  display: inline-block;
  padding: 7px 10px;
  font-size: 1.25rem;
  color: var(--white);
  background-color: var(--primary-color);
  border-radius: 100%;
}

.footer__socials a:hover {
  background-color: var(--primary-color-dark);
}

.footer__col h4 {
  margin-bottom: 2rem;
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text-dark);
}

.footer__links {
  display: grid;
  gap: 1rem;
}

.footer__links a {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-light);
}

.footer__links a:hover {
  color: var(--primary-color);
}

.footer__links a span {
  font-size: 1.25rem;
}

.footer__col form {
  display: grid;
  gap: 1rem;
}

.footer__col input {
  padding: 0.75rem;
  font-size: 1rem;
  color: var(--text-dark);
  background-color: var(--white);
  border: 1px solid var(--text-light);
  border-radius: 5px;
}

.footer__col input::placeholder {
  color: var(--text-light);
}

.footer__col .btn {
  border-radius: 5px;
}

.footer__bar {
  padding: 1rem;
  font-size: 0.9rem;
  color: var(--text-light);
  text-align: center;
}

@media (width > 540px) {


  .footer__container {
    grid-template-columns: repeat(2, 1fr);
  }

  .footer__col:last-child {
    grid-area: 2/1/3/2;
  }
}
@media (width > 768px) {
  

  .footer__container {
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
  }

  .footer__col:last-child {
    grid-area: unset;
  }
}

</style>