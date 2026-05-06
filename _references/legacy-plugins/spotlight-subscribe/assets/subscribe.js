document.addEventListener("DOMContentLoaded", function() {
    // Function to hide all newsletter forms
    function hideNewsletterForms() {
        document.querySelectorAll('.spotlight-subscribe-wrapper').forEach(wrapper => {
            wrapper.style.display = 'none';
        });
    }

    // Check if user is already subscribed
    if (localStorage.getItem('newsletter_subscribed')) {
        hideNewsletterForms();
        return;
    }

    const wrappers = document.querySelectorAll('.spotlight-subscribe-wrapper');

    wrappers.forEach(wrapper => {
        // Handle close button clicks
        const closeButton = wrapper.querySelector('.spotlight-close-button');
        if (closeButton) {
            closeButton.addEventListener('click', function(e) {
                e.preventDefault();
                localStorage.setItem('newsletter_subscribed', 'true');
                hideNewsletterForms();
            });
        }

        // Set up intersection observer with adjusted thresholds
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                // Check if at least 30% of the element is visible
                if (entry.intersectionRatio > 0.3) {
                    entry.target.classList.add('in-view');
                } else {
                    entry.target.classList.remove('in-view');
                }
            });
        }, {
            threshold: [0, 0.3], // End effect when less than 30% visible
            rootMargin: '0px 0px -20% 0px' // Reduced bottom margin to end effect earlier
        });

        observer.observe(wrapper);

        // Handle form submission
        const form = wrapper.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                // Set localStorage when form is submitted
                localStorage.setItem('newsletter_subscribed', 'true');
            });
        }
    });
});
