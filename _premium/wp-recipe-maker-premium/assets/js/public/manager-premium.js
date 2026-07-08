import  { parseQuantity, formatQuantity } from 'Shared/quantities';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.managerPremium = {
	loadRecipeObject: ( id, recipe ) => {
		// Add premium functionality to recipe object.
		return {
			...recipe,
            data: {
                ...recipe.data,
            },
            addRating: ( data ) => {
                // Maybe track analytics. Only for GA tracking, local tracking is through PHP.
                if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
                    window.WPRecipeMaker.analytics.registerAction( id, wprm_public.post_id, 'user-rating', { rating: data.rating } );
                }

                // Save individual rating and get new totals after save.
                return window.WPRecipeMaker.userRating.addRatingForRecipe( data, id ).then( ( newRating ) => {
                    if ( false !== newRating ) {
                        window.WPRecipeMaker.manager.changeRecipeData( id, {
                            rating: newRating,
                        } );
        
                        window.WPRecipeMaker.manager.triggerChangeEvent( id, 'rating' );
                        return true;
                    }

                    return false;
                });
            },
			setServings: ( servings, overrideContainerId = false ) => {
                const parsed = parseQuantity( servings );
                const formatted = formatQuantity( parsed, wprmp_public.settings.adjustable_servings_round_to_decimals, false );
                const multiplier = parsed / recipe.data.originalServingsParsed;

                window.WPRecipeMaker.manager.changeRecipeData( id, {
                    currentServings: servings,
                    currentServingsParsed: parsed,
                    currentServingsFormatted: formatted,
                    currentServingsMultiplier: multiplier,
                    overrideContainerId, // Is used to target a different container than the recipe itself. Needed when printing the same recipe multiple times in different servings.
                } );

                window.WPRecipeMaker.manager.triggerChangeEvent( id, 'servings' );
			},
            setAdvancedServings: ( changes ) => {
                // Make sure we're using the current values.
                window.WPRecipeMaker.manager.getRecipe( id ).then( ( currentRecipe ) => {
                    // Check if values need to change because of unit switch.
                    if ( changes.hasOwnProperty( 'unit' ) && changes.unit !== currentRecipe.data.currentAdvancedServings.unit ) {
                        const inchToCm = 2.54;
                        const factor = 'cm' === changes.unit ? inchToCm : 1 / inchToCm;

                        if ( '?' !== currentRecipe.data.currentAdvancedServings.diameter ) { changes.diameter = Math.round( currentRecipe.data.currentAdvancedServings.diameter * factor ); }
                        if ( '?' !== currentRecipe.data.currentAdvancedServings.width ) { changes.width = Math.round( currentRecipe.data.currentAdvancedServings.width * factor ); }
                        if ( '?' !== currentRecipe.data.currentAdvancedServings.length ) { changes.length = Math.round( currentRecipe.data.currentAdvancedServings.length * factor ); }
                        if ( '?' !== currentRecipe.data.currentAdvancedServings.height ) { changes.height = Math.round( currentRecipe.data.currentAdvancedServings.height * factor ); }
                    }

                    window.WPRecipeMaker.manager.changeRecipeData( id, {
                        currentAdvancedServings: {
                            ...currentRecipe.data.currentAdvancedServings,
                            ...changes,
                        },
                    } );

                    window.WPRecipeMaker.manager.triggerChangeEvent( id, 'advancedServings' );
                } );
            },
            setUnitSystem: ( system ) => {
                system = parseInt( system );

                // Check if recipe actually has this unit system available.
                if ( ! recipe.data.unitSystems.includes( system ) ) {
                    return;
                }

                window.WPRecipeMaker.manager.changeRecipeData( id, {
                    currentSystem: system,
                } );

                window.WPRecipeMaker.manager.triggerChangeEvent( id, 'unitSystem' );
			},
		};
	},
};