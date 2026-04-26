
document.addEventListener("DOMContentLoaded", function () {
    const nav = document.querySelector("nav");
    const menuBtn = document.getElementById("menu-btn");
    const navLinks = document.getElementById("nav-links");
    const accountMenu = document.querySelector(".nav__account-menu");
    const accountTrigger = accountMenu?.querySelector(".nav__account-trigger");
    const mobileBreakpoint = 1024;

    if (!nav || !menuBtn || !navLinks) return;

    const menuIcon = menuBtn.querySelector("i");

    function setAccountMenuState(isOpen) {
        if (!accountMenu || !accountTrigger) return;
        accountMenu.classList.toggle("is-open", isOpen);
        accountTrigger.setAttribute("aria-expanded", String(isOpen));
    }

    function setMenuState(isOpen) {
        const shouldLockBody = isOpen && window.innerWidth <= mobileBreakpoint;
        navLinks.classList.toggle("open", isOpen);
        document.body.classList.toggle("nav-open", shouldLockBody);
        menuBtn.setAttribute("aria-expanded", String(isOpen));
        menuBtn.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");

        if (!isOpen) {
            setAccountMenuState(false);
        }

        if (menuIcon) {
            menuIcon.classList.toggle("ri-menu-line", !isOpen);
            menuIcon.classList.toggle("ri-close-line", isOpen);
        }
    }

    function handleScroll() {
        if (window.innerWidth <= mobileBreakpoint) {
            nav.classList.remove("scrolled");
            return;
        }

        if (window.scrollY > 30) {
            nav.classList.add("scrolled");
        } else {
            nav.classList.remove("scrolled");
        }
    }

    menuBtn.addEventListener("click", function () {
        const isOpen = !navLinks.classList.contains("open");
        setMenuState(isOpen);
    });

    document.querySelectorAll("#nav-links a").forEach((link) => {
        link.addEventListener("click", () => {
            setAccountMenuState(false);
            setMenuState(false);
        });
    });

    if (accountTrigger) {
        accountTrigger.addEventListener("click", function (event) {
            event.stopPropagation();
            const isOpen = !accountMenu.classList.contains("is-open");
            setAccountMenuState(isOpen);
        });
    }

    document.addEventListener("click", function (event) {
        if (accountMenu && !accountMenu.contains(event.target)) {
            setAccountMenuState(false);
        }

        if (window.innerWidth > mobileBreakpoint) return;
        if (!navLinks.classList.contains("open")) return;

        const clickedInsideNav = nav.contains(event.target);
        if (!clickedInsideNav) {
            setAccountMenuState(false);
            setMenuState(false);
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            setMenuState(false);
        }
    });

    window.addEventListener("scroll", handleScroll);
    window.addEventListener("resize", function () {
        if (window.innerWidth > mobileBreakpoint) {
            setAccountMenuState(false);
            setMenuState(false);
        }
        handleScroll();
    });

    setAccountMenuState(false);
    setMenuState(false);
    handleScroll();
});
