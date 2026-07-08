import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import  { parseQuantity, formatQuantity } from 'Shared/quantities';
import { __wprm } from 'Shared/Translations';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.quantities = {
	load() {
		// Add event listeners.
		document.addEventListener( 'input', function(e) {
			if ( e.target.matches( 'input.wprm-recipe-servings' ) ) {
				WPRecipeMaker.quantities.inputChange( e.target );
			}
		}, false );
		document.addEventListener( 'change', function(e) {
			if ( e.target.matches( 'input.wprm-recipe-servings' ) ) {
				WPRecipeMaker.quantities.inputChange( e.target );
			}
		}, false );
		document.addEventListener( 'click', function(e) {
			if ( e.target.matches( '.wprm-recipe-servings-change' ) ) {
				WPRecipeMaker.quantities.changeClick( e.target );
			}
			if ( e.target.matches( '.wprm-recipe-adjustable-servings' ) ) {
				WPRecipeMaker.quantities.multiplierClick( e.target );
			}
		}, false );

		document.addEventListener( 'keypress', function(e) {
			if ( e.target.matches( '.wprm-recipe-servings-change' ) ) {
				const key = e.which || e.keyCode || 0;

				if ( 13 === key || 32 === key ) {
					WPRecipeMaker.quantities.changeClick( e.target );
					e.preventDefault();
				}
			}
		}, false );

		// Listen for serving changes.
		document.addEventListener( 'wprm-recipe-change', ( event ) => {
			if ( 'servings' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.quantities.updateServingsDisplay( recipe );
						WPRecipeMaker.quantities.updateAdjustables( recipe );

						// Backwards compatibility.
						document.dispatchEvent( new CustomEvent( 'wprmAdjustedServings', { detail: recipe.id } ) );
					}
				});
			}
			if ( 'unitSystem' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						// Might need to change fraction usage.
						WPRecipeMaker.quantities.updateAdjustables( recipe );
					}
				});
			}
		});

		// Init.
		window.WPRecipeMaker.quantities.init();

		// Check for initial servings URL parameter.
		if ( wprmp_public.settings.adjustable_servings_url ) {
			const param = wprmp_public.settings.adjustable_servings_url_param;

			if ( param ) {
				const urlParams = new URLSearchParams( window.location.search );
				const servings = parseFloat( urlParams.get( param ) );

				if ( ! isNaN( servings ) && 0 < servings ) {
					const recipes = window.WPRecipeMaker.manager.findRecipesOnPage();
					
					for ( let id of recipes ) {
						window.WPRecipeMaker.manager.getRecipe( id ).then( ( recipe ) => {
							recipe.setServings( servings );
						} );
					}
				}
			}
		}
	},
    init() {
		// Replace serving elements with functionality.
		const servingElements = document.querySelectorAll( '.wprm-recipe-servings' );

		for ( let servingElement of servingElements ) {
			if ( ! servingElement.dataset.hasOwnProperty( 'servings' ) ) {
				// Init different adjustable servings.
				const servings = this.parse( servingElement.innerText );

				if ( 0 < servings ) {
					servingElement.dataset.servings = servings;
					servingElement.dataset.originalServings = servings;

					// No adjusting on print pages.
					if ( ! document.querySelector( 'body' ).classList.contains( 'wprm-print' ) ) {
						if ( 'modern' === wprmp_public.settings.recipe_template_mode ) {
							if ( servingElement.classList.contains( 'wprm-recipe-servings-adjustable-tooltip' ) ) {
								this.initTooltipSlider( servingElement );
							} else if ( servingElement.classList.contains( 'wprm-recipe-servings-adjustable-text' ) ) {
								this.initTextInput( servingElement );
							} else if ( servingElement.classList.contains( 'wprm-recipe-servings-adjustable-text-buttons' ) ) {
								this.initTextButtonsInput( servingElement );
							}
						} else if ( wprmp_public.settings.features_adjustable_servings ) {
							if ( 'text_field' === wprmp_public.settings.servings_changer_display ) {
								this.initTextInput( servingElement );
							} else { // Default = Tooltip Slider
								this.initTooltipSlider( servingElement );
							}
						}
					}
				}
			}
		}
	},
	getRecipeIdFromElem( elem ) {
		let recipeId = elem.dataset.recipe;

		// Backwards compatibility.
		if ( ! recipeId ) {
			for ( var parent = elem.parentNode; parent && parent != document; parent = parent.parentNode ) {
				if ( parent.matches( '.wprm-recipe-container' ) ) {
					recipeId = parent.dataset.recipeId;
					break;
				}
			}
		}

		return recipeId;
	},
	initTextInput( elem ) {
		let servings = elem.dataset.servings,
			recipeId = this.getRecipeIdFromElem( elem ),
			ariaLabel = elem.getAttribute( 'aria-label' );

		if ( recipeId ) {
			// Construct input field.
			const input = '<input type="number" class="wprm-recipe-servings wprm-recipe-servings-' + recipeId + '" min="0" step="any" value="' + servings + '" data-recipe="' + recipeId + '" aria-label="' + ariaLabel + '" />';
			elem.outerHTML = input;
		}
	},
	initTextButtonsInput( elem ) {
		let servings = elem.dataset.servings,
			recipeId = this.getRecipeIdFromElem( elem ),
			ariaLabel = elem.getAttribute( 'aria-label' );

		if ( recipeId ) {
			// Button style.
			let buttonStyle = '';
			buttonStyle += 'background-color: ' + elem.dataset.buttonBackground + ';';
			buttonStyle += 'border-color: ' + elem.dataset.buttonBackground + ';';
			buttonStyle += 'color: ' + elem.dataset.buttonAccent + ';';
			buttonStyle += 'border-radius: ' + elem.dataset.buttonRadius + ';';

			// Input style.
			let inputStyle = '';
			inputStyle += 'border-color: ' + elem.dataset.buttonBackground + ';';

			// Construct input field.
			const decrement = '<span class="wprm-recipe-servings-decrement wprm-recipe-servings-change" style="' + buttonStyle + '" role="button" tabindex="0" aria-label="' + __wprm( 'Decrease serving size by 1' ) + '">–</span>';
			const input = '<input type="text" class="wprm-recipe-servings wprm-recipe-servings-' + recipeId + '" min="0" step="any" value="' + servings + '" data-recipe="' + recipeId + '" aria-label="' + ariaLabel + '" style="' + inputStyle + '"/>';
			const increment = '<span class="wprm-recipe-servings-increment wprm-recipe-servings-change" style="' + buttonStyle + '" role="button" tabindex="0" aria-label="' + __wprm( 'Increase serving size by 1' ) + '">+</span>';
			elem.outerHTML = '<span class="wprm-recipe-servings-text-buttons-container">' + decrement + input + increment + '</span>';
		}
	},
	initTooltipSlider( elem ) {
		let recipeId = this.getRecipeIdFromElem( elem ),
			ariaLabel = elem.getAttribute( 'aria-label' );

		if ( recipeId ) {
			// Wrap with link.
			let link = document.createElement('a');
			link.href = '#';
			link.classList.add( 'wprm-recipe-servings-link' );
			link.setAttribute( 'aria-label', ariaLabel );

			elem.parentNode.insertBefore( link, elem );
			link.appendChild( elem );

			// Add tooltip.
			const tooltip = tippy( link, {
				theme: 'wprm',
				content: '',
				onShow(instance) {
					window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
						if ( recipe ) {
							const servings = recipe.data.currentServingsParsed;
							const max = 20 < 2 * servings ? 2 * servings : 20;
	
							const countDecimals = function (value) {
								const parts = value.toString().split(".");
								return parts.length > 1 ? parts[1].length : 0;
							}
	
							const decimals = countDecimals( servings );
							const step = 1 / ( Math.pow( 10, decimals ) );
		
							instance.setContent( `<input id="wprm-recipe-servings-slider-input" type="range" min="1" max="${ max }" step="${ step }" value="${ servings }" data-recipe="${ recipeId }" class="wprm-recipe-servings-slider wprm-recipe-servings-${ recipeId }" aria-label="${ ariaLabel }" oninput="WPRecipeMaker.quantities.inputChange(this)" onchange="WPRecipeMaker.quantities.inputChange(this)"></input>` );
						} else {
							return false;
						}
					});
				},
				allowHTML: true,
				interactive: true,
				delay: [0, 250],
			});

			// Open tippy on click.
			link.onclick = ( e ) => {
				e.preventDefault();
				tooltip.show();

				setTimeout( () => {
					const input = document.getElementById('wprm-recipe-servings-slider-input');

					if ( input ) {
						input.focus();
					}
				}, 250);
			};
		}
	},
	inputChange( input ) {
		let servings = input.value,
			recipeId = input.dataset.recipe;

		if ( servings ) {
			// Track action for analytics.
			const type = input.classList.contains( 'wprm-recipe-servings-slider' ) ? 'slider' : 'input';
			window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'adjust-servings', {
				type,
			});

			this.setServings( recipeId, servings );
		}
	},
	changeClick( elem ) {
		const parent = elem.closest( '.wprm-recipe-servings-text-buttons-container' );

		if ( parent ) {
			const input = parent.querySelector( 'input' );
			const servings = this.parse( input.value );
			let newServings = servings;

			// Don't go to 0 or below when decrementing.
			if ( elem.classList.contains( 'wprm-recipe-servings-decrement' ) && servings > 1 ) {
				newServings--;
			} else if ( elem.classList.contains( 'wprm-recipe-servings-increment' ) ) {
				newServings++;
			}

			if ( newServings !== servings ) {
				input.value = newServings;

				// Trigger change.
				this.inputChange( input );
			}
		}
	},
	multiplierClick( elem ) {
		if ( ! elem.classList.contains( 'wprm-toggle-active' ) || '?' === elem.dataset.multiplier ) {
			const multiplier = elem.dataset.multiplier,
				recipeId = elem.dataset.recipe,
				servings = elem.dataset.servings;
			
			let newServings = false;

			if ( '?' === multiplier ) {
				newServings = prompt( elem.getAttribute( 'aria-label' ) );

				if ( newServings ) {
					newServings = this.parse( newServings );
				}
			} else {
				newServings = this.parse( servings ) * this.parse( multiplier );
			}

			if ( newServings ) {
				// Track action for analytics.
				window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'adjust-servings', {
					type: 'button',
				});

				this.setServings( recipeId, newServings );
			}
		}
	},
	setServings( recipeId, servings ) {
		window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
			if ( recipe ) {
				recipe.setServings( servings );
			}
		});
	},
	updateServingsDisplay( recipe ) {
		// Update servings.
		const containerId = recipe.data.hasOwnProperty( 'overrideContainerId' ) && false !== recipe.data.overrideContainerId ? recipe.data.overrideContainerId : recipe.id;
		const servingElems = document.querySelectorAll( '.wprm-recipe-servings-' + containerId );

		for ( let servingElem of servingElems ) {
			servingElem.textContent = recipe.data.currentServingsFormatted;
			servingElem.dataset.servings = recipe.data.currentServingsParsed;

			// Use raw value (4. instead of 4) for input fields.
			if ( 'input' === servingElem.tagName.toLowerCase() ) {
				if ( typeof recipe.data.currentServings === 'string' || recipe.data.currentServings instanceof String ) {
					// Servings set through text input field, set as is.
					servingElem.value = recipe.data.currentServings;
				} else {
					// Servings set through other field (buttons, slider, advanced servings), set rounded value.
					servingElem.value = recipe.data.currentServingsFormatted;
				}
			} else {
				servingElem.value = recipe.data.currentServingsParsed;
			}
		}

		// Check multiplier buttons.
		const multiplierContainers = document.querySelectorAll( '.wprm-recipe-adjustable-servings-' + containerId + '-container' );

		for ( let multiplierContainer of multiplierContainers ) {
			const multiplierButtons = multiplierContainer.querySelectorAll( '.wprm-recipe-adjustable-servings' );
			let matchFound = false;

			for ( let multiplierButton of multiplierButtons ) {
				multiplierButton.classList.remove( 'wprm-toggle-active' );

				if ( parseQuantity( multiplierButton.dataset.multiplier ) === recipe.data.currentServingsMultiplier ) {
					matchFound = true;
					multiplierButton.classList.add( 'wprm-toggle-active' );
				} else if ( '?' === multiplierButton.dataset.multiplier && ! matchFound ) {
					multiplierButton.classList.add( 'wprm-toggle-active' );
				}
			}
		}
	},
	updateAdjustables( recipe ) {
		if ( ! recipe.data.hasOwnProperty( 'adjustables' ) ) {
			window.WPRecipeMaker.quantities.findAdjustables( recipe );
		}

		for ( let adjustable of recipe.data.adjustables ) {
			if ( recipe.data.currentServingsParsed === recipe.data.originalServingsParsed ) {
				adjustable.elem.textContent = adjustable.original;
			} else {
				const newQuantity = recipe.data.currentServings * adjustable.unitQuantity;

				if ( ! isNaN( newQuantity ) ) {
					let allowFractions = true;

					// Maybe not allow fractions in the current unit conversion system.
					if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
						const system = recipe.data.hasOwnProperty( 'currentSystem' ) ? recipe.data.currentSystem : 1;
						allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ system }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ system }_fractions`] : true;
					}

					adjustable.elem.textContent = this.format( newQuantity, allowFractions );
				}
			}
		}
	},
	findAdjustables( recipe ) {
		let adjustables = recipe.data.hasOwnProperty( 'adjustables' ) ? recipe.data.adjustables : [];

		// Loop over existing adjustables to make sure they still exist.
		for ( let i = adjustables.length - 1; i >= 0; i-- ) {
			if ( ! document.body.contains( adjustables[i].elem ) ) {
				adjustables.splice( i, 1 );
			}
		}

		// Containers to check for adjustables.
		const containers = document.querySelectorAll( `#wprm-recipe-container-${ recipe.id }, .wprm-recipe-roundup-item-${ recipe.id }, .wprm-print-recipe-${ recipe.id }, .wprm-recipe-${ recipe.id }-ingredients-container, .wprm-recipe-${ recipe.id }-instructions-container, .wprm-cook-mode-${ recipe.id }` );

		for ( let container of containers ) {
			// Look for adjustable shortcode.
			const adjustablesElems = container.querySelectorAll( '.wprm-dynamic-quantity' );
			for ( let adjustablesElem of adjustablesElems ) {
				// Only do this once.
				if ( 0 === adjustablesElem.querySelectorAll( '.wprm-adjustable' ).length ) {
					// Surround all the number blocks
					let quantity = adjustablesElem.innerText;
	
					// Special case: .5
					if ( /^\.\d+\s*$/.test( quantity ) ) {
						adjustablesElem.innerHTML = '<span class="wprm-adjustable">' + quantity + '</span>';
					} else {
						const fractions = '\u00BC\u00BD\u00BE\u2150\u2151\u2152\u2153\u2154\u2155\u2156\u2157\u2158\u2159\u215A\u215B\u215C\u215D\u215E';
						const number_regex = '[\\d'+fractions+']([\\d'+fractions+'.,\\/\\s]*[\\d'+fractions+'])?';
						const substitution = '<span class="wprm-adjustable">$&</span>';
	
						quantity = quantity.replace( new RegExp( number_regex, 'g' ), substitution );
						adjustablesElem.innerHTML = quantity;
					}
				}
			}

			// WP Ultimate Recipe compatibility.
			const wpurpElems = container.querySelectorAll( '.wpurp-adjustable-quantity' );
			for ( let wpurpElem of wpurpElems ) {
				wpurpElem.classList.add( 'wprm-adjustable' );
			}

			// Init all adjustables.
			const adjustableElems = container.querySelectorAll( '.wprm-adjustable' );
			for ( let adjustableElem of adjustableElems ) {
				// Don't add again if already part of adjustables.
				if ( -1 !== adjustables.findIndex( (existingAdjustable) => existingAdjustable.elem === adjustableElem ) ) {
					continue;
				}

				// Add to adjustables.
				adjustables.push( {
					elem: adjustableElem,
					original: adjustableElem.innerText,
					unitQuantity: parseQuantity( adjustableElem.innerText ) / recipe.data.originalServingsParsed,
				} );
			}
		}

		window.WPRecipeMaker.manager.changeRecipeData( recipe.id, {
			adjustables,
		} );
	},
	parse( quantity ) {
		return parseQuantity( quantity );
	},
	format( quantity, allowFractions = true ) {
		return formatQuantity( quantity, wprmp_public.settings.adjustable_servings_round_to_decimals, allowFractions );
	},
}

ready(() => {
	window.WPRecipeMaker.quantities.load();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}