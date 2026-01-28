 <nav>
      <div class="nav__header">
        <div class="nav__logo">
          <a href="#" class="logo">Yalla Nemshi</a>
        </div>
        <div class="nav__menu__btn" id="menu-btn">
          <i class="ri-menu-line"></i>
        </div>
      </div>
      <ul class="nav__links" id="nav-links">
        <li><a href="#home">HOME</a></li>
        <li><a href="#about">ABOUT</a></li>
        <li><a href="#tour">TOUR</a></li>
        <li><a href="#package">PACKAGE</a></li>
        <li><a href="#contact">CONTACT</a></li>
        <li><a href="#">BOOK TRIP</a></li>
      </ul>
      <div class="nav__btns">
        <button class="btn">BOOK TRIP</button>
      </div>
    </nav>

<style>
nav {
  position: fixed;
  isolation: isolate;
  top: 0;
  width: 100%;
  z-index: 9;
}

.nav__header {
  padding: 1rem;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: var(--primary-color);
}

.nav__logo .logo {
  font-size: 1.5rem;
  color: var(--white);
}

.nav__menu__btn {
  font-size: 1.5rem;
  color: var(--white);
  cursor: pointer;
}

.nav__links {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 2rem;
  background-color: var(--primary-color);
  transition: transform 0.5s;
  z-index: -1;
}

.nav__links.open {
  transform: translateY(100%);
}

.nav__links a {
  font-weight: 600;
  color: var(--white);
  white-space: nowrap;
}

.nav__links a:hover {
  color: var(--text-dark);
}

.nav__btns {
  display: none;
}




@media (width > 768px) {
  nav {
    position: static;
    padding: 2rem 1rem;
    max-width: var(--max-width);
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
  }

  .nav__header {
    flex: 1;
    padding: 0;
    background-color: transparent;
  }

  .nav__logo .logo {
    color: var(--text-dark);
  }

  .nav__menu__btn {
    display: none;
  }

  .nav__links {
    position: static;
    width: fit-content;
    padding: 0;
    flex-direction: row;
    background-color: transparent;
    transform: none !important;
  }

  .nav__links a {
    color: var(--text-dark);
  }

  .nav__links a:hover {
    color: var(--primary-color);
  }

  .nav__links li:last-child {
    display: none;
  }

  .nav__btns {
    flex: 1;
    display: flex;
    justify-content: flex-end;
  }

  .nav__btns button {
    padding: 0.75rem 2rem;
    background-color: var(--text-dark);
  }


}

</style>