document.addEventListener("DOMContentLoaded", function () {
    const statNumbers = document.querySelectorAll(".stat-number");
    if (statNumbers.length) {
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
        } else {
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
        }
    }

    const chips = document.querySelectorAll('.chip');
    const searchInput = document.getElementById('finderSearch');
    const grid = document.getElementById('finderGrid');
    const finderSection = document.querySelector('.finder-section');
    const searchUrl = finderSection?.dataset.searchUrl || '/search-destinations';
    const reviewCompose = document.querySelector('.review-compose');

    if (reviewCompose) {
        const summary = reviewCompose.querySelector('.review-compose__toggle');
        const panel = reviewCompose.querySelector('.review-compose__panel');
        let animation;
        let isClosing = false;
        let isExpanding = false;

        function finishAnimation(open) {
            reviewCompose.open = open;
            reviewCompose.style.height = '';
            reviewCompose.style.overflow = '';
            reviewCompose.classList.remove('is-closing', 'is-expanding');
            animation = null;
            isClosing = false;
            isExpanding = false;
        }

        function closeCompose() {
            isClosing = true;
            reviewCompose.classList.remove('is-expanding');
            reviewCompose.classList.add('is-closing');

            const startHeight = `${reviewCompose.offsetHeight}px`;
            const endHeight = `${summary.offsetHeight}px`;

            if (animation) {
                animation.cancel();
            }

            animation = reviewCompose.animate(
                {
                    height: [startHeight, endHeight]
                },
                {
                    duration: 220,
                    easing: 'ease-out'
                }
            );

            animation.onfinish = () => finishAnimation(false);
            animation.oncancel = () => {
                isClosing = false;
            };
        }

        function openCompose() {
            isExpanding = true;
            reviewCompose.classList.remove('is-closing');
            reviewCompose.classList.add('is-expanding');

            const startHeight = `${reviewCompose.offsetHeight}px`;
            reviewCompose.open = true;

            requestAnimationFrame(() => {
                const endHeight = `${summary.offsetHeight + panel.offsetHeight}px`;

                if (animation) {
                    animation.cancel();
                }

                animation = reviewCompose.animate(
                    {
                        height: [startHeight, endHeight]
                    },
                    {
                        duration: 260,
                        easing: 'ease-out'
                    }
                );

                animation.onfinish = () => finishAnimation(true);
                animation.oncancel = () => {
                    isExpanding = false;
                };
            });
        }

        summary?.addEventListener('click', function (event) {
            event.preventDefault();

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                reviewCompose.open = !reviewCompose.open;
                return;
            }

            reviewCompose.style.overflow = 'hidden';

            if (isClosing || !reviewCompose.open) {
                openCompose();
            } else if (isExpanding || reviewCompose.open) {
                closeCompose();
            }
        });
    }
    
    if (!chips.length || !searchInput || !grid) return;

    let activeFilter = 'all';
    let searchTimeout;

    function filterCards() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        
        // Show loading state
        grid.innerHTML = '<div class="loading-spinner"><i class="ri-loader-4-line"></i> Loading destinations...</div>';
        
        // Make AJAX request to search
        const params = new URLSearchParams({
            search: searchTerm,
            filter: activeFilter
        });

        fetch(`${searchUrl}?${params.toString()}`)
            .then(response => response.json())
            .then(places => {
                if (places.length === 0) {
                    grid.innerHTML = '<div class="no-results"><i class="ri-emotion-sad-line"></i> No destinations found. Try a different search!</div>';
                    return;
                }
                
                // Render the results
                grid.innerHTML = places.map(place => {
                    const isRestaurant = place.type === 'restaurant';
                    const name = escapeHtml(place.title || '');
                    const image = escapeHtml(place.image || '/images/default-place.jpg');
                    const location = escapeHtml(place.location || '');
                    const route = isRestaurant ? 
                        `/restaurants/${place.id}` : 
                        `/hotels/${place.id}`;
                    return `
                        <article class="place-card" data-type="${isRestaurant ? 'restaurant' : 'hotel'}">
                            <a href="${route}" class="place-card__link">
                                <img src="${image}" alt="${name}">
                                <div class="place-card__overlay"></div>
                                <div class="place-card__content">
                                    <h4>${name}</h4>
                                    <p><i class="ri-map-pin-2-fill"></i> ${location}</p>
                                </div>
                            </a>
                        </article>
                    `;
                }).join('');
            })
            .catch(error => {
                console.error('Error:', error);
                grid.innerHTML = '<div class="error-message"><i class="ri-error-warning-line"></i> Something went wrong. Please try again.</div>';
            });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            chips.forEach(btn => btn.classList.remove('chip--active'));
            this.classList.add('chip--active');
            activeFilter = this.dataset.filter;
            filterCards();
        });
    });
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterCards, 300); // Debounce search
    });
});
