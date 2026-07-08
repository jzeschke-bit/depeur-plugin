import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import  { parseQuantity, formatQuantity } from 'Shared/quantities';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.advancedServings = {
	load() {
		// Listen for serving changes.
		document.addEventListener( 'wprm-recipe-change', ( event ) => {
			if ( 'servings' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.advancedServings.adjustedServingsDirectly( recipe );
					}
				});
			}
			if ( 'advancedServings' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.advancedServings.updateAdvancedServingsDisplay( recipe );
					}
				});
			}
			if ( 'unitSystem' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.advancedServings.updateUnitOnUnitSystemChange( recipe );
					}
				});
			}
		});

		// Init.
		window.WPRecipeMaker.advancedServings.init();
	},
    init() {
		// Replace input fields with functionality, unless on print page.
		if ( ! document.querySelector( 'body' ).classList.contains( 'wprm-print' ) ) {
			const inputElements = document.querySelectorAll( 'span.wprm-recipe-advanced-servings-input' );

			for ( let inputElement of inputElements ) {
				let link = document.createElement( 'a' );
				
				// Content.
				link.innerHTML = inputElement.innerHTML;

				// Link attributes.
				link.setAttribute( 'href', '#' );
				link.setAttribute( 'role', 'button' );

				// Get attributes of original element.
				const attrs = [...inputElement.attributes].reduce((attrs, attribute) => {
					attrs[attribute.name] = attribute.value;
					return attrs;
				}, {});

				// Set same attributes for new link.
				for ( let attr of Object.keys( attrs ) ) {
					link.setAttribute( attr, attrs[ attr ] );
				}
				
				// Replace.
				inputElement.parentNode.replaceChild( link, inputElement );

				// Attach functionality.
				link.onclick = (e) => {
					e.preventDefault();
					WPRecipeMaker.advancedServings.onClickInput( e.target );
				}

				// Show tooltip.
				if ( attrs.hasOwnProperty( 'aria-label' ) ) {
					tippy( link, {
						theme: 'wprm',
						content: attrs['aria-label'],
					} );
				}
			}
		}
	},
	onClickInput( elem ) {
		if ( elem.classList.contains( 'wprm-recipe-advanced-servings-input-shape' ) ) {
			WPRecipeMaker.advancedServings.toggleShape( elem );
		} else if ( elem.classList.contains( 'wprm-recipe-advanced-servings-input-unit' ) ) {
			WPRecipeMaker.advancedServings.toggleUnit( elem );
		} else {
			WPRecipeMaker.advancedServings.changeNumber( elem );
		}
	},
	changeNumber( elem ) {
		WPRecipeMaker.advancedServings.getRecipeFromElem( elem ).then( ( recipe ) => {
			if ( recipe ) {
				const label = elem.getAttribute( 'aria-label' );
				const value = elem.innerText;

				const newValue = prompt( `${label}:`, value );
				if ( newValue ) {
					const parsedValue = WPRecipeMaker.advancedServings.parse( newValue );

					if ( parsedValue && 0 < parsedValue ) {
						let changes = {};
						changes[ elem.dataset.type ] = parsedValue,
						recipe.setAdvancedServings( changes );
					}
				}
			}
		} );
	},
	toggleShape( elem ) {
		WPRecipeMaker.advancedServings.getRecipeFromElem( elem ).then( ( recipe ) => {
			if ( recipe ) {
				const currentShape = recipe.data.currentAdvancedServings.shape;
				const newShape = 'round' === currentShape ? 'rectangle' : 'round';

				recipe.setAdvancedServings( { shape: newShape } );
			}
		} );
	},
	toggleUnit( elem ) {
		WPRecipeMaker.advancedServings.getRecipeFromElem( elem ).then( ( recipe ) => {
			if ( recipe ) {
				const currentUnit = recipe.data.currentAdvancedServings.unit;
				const newUnit = 'cm' === currentUnit ? 'inch' : 'cm';

				recipe.setAdvancedServings( { unit: newUnit } );
			}
		} );
	},
	adjustedServingsDirectly( recipe ) {
		const servingsFromAdvanced = WPRecipeMaker.advancedServings.getServingsFromAdvancedServings( recipe );
		const currentServings = recipe.data.currentServingsParsed;

		if ( currentServings !== servingsFromAdvanced ) {
			if ( currentServings === recipe.data.originalServingsParsed ) {
				// Recipes uses original servings, so set original advanced servings as well.
				recipe.setAdvancedServings( recipe.data.originalAdvancedServings );
			} else {
				// Values unknown now.
				recipe.setAdvancedServings( {
					diameter: '?',
					width: '?',
					length: '?',
				} );
			}
		}
	},
	getRecipeFromElem( elem ) {
		let container = false;

		for ( var parent = elem.parentNode; parent && parent != document; parent = parent.parentNode ) {
			if ( parent.matches( '.wprm-recipe-advanced-servings-container' ) ) {
				container = parent;
				break;
			}
		}

		if ( container ) {
			return window.WPRecipeMaker.manager.getRecipe( container.dataset.recipe );
		}

		return Promise.resolve( false );
	},
	updateUnitOnUnitSystemChange( recipe ) {
		if ( wprmp_public.settings.unit_conversion_advanced_servings_conversion ) {
			const newUnit = wprmp_public.settings[ `unit_conversion_system_${ recipe.data.currentSystem }_length_unit` ];

			if ( newUnit !== recipe.data.currentAdvancedServings.unit ) {
				recipe.setAdvancedServings( { unit: newUnit } );
			}
		}
	},
	updateAdvancedServingsDisplay( recipe ) {
		const containers = document.querySelectorAll( `.wprm-recipe-advanced-servings-${ recipe.id }-container` );
		const current = recipe.data.currentAdvancedServings;

		for ( let container of containers ) {
			// Selected shape.
			const shapeInput = container.querySelector( '.wprm-recipe-advanced-servings-input-shape' );
			shapeInput.innerHTML = shapeInput.dataset[`shape${ current.shape[0].toUpperCase() + current.shape.slice(1) }`];

			if ( 'round' === current.shape ) {
				container.querySelector( '.wprm-recipe-advanced-servings-round' ).style.display = '';
				container.querySelector( '.wprm-recipe-advanced-servings-rectangle' ).style.display = 'none';
			} else {
				container.querySelector( '.wprm-recipe-advanced-servings-rectangle' ).style.display = '';
				container.querySelector( '.wprm-recipe-advanced-servings-round' ).style.display = 'none';
			}

			// Selected unit.
			const unitInputs = container.querySelectorAll( '.wprm-recipe-advanced-servings-input-unit' );

			for ( let unitInput of unitInputs ) {
				unitInput.innerHTML = unitInput.dataset[`unit${ current.unit[0].toUpperCase() + current.unit.slice(1) }`];
			}

			// Numbers.
			container.querySelector( '.wprm-recipe-advanced-servings-input-diameter' ).innerHTML = WPRecipeMaker.advancedServings.format( current.diameter );
			container.querySelector( '.wprm-recipe-advanced-servings-input-width' ).innerHTML = WPRecipeMaker.advancedServings.format( current.width );
			container.querySelector( '.wprm-recipe-advanced-servings-input-length' ).innerHTML = WPRecipeMaker.advancedServings.format( current.length );

			// Optional height.
			if ( current.height ) {
				container.querySelector( '.wprm-recipe-advanced-servings-input-height' ).innerHTML = WPRecipeMaker.advancedServings.format( current.height );
			}
		}

		// Updated serving values, check if we have a valid servings value to set.
		const newServings = WPRecipeMaker.advancedServings.getServingsFromAdvancedServings( recipe );

		if ( newServings ) {
			recipe.setServings( newServings );
		}
	},
	getServingsFromAdvancedServings( recipe ) {
		if ( recipe ) {
			const current = recipe.data.currentAdvancedServings;
			const original = recipe.data.originalAdvancedServings;

			// Return false if some values are unknown.
			if ( '?' === current.height ) {
				return false;
			}
			if ( 'round' === current.shape ) {
				if ( '?' === current.diameter ) {
					return false;
				}
			} else {
				if ( '?' === current.width || '?' === current.length ) {
					return false;
				}
			}

			// All values we need are set, calculate.
			const usingHeight = 0 < original.height;

			let originalArea = WPRecipeMaker.advancedServings.getArea( original );
			let currentArea = WPRecipeMaker.advancedServings.getArea( current );

			if ( usingHeight ) {
				const originalHeight = 'inch' === original.unit ? original.height * 2.54 : original.height;
				const currentHeight = 'inch' === current.unit ? current.height * 2.54 : current.height;

				originalArea *= originalHeight;
				currentArea *= currentHeight;
			}

			const factor = currentArea / originalArea;
			return recipe.data.originalServingsParsed * factor;
		}

		return false;
	},
	getArea( values ) {
		let radius = values.diameter / 2;
		let width = values.width;
		let length = values.length;

		// Always use cm for area calculation.
		if ( 'inch' === values.unit ) {
			radius *= 2.54;
			width *= 2.54;
			length *= 2.54;
		}

		if ( 'round' === values.shape ) {
			return Math.PI * radius * radius;
		} else {
			return width * length;
		}
	},
	parse( quantity ) {
		return parseQuantity( quantity );
	},
	format( quantity ) {
		const formatted = formatQuantity( quantity, wprmp_public.settings.adjustable_servings_round_to_decimals );

		if ( isNaN( formatted ) ) {
			return quantity;
		} else {
			return formatted;
		}
	},
}

ready(() => {
	window.WPRecipeMaker.advancedServings.load();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}