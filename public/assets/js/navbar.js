
document.addEventListener("DOMContentLoaded", function () {
    const nav = document.querySelector("nav");
    const menuBtn = document.getElementById("menu-btn");
    const navLinks = document.getElementById("nav-links");

    if (!nav || !menuBtn || !navLinks) return;

    const menuIcon = menuBtn.querySelector("i");

    function setMenuState(isOpen) {
        const shouldLockBody = isOpen && window.innerWidth <= 768;
        navLinks.classList.toggle("open", isOpen);
        document.body.classList.toggle("nav-open", shouldLockBody);
        menuBtn.setAttribute("aria-expanded", String(isOpen));
        menuBtn.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");

        if (menuIcon) {
            menuIcon.classList.toggle("ri-menu-line", !isOpen);
            menuIcon.classList.toggle("ri-close-line", isOpen);
        }
    }

    function handleScroll() {
        if (window.innerWidth <= 768) {
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
            setMenuState(false);
        });
    });

    document.addEventListener("click", function (event) {
        if (window.innerWidth > 768) return;
        if (!navLinks.classList.contains("open")) return;

        const clickedInsideNav = nav.contains(event.target);
        if (!clickedInsideNav) {
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
        if (window.innerWidth > 768) {
            setMenuState(false);
        }
        handleScroll();
    });

    setMenuState(false);
    handleScroll();
});
