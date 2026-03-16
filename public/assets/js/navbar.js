
document.addEventListener("DOMContentLoaded", function () {
    const nav = document.querySelector("nav");
    const menuBtn = document.getElementById("menu-btn");
    const navLinks = document.getElementById("nav-links");

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
        navLinks.classList.toggle("open");
    });

    document.querySelectorAll("#nav-links a").forEach(link => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("open");
        });
    });

    window.addEventListener("scroll", handleScroll);
    window.addEventListener("resize", handleScroll);
    handleScroll();
});
