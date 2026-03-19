document.addEventListener("DOMContentLoaded", function () {
  const statNumbers = document.querySelectorAll(".stat-number");

  if (!statNumbers.length) return;

  let hasAnimated = false;

  function animateValue(element) {
    const target = parseFloat(element.dataset.target) || 0;
    const suffix = element.dataset.suffix || "";
    const decimals = parseInt(element.dataset.decimals || "0", 10);

    const duration = 1800;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // ease out
      const easedProgress = 1 - Math.pow(1 - progress, 3);
      const currentValue = target * easedProgress;

      if (decimals > 0) {
        element.textContent = currentValue.toFixed(decimals) + suffix;
      } else {
        element.textContent = Math.floor(currentValue).toLocaleString() + suffix;
      }

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        if (decimals > 0) {
          element.textContent = target.toFixed(decimals) + suffix;
        } else {
          element.textContent = Math.floor(target).toLocaleString() + suffix;
        }
      }
    }

    requestAnimationFrame(update);
  }

  const statsSection = document.querySelector(".stats-strip");

  if (!statsSection) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && !hasAnimated) {
          hasAnimated = true;
          statNumbers.forEach((number) => animateValue(number));
          observer.unobserve(statsSection);
        }
      });
    },
    { threshold: 0.35 }
  );

  observer.observe(statsSection);
});