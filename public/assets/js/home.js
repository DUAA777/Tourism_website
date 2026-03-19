document.addEventListener("DOMContentLoaded", function () {
    const statNumbers = document.querySelectorAll(".stat-number");

    if (!statNumbers.length) return;

    let started = false;

    function animateNumber(element) {
        const target = parseFloat(element.getAttribute("data-target")) || 0;
        const suffix = element.getAttribute("data-suffix") || "";
        const decimals = parseInt(element.getAttribute("data-decimals") || "0", 10);

        let start = 0;
        const duration = 1800;
        const increment = target / (duration / 16);

        function updateCounter() {
            start += increment;

            if (start >= target) {
                if (decimals > 0) {
                    element.textContent = target.toFixed(decimals) + suffix;
                } else {
                    element.textContent = Math.floor(target).toLocaleString() + suffix;
                }
                return;
            }

            if (decimals > 0) {
                element.textContent = start.toFixed(decimals) + suffix;
            } else {
                element.textContent = Math.floor(start).toLocaleString() + suffix;
            }

            requestAnimationFrame(updateCounter);
        }

        updateCounter();
    }

    function startCounters() {
        if (started) return;
        started = true;
        statNumbers.forEach((number) => animateNumber(number));
    }

    const statsSection = document.querySelector(".stats-strip");

    if (!statsSection) {
        startCounters();
        return;
    }

    const observer = new IntersectionObserver(
        function (entries) {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    startCounters();
                    observer.unobserve(statsSection);
                }
            });
        },
        { threshold: 0.2 }
    );

    observer.observe(statsSection);
});