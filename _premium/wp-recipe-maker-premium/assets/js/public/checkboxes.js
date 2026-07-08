window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.checkboxes = {
    initModern: () => {
		document.addEventListener( 'change', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-checkbox' ) ) {
					window.WPRecipeMaker.checkboxes.toggle( target, e );
					break;
				}
			}
        }, false );
    },
	toggle: ( el, e ) => {
        e.preventDefault();

        for ( var parent = el.parentNode; parent && parent != document; parent = parent.parentNode ) {
            if ( parent.matches( 'li' ) ) {
                parent.classList.toggle('wprm-checkbox-is-checked');
                break;
            }
        }
    },
    initLegacy: () => {
        let list_items = '';
        if ( wprmp_public.settings.template_ingredient_list_style == 'checkbox' && wprmp_public.settings.template_instruction_list_style == 'checkbox' ) {
            list_items = 'li.wprm-recipe-ingredient, li.wprm-recipe-instruction:not(.wprm-recipe-instruction-tip)';
        } else {
            list_items = wprmp_public.settings.template_ingredient_list_style == 'checkbox' ? 'li.wprm-recipe-ingredient' : 'li.wprm-recipe-instruction:not(.wprm-recipe-instruction-tip)';
        }

        const checkboxes = document.querySelectorAll( list_items );
        for ( let checkbox of checkboxes ) {
            checkbox.classList.add('wprm-list-checkbox-container');

            let clickableBox = document.createElement('span');
            clickableBox.classList.add( 'wprm-list-checkbox' );

            checkbox.insertBefore(clickableBox, checkbox.firstChild);
        }

		document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-list-checkbox' ) ) {
					window.WPRecipeMaker.checkboxes.click( target, e );
					break;
				}
			}
        }, false );
    },
    click: ( el, e ) => {
        e.preventDefault();

        for ( var parent = el.parentNode; parent && parent != document; parent = parent.parentNode ) {
            if ( parent.matches( '.wprm-list-checkbox-container' ) ) {
                el.classList.toggle('wprm-list-checkbox-checked');
                parent.classList.toggle('wprm-list-checkbox-checked');
                break;
            }
        }
    },
};

ready(() => {
    if ('legacy' === wprmp_public.settings.recipe_template_mode && ( wprmp_public.settings.template_ingredient_list_style == 'checkbox' || wprmp_public.settings.template_instruction_list_style == 'checkbox') ) {
        window.WPRecipeMaker.checkboxes.initLegacy();
    }

    if ('modern' === wprmp_public.settings.recipe_template_mode ) {
        window.WPRecipeMaker.checkboxes.initModern();
    }
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}
