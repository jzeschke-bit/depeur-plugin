import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.shareOptionsPopup = {
    init: () => {
        // Listen for clicks on container.
		document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-recipe-share-options-popup' ) ) {
					window.WPRecipeMaker.shareOptionsPopup.click( target, e );
					break;
				}
			}
        }, false );
    },
    click: ( el, e ) => {
        e.preventDefault();

        // Remove any existing tippy.
        const existingTippy = el._tippy;

        if ( existingTippy ) {
            existingTippy.destroy();
        }
        
        // Check if previous element has the popup container class
        const previousElement = el.previousElementSibling;
        if ( previousElement && previousElement.classList && previousElement.classList.contains( 'wprm-recipe-share-options-popup-container' ) ) {
            el.role = "button"; // Needed for accessibility.

            // Background color.
            let backgroundColor = '#333333';
            if ( previousElement.dataset.hasOwnProperty( 'color' ) ) {
                backgroundColor = previousElement.dataset.color;
            }

            tippy( el, {
                theme: 'wprm-variable',
                content: previousElement.innerHTML,
                allowHTML: true,
                interactive: true,
                trigger: 'click',
                onMount(instance) {
                    const tippyBox = instance.popper.querySelector('.tippy-box');
                    tippyBox.style.setProperty('--wprm-tippy-background', backgroundColor);
                },
                onCreate(instance) {
                    instance.show();
                },
            });
        }
    },
};

ready(() => {
    window.WPRecipeMaker.shareOptionsPopup.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}