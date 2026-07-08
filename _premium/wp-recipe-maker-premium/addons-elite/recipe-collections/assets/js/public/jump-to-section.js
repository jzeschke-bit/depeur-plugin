/**
 * Handle jump-to-section links inside recipe collections.
 * 
 * Prevents hash changes that would break HashRouter navigation
 * by intercepting clicks and manually scrolling to the target.
 * 
 * Note: Links with wprm-jump-smooth-scroll class or when
 * jump_output_hash is false are already handled by smooth-scroll.js.
 */

(function() {
    'use strict';

    // Container selectors for recipe collections
    const COLLECTION_CONTAINERS = [
        '#wprm-recipe-collections-app',
        '.wprm-recipe-saved-collections-app',
        '.wprm-shopping-list-app',
        '#wprm-recipe-collections-print-app'
    ];

    /**
     * Check if an element is inside a recipe collections container.
     */
    function isInsideCollectionContainer(element) {
        for (let selector of COLLECTION_CONTAINERS) {
            const container = document.querySelector(selector);
            if (container && container.contains(element)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Handle click on jump-to-section link.
     */
    function handleJumpToSectionClick(event, link) {
        // Only handle if inside a recipe collections container
        if (!isInsideCollectionContainer(link)) {
            return;
        }

        // Skip if already handled by smooth-scroll.js
        // (links with wprm-jump-smooth-scroll class or when jump_output_hash is false)
        if (link.classList.contains('wprm-jump-smooth-scroll')) {
            return;
        }

        // Check if hash output is disabled (handled by smooth-scroll.js)
        if (typeof wprm_public !== 'undefined' && 
            wprm_public.settings && 
            !wprm_public.settings.jump_output_hash) {
            return;
        }

        // Prevent default hash navigation
        event.preventDefault();
        event.stopPropagation();

        // Get the target hash from the href
        const href = link.getAttribute('href');
        if (!href || !href.startsWith('#')) {
            return;
        }

        const targetId = href.substring(1); // Remove the #
        const targetElement = document.getElementById(targetId) || document.querySelector(`[id="${targetId}"]`);

        if (!targetElement) {
            // Try to find element with the ID as class or data attribute
            const alternativeSelectors = [
                `[data-id="${targetId}"]`,
                `.${targetId}`
            ];
            
            for (let selector of alternativeSelectors) {
                const altElement = document.querySelector(selector);
                if (altElement) {
                    altElement.scrollIntoView({ behavior: 'auto', block: 'start' });
                    return;
                }
            }
            
            // Element not found, return silently to avoid breaking navigation
            return;
        }

        // Instant scroll (smooth scroll is handled by smooth-scroll.js)
        try {
            targetElement.scrollIntoView({ behavior: 'auto', block: 'start' });
        } catch (e) {
            // Fallback for older browsers
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset;
            window.scrollTo(0, offsetPosition);
        }
    }

    /**
     * Initialize jump-to-section link handling.
     */
    function init() {
        // Use event delegation to handle dynamically added links
        // Use capture phase to intercept before smooth-scroll.js (which uses bubble phase)
        document.addEventListener('click', function(event) {
            const link = event.target.closest('.wprm-recipe-jump-to-section');
            if (link) {
                handleJumpToSectionClick(event, link);
            }
        }, true);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

