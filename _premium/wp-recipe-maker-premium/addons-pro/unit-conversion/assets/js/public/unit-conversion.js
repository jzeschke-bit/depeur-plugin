import '../../css/public/unit-conversion.scss';

import  { parseQuantity, formatQuantity } from 'Shared/quantities';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.conversion = {
    init() {
        // Add event listeners.
		document.addEventListener( 'click', function(e) {
			if ( e.target.matches( '.wprm-unit-conversion' ) ) {
                e.preventDefault();
				WPRecipeMaker.conversion.clickSystem( e.target );
			}
            if ( e.target.matches( '.wprm-unit-conversion-checkbox' ) ) {
                WPRecipeMaker.conversion.toggleSwitch( e.target );
            }
        }, false );
		document.addEventListener( 'change', function(e) {
			if ( e.target.matches( '.wprm-unit-conversion-dropdown' ) ) {
				WPRecipeMaker.conversion.changeDropdown( e.target );
			}
		}, false );

        // Listen for unit system changes.
		document.addEventListener( 'wprm-recipe-change', ( event ) => {
			if ( 'unitSystem' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.conversion.updateSelectors( recipe );
						WPRecipeMaker.conversion.updateTemperatures( recipe );

                        // Maybe remember the unit system that was picked.
                        if ( wprmp_public.settings.unit_conversion_enabled && wprmp_public.settings.unit_conversion_remember ) {
                            localStorage.setItem( 'wprm-unit-system', recipe.data.currentSystem );
                        }
					}
				});
			}
		});

        // Set preferred unit system after slight delay to make sure other features have had their init.
        setTimeout( () => {
            WPRecipeMaker.conversion.setPreferedUnitSystem();
        }, 100 );
    },
    setPreferedUnitSystem() {
        // Maybe set to preferred system of the current visitor.
        if ( wprmp_public.settings.unit_conversion_enabled && wprmp_public.settings.unit_conversion_remember ) {
            let savedSystem = localStorage.getItem( 'wprm-unit-system' );

            if ( savedSystem ) {
                savedSystem = parseInt( savedSystem );
                
                if ( ! isNaN( savedSystem ) && 0 < savedSystem ) {
                    const recipes = window.WPRecipeMaker.manager.findRecipesOnPage();

                    for ( let id of recipes ) {
                        window.WPRecipeMaker.manager.getRecipe( id ).then( ( recipe ) => {
                            if ( recipe && recipe.data.currentSystem !== savedSystem ) {
                                recipe.setUnitSystem( savedSystem );
                            }
                        });
                    }
                }
            }
        }
    },
    clickSystem( elem ) {
        const recipeId = elem.dataset.recipe,
            system = parseInt( elem.dataset.system );

        if ( ! elem.classList.contains( 'wprmpuc-active' ) ) {
            // Track unit conversion action for analytics.
            if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
                window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'unit-conversion', {
                    type: 'link',
                });
            }

            this.setSystem( recipeId, system );
        }
    },
    toggleSwitch( elem ) {
        const recipeId = elem.dataset.recipe;

        let system = elem.checked ? elem.dataset.onSystem : elem.dataset.offSystem;
        system = parseInt( system );

        if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
            window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'unit-conversion', {
                type: 'switch',
            });
        }

        this.setSystem( recipeId, system );
    },
    changeDropdown( elem ) {
        const recipeId = elem.dataset.recipe,
            system = parseInt( elem.value );

        // Track unit conversion action for analytics.
        if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
            window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'unit-conversion', {
                type: 'dropdown',
            });
        }

        this.setSystem( recipeId, system );
    },
    setSystem( recipeId, system ) {
        window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
			if ( recipe ) {
				recipe.setUnitSystem( system );
			}
		});
    },
    updateSelectors( recipe ) {
        const unitConversionChangers = document.querySelectorAll( '.wprm-unit-conversion-container-' + recipe.id );

        for ( let unitConversionChanger of unitConversionChangers ) {
            const dropdown = unitConversionChanger.querySelector('.wprm-unit-conversion-dropdown');
            const checkbox = unitConversionChanger.querySelector('.wprm-unit-conversion-checkbox');

            if ( dropdown ) {
                dropdown.value = recipe.data.currentSystem;
            } else if ( checkbox ) {
                checkbox.checked = recipe.data.currentSystem === parseInt( checkbox.dataset.onSystem );
            } else {
                const links = unitConversionChanger.querySelectorAll( '.wprm-unit-conversion' );
                for ( let link of links ) {
                    const linkSystem = parseInt( link.dataset.system );
                    if ( recipe.data.currentSystem === linkSystem ) {
                        link.classList.add( 'wprmpuc-active' );
                    } else {
                        link.classList.remove( 'wprmpuc-active' );
                    }
                }
            }
        }
    },
    updateTemperatures( recipe ) {
        if ( 'change' === wprmp_public.settings.unit_conversion_temperature ) {
            const newUnit = wprmp_public.settings[ `unit_conversion_system_${ recipe.data.currentSystem }_temperature` ];
            const containers = document.querySelectorAll( `#wprm-recipe-container-${ recipe.id }, .wprm-recipe-roundup-item-${ recipe.id }, .wprm-print-recipe-${ recipe.id }, .wprm-recipe-${ recipe.id }-instructions-container` );

            for ( let container of containers ) {
                const temperatures = container.querySelectorAll( '.wprm-temperature-container' );

                for ( let temperature of temperatures ) {
                    const temperatureData = WPRecipeMaker.temperature.getData( temperature );

                    const currentUnit = temperature.dataset.hasOwnProperty( 'currentUnit' ) ? temperature.dataset.currentUnit : temperatureData.unit;

                    if ( currentUnit !== newUnit ) {
                        let newValue;

                        // Reuse original value, if that's what we are converting to.
                        if ( newUnit === temperatureData.unit ) {
                            newValue = temperatureData.value;
                        } else {
                            const valueToConvert = parseQuantity( temperatureData.value );

                            if ( 'C' === newUnit ) {
                                newValue = (valueToConvert - 32) * 5/9;
                            } else {
                                newValue = (valueToConvert * 9/5) + 32;
                            }

                            // Maybe round to nearest multiple of 5 or 10.
                            if ( 'round_5' === wprmp_public.settings.unit_conversion_temperature_precision ) {
                                newValue = Math.round( newValue / 5 ) * 5;
                            } else if ( 'round_10' === wprmp_public.settings.unit_conversion_temperature_precision ) {
                                newValue = Math.round( newValue / 10 ) * 10;
                            }

                            newValue = formatQuantity( newValue, 0 );
                        }

                        temperature.querySelector('.wprm-temperature-value').innerHTML = newValue;
                        temperature.querySelector('.wprm-temperature-unit').innerHTML = ` °${ newUnit }`;
                        temperature.dataset.currentUnit = newUnit;
                    }
                }
            }
        }
    },
}

ready(() => {
	window.WPRecipeMaker.conversion.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}