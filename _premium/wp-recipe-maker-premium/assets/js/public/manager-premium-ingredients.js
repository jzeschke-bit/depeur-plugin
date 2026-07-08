import  { parseQuantity, formatQuantity } from 'Shared/quantities';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.managerPremiumIngredients = {
	load() {
		// Listen for ingredient changes.
		document.addEventListener( 'wprm-recipe-change', ( event ) => {
			if ( 'servings' === event.detail.type || 'unitSystem' === event.detail.type ) {
				window.WPRecipeMaker.manager.getRecipe( event.detail.id ).then( ( recipe ) => {
					if ( recipe ) {
						WPRecipeMaker.managerPremiumIngredients.updateIngredientsDisplay( recipe );
					}
				});
			}
		});
	},
	getCurrentIngredients( recipe ) {
		// Needs to have happened first to make sure we have parsed amounts.
		if ( ! recipe.data.hasOwnProperty( 'ingredientsElements' ) ) {
			window.WPRecipeMaker.managerPremiumIngredients.findIngredientsElements( recipe );
		}

		let currentIngredients = [];

		const revertOriginal = recipe.data.currentServingsParsed === recipe.data.originalServingsParsed && recipe.data.currentSystem === recipe.data.originalSystem;

		for ( let i = 0; i < recipe.data.ingredients.length; i++ ) {
			const ingredient = recipe.data.ingredients[ i ];
			let currentIngredient = {}			

			for ( let i = 1; i <= 2; i++ ) {
				let unitSystem;
				let usingFallbackUnitSystem;

				if ( ingredient.unit_systems.hasOwnProperty( 'unit-system-' + i ) ) {
					unitSystem = ingredient.unit_systems[ 'unit-system-' + i ];
					usingFallbackUnitSystem = false;
				} else {
					// Pick the other one, which should be set
					unitSystem = ingredient.unit_systems[ 'unit-system-' + ( i % 2 + 1 ) ];
					usingFallbackUnitSystem = true;
				}

				// Check if fractions should be used in this unit system.
				let allowFractions = wprmp_public.settings.fractions_enabled;
			
				if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
					allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ i }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ i }_fractions`] : true;
				}

				// Check if we can set a quantity.
				let currentAmount = unitSystem.amount; // Default to original amount.
				let currentAmountString = unitSystem.amountString;
				let currentAmountParsed = false;
				let amountIsSingular = false;

				// Calulate the quantity based on the current servings.
				for ( let k = 0; k < unitSystem.amountParts.length; k++ ) {
					const amountPart = unitSystem.amountParts[ k ];
					const calculatedQuantity = amountPart.numberUnitQuantity * recipe.data.currentServingsParsed;

					if ( ! isNaN( calculatedQuantity ) && 0 < calculatedQuantity ) {
						// Use the first part to determine if the amount is singular and for the parsed amount.
						if ( 0 === k ) {
							amountIsSingular = calculatedQuantity <= 1;
							currentAmountParsed = calculatedQuantity;
						}
						
						// Replace this part with its formatted value.
						currentAmountString = currentAmountString.replace( `%wprm${k}%`, this.format( calculatedQuantity, allowFractions ) );
					}
				}

				// Make sure there is no %wprm0% placeholder left in currentAmountString.
				if ( false === /%wprm\d+%/g.test( currentAmountString ) ) {
					currentAmount = currentAmountString;
				}

				// Maybe just use the original amount.
				if ( revertOriginal ) {
					currentAmount = unitSystem.amount;
				} else {
					// If the amount is the same as the original, and we filled in an explicit value, is that value.
					if ( recipe.data.currentServingsParsed === recipe.data.originalServingsParsed && ! usingFallbackUnitSystem ) {
						currentAmount = unitSystem.amount;
					}
				}

				// Get unit.
				let currentUnit = unitSystem.unitParsed;

				if ( ! revertOriginal ) {
					// If the amount is the same as the original, and we filled in an explicit value, is that value.
					if ( recipe.data.currentServingsParsed === recipe.data.originalServingsParsed && ! usingFallbackUnitSystem ) {
						currentUnit = unitSystem.unitParsed;
					} else {
						const unitSingular = unitSystem.hasOwnProperty( 'unit_singular' ) ? unitSystem.unit_singular : false;
						const unitPlural = unitSystem.hasOwnProperty( 'unit_plural' ) ? unitSystem.unit_plural : false;

						currentUnit = unitSystem.unitParsed;
						if ( unitSingular && unitPlural ) {
							currentUnit = amountIsSingular ? unitSingular : unitPlural;
						}
					}
				}

				// Get name.
				let currentName = ingredient.name;
				let needsNameChange = false;

				if ( ! revertOriginal ) {
					const nameSingular = ingredient.hasOwnProperty( 'name_singular' ) ? ingredient.name_singular : false;
					const namePlural = ingredient.hasOwnProperty( 'name_plural' ) ? ingredient.name_plural : false;

					if ( nameSingular && namePlural ) {
						needsNameChange = true;
						currentName = amountIsSingular ? nameSingular : namePlural;
					}
				}

				// Set ingredient data.
				currentIngredient[ 'unit-system-' + i ] = {
					amount: currentAmount,
					amountParsed: currentAmountParsed,
					amountIsSingular,
					unit: currentUnit,
					name: currentName,
					needsNameChange,
				};
			}

			currentIngredients.push( currentIngredient );
		}

		return currentIngredients;
	},
	updateIngredientsDisplay( recipe ) {
		if ( ! recipe.data.hasOwnProperty( 'ingredientsElements' ) ) {
			window.WPRecipeMaker.managerPremiumIngredients.findIngredientsElements( recipe );
		}

		const containerId = recipe.data.hasOwnProperty( 'overrideContainerId' ) && false !== recipe.data.overrideContainerId ? recipe.data.overrideContainerId : recipe.id;
		const revertOriginal = recipe.data.currentServingsParsed === recipe.data.originalServingsParsed && recipe.data.currentSystem === recipe.data.originalSystem;
		const currentIngredients = window.WPRecipeMaker.managerPremiumIngredients.getCurrentIngredients( recipe );
		
		// Collect all splits for updating inline ingredients
		const splitsByParentUid = {};
		for ( let i = 0; i < recipe.data.ingredients.length; i++ ) {
			const ingredient = recipe.data.ingredients[ i ];
			if ( ingredient.splits && Array.isArray( ingredient.splits ) && ingredient.splits.length >= 2 ) {
				splitsByParentUid[ingredient.uid] = ingredient.splits;
			}
		}

			for ( let i = 0; i < recipe.data.ingredients.length; i++ ) {
				const ingredient = recipe.data.ingredients[ i ];
				const ingredientElements = recipe.data.ingredientsElements[ i ];
				const currentIngredient = currentIngredients[ i ];
				const getNameForAmount = ( amount, fallbackName = ingredient.name || '' ) => {
					let nameForAmount = fallbackName;
					const nameSingular = ingredient.hasOwnProperty( 'name_singular' ) ? ingredient.name_singular : false;
					const namePlural = ingredient.hasOwnProperty( 'name_plural' ) ? ingredient.name_plural : false;

					if ( nameSingular && namePlural && amount && ! isNaN( amount ) && amount > 0 ) {
						nameForAmount = amount <= 1 ? nameSingular : namePlural;
					}

					return nameForAmount;
				};
				const existingSystemNumbers = Object.keys( ingredient.unit_systems || {} )
					.map( ( key ) => {
						const match = key.match( /^unit-system-(\d+)$/ );
						return match ? parseInt( match[1], 10 ) : false;
					} )
					.filter( ( num ) => false !== num && ! isNaN( num ) )
					.sort( ( a, b ) => a - b );
				const getUnitForAmount = ( systemNumber, amount, fallbackUnit = '' ) => {
					let unitForAmount = fallbackUnit;
					const systemKey = 'unit-system-' + systemNumber;
					let originalSystem = ingredient.unit_systems && ingredient.unit_systems.hasOwnProperty( systemKey ) ? ingredient.unit_systems[ systemKey ] : false;

					// If this unit system is not available, use the first actual system on the ingredient
					// to still get correct singular/plural forms.
					if ( ! originalSystem && existingSystemNumbers.length > 0 ) {
						const fallbackSystemKey = 'unit-system-' + existingSystemNumbers[0];
						originalSystem = ingredient.unit_systems && ingredient.unit_systems.hasOwnProperty( fallbackSystemKey ) ? ingredient.unit_systems[ fallbackSystemKey ] : false;
					}

					if ( originalSystem ) {
						const unitSingular = originalSystem.hasOwnProperty( 'unit_singular' ) ? originalSystem.unit_singular : false;
						const unitPlural = originalSystem.hasOwnProperty( 'unit_plural' ) ? originalSystem.unit_plural : false;

						if ( unitSingular && unitPlural && amount && ! isNaN( amount ) && amount > 0 ) {
							unitForAmount = amount <= 1 ? unitSingular : unitPlural;
						}
					}

					return unitForAmount;
				};
				const parseBooleanDataValue = ( value, fallback = true ) => {
					if ( undefined === value || null === value || '' === value ) {
						return fallback;
					}

					const normalized = `${ value }`.toLowerCase();

					if ( [ '1', 'true', 'yes', 'on' ].includes( normalized ) ) {
						return true;
					}
					if ( [ '0', 'false', 'no', 'off' ].includes( normalized ) ) {
						return false;
					}

					return fallback;
				};
				const getBothUnitsStyleForLinkedIngredient = ( linkedIngredient ) => {
					const styleFromData = linkedIngredient.dataset.hasOwnProperty( 'bothUnitsStyle' ) ? linkedIngredient.dataset.bothUnitsStyle : '';
					if ( 'parentheses' === styleFromData || 'slash' === styleFromData ) {
						return styleFromData;
					}
					return '';
				};
				const shouldShowIdenticalBothUnits = ( linkedIngredient ) => {
					if ( linkedIngredient.dataset.hasOwnProperty( 'bothUnitsShowIdentical' ) ) {
						return parseBooleanDataValue( linkedIngredient.dataset.bothUnitsShowIdentical, true );
					}
					return true;
				};
				const shouldShowBothUnitsForLinkedIngredient = ( linkedIngredient, unitSystemSpans ) => {
					if ( linkedIngredient.dataset.hasOwnProperty( 'bothUnits' ) ) {
						return parseBooleanDataValue( linkedIngredient.dataset.bothUnits, false );
					}

					// Backward-compatible fallback for older markup.
					if ( linkedIngredient.dataset.hasOwnProperty( 'bothUnitsStyle' ) || linkedIngredient.dataset.hasOwnProperty( 'bothUnitsShowIdentical' ) ) {
						return true;
					}

					return unitSystemSpans && unitSystemSpans.length > 1;
				};
				const getSplitPercentageForId = ( splitId ) => {
					if ( null === splitId || isNaN( splitId ) ) {
						return null;
					}

					if ( ingredient.splits && Array.isArray( ingredient.splits ) ) {
						for ( let split of ingredient.splits ) {
							if ( parseInt( split.id, 10 ) === splitId && split.percentage !== undefined && split.percentage !== null && '' !== `${ split.percentage }` ) {
								const parsedPercentage = parseFloat( split.percentage );
								if ( ! isNaN( parsedPercentage ) ) {
									return parsedPercentage;
								}
							}
						}
					}

					return null;
				};

			// Update all ingredient elements for this ingredient.
			for ( let ingredientElement of ingredientElements ) {
				// Loop over all amount elements.
				for ( let j = 0; j < ingredientElement.amounts.length; j++ ) {
					const amount = ingredientElement.amounts[ j ];
					let system = recipe.data.currentSystem;

					if ( ingredientElement.showingBothUnitSystems && 1 === j ) {
						system = system % 2 + 1; // Show the other unit system.
					}

					// Ingredient values for this unit system.
					const currentSystemValues = currentIngredient[ 'unit-system-' + system ];

					// Set amounts first.
					if ( revertOriginal ) {
						amount.elem.innerHTML = amount.original;
					} else {
						amount.elem.innerHTML = currentSystemValues.amount;
					}

					// Set units (if there is one).
					if ( ingredientElement.units.hasOwnProperty( j ) ) {
						if ( revertOriginal ) {
							ingredientElement.units[ j ].elem.innerHTML = ingredientElement.units[ j ].original;
						} else {
							ingredientElement.units[ j ].elem.innerHTML = currentSystemValues.unit;
						}
					}
				}

				// Set ingredient name for current unit system.
				if ( ingredientElement.name ) {
					if ( currentIngredient[ 'unit-system-' + recipe.data.currentSystem ].needsNameChange ) {
						ingredientElement.name.elem.innerHTML = currentIngredient[ 'unit-system-' + recipe.data.currentSystem ].name;
					} else {
						ingredientElement.name.elem.innerHTML = ingredientElement.name.original;
					}
				}
			}

			// Update any associated ingredients based on UID.
			if ( false !== ingredient.uid && 1 <= ingredientElements.length ) {
				// Build selector for main ingredient and all its splits
				// Class names use dash instead of colon (e.g., "2-1" instead of "2:1")
				let selector = '.wprm-inline-ingredient-' + containerId + '-' + ingredient.uid + ', ' +
				               '.wprm-recipe-instruction-ingredient-' + containerId + '-' + ingredient.uid;
				
				// Add selectors for splits if they exist
				if ( splitsByParentUid[ingredient.uid] ) {
					for ( let split of splitsByParentUid[ingredient.uid] ) {
						if ( split.percentage !== undefined && split.percentage !== null ) {
							// Use dash instead of colon in class name
							const splitUidForClass = ingredient.uid + '-' + split.id;
							selector += ', .wprm-inline-ingredient-' + containerId + '-' + splitUidForClass;
							selector += ', .wprm-recipe-instruction-ingredient-' + containerId + '-' + splitUidForClass;
						}
					}
				}
				
				// Check if there are any linked ingredients to update.
				const linkedIngredients = document.querySelectorAll( selector );
			
				if ( 0 < linkedIngredients.length ) {
					// Construct text to use first.
					let ingredientString = '';
					let notesString = '';

					for ( let ingredientElement of ingredientElements ) {
						const ingredientClone = document.createElement( 'div' );
						ingredientClone.innerHTML = ingredientElement.elem.innerHTML;

						// If servings have changed, check if there is a span with class "wprm-dynamic-quantity" anywhere inside the clone and get it.
						if ( recipe.data.currentServingsParsed !== recipe.data.originalServingsParsed ) {
							const dynamicQuantities = ingredientClone.querySelectorAll( '.wprm-dynamic-quantity' );
							for ( let dynamicQuantity of dynamicQuantities ) {
								const value = parseQuantity( dynamicQuantity.innerText );

								if ( value && ! isNaN( value ) ) {
									const unitQuantity = value / recipe.data.originalServingsParsed;

									if ( unitQuantity && ! isNaN( unitQuantity ) ) {
										const newQuantity = recipe.data.currentServings * unitQuantity;

										if ( newQuantity && ! isNaN( newQuantity ) ) {
											let allowFractions = true;

											// Maybe not allow fractions in the current unit conversion system.
											if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
												const system = recipe.data.hasOwnProperty( 'currentSystem' ) ? recipe.data.currentSystem : 1;
												allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ system }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ system }_fractions`] : true;
											}

											dynamicQuantity.textContent = formatQuantity( newQuantity, wprmp_public.settings.adjustable_servings_round_to_decimals, allowFractions );
										}
									}
								}
							}
						}

						// Remove commas and hyphens from direct text nodes (notes identifier).
						Array.from(ingredientClone.childNodes).forEach(node => {
							if (node.nodeType === Node.TEXT_NODE) {
								node.textContent = node.textContent.replace(/[,-]/g, '');
							}
						});

						// Store and remove notes.
						const ingredientCloneNotes = ingredientClone.querySelector( '.wprm-recipe-ingredient-notes, .wprm-cook-mode-ingredient-notes' );
						if ( ingredientCloneNotes ) {
							notesString = ingredientCloneNotes.innerText;
							ingredientCloneNotes.remove();
						}

						// Remove checkbox.
						const ingredientCloneCheckbox = ingredientClone.querySelector( '.wprm-checkbox-container' );
						if ( ingredientCloneCheckbox ) { ingredientCloneCheckbox.remove(); }

						// Remove ingredient image.
						const ingredientCloneImage = ingredientClone.querySelector( '.wprm-recipe-ingredient-image' );
						if ( ingredientCloneImage ) { ingredientCloneImage.remove(); }

						// Get and clean up remaining text.
						ingredientString = ingredientClone.innerText;
						ingredientString = ingredientString.replace( /\s\s+/g, ' ' );
						ingredientString = ingredientString.trim();

						// Found a string to use in this element, no need to look further.
						if ( ingredientString ) {
							break;
						}
					}

					if ( ingredientString ) {
						for ( let linkedIngredient of linkedIngredients ) {
							let linkedIngredientText = ingredientString;
							// Check if this is a split - if so, recalculate based on split's percentage
							let isSplit = false;
							let splitPercentage = null;
							let splitId = null;
							
							// Check data attributes first (preferred method).
							if ( linkedIngredient.dataset.hasOwnProperty( 'splitId' ) && '' !== linkedIngredient.dataset.splitId ) {
								const parsedSplitId = parseInt( linkedIngredient.dataset.splitId, 10 );
								if ( ! isNaN( parsedSplitId ) ) {
									splitId = parsedSplitId;
								}
							}
							if ( linkedIngredient.dataset.hasOwnProperty( 'splitPercentage' ) && '' !== linkedIngredient.dataset.splitPercentage ) {
								const parsedSplitPercentage = parseFloat( linkedIngredient.dataset.splitPercentage );
								if ( ! isNaN( parsedSplitPercentage ) ) {
									splitPercentage = parsedSplitPercentage;
								}
							}
							
							// If needed, derive split ID from class name.
							if ( null === splitId ) {
								// Check if class name indicates it's a split (format: "uid:splitId")
								// Ensure ingredient.uid is numeric to prevent regex injection.
								const uid = parseInt( ingredient.uid, 10 );
								if ( ! isNaN( uid ) && uid >= 0 ) {
									const classList = linkedIngredient.className.split( ' ' );
									for ( let className of classList ) {
										// Legacy class style: ...-uid:splitId
										if ( className.includes( '-' + uid + ":" ) ) {
											const match = className.match( new RegExp( '-' + uid + ":(\\d+)" ) );
											if ( match && match[1] ) {
												splitId = parseInt( match[1], 10 );
											}
										} else if ( className.includes( '-' + uid + '-' ) ) {
											// Current class style: ...-uid-splitId
											const match = className.match( new RegExp( '-' + uid + '-(\\d+)$' ) );
											if ( match && match[1] ) {
												splitId = parseInt( match[1], 10 );
											}
										}
										
										if ( null !== splitId && ! isNaN( splitId ) ) {
											break;
										}
									}
								}
							}
							
							// If percentage is still unknown, derive it from the split ID.
							if ( ( null === splitPercentage || isNaN( splitPercentage ) ) && null !== splitId ) {
								splitPercentage = getSplitPercentageForId( splitId );
							}
							
							if ( null !== splitPercentage && ! isNaN( splitPercentage ) && splitPercentage >= 0 && splitPercentage <= 100 ) {
								isSplit = true;
							}
							
							// Check if this linked ingredient is showing both unit systems (inline ingredients with spans)
							const unitSystemSpans = linkedIngredient.querySelectorAll( '.wprm-recipe-ingredient-unit-system' );
							
								const linkedShowBothUnits = shouldShowBothUnitsForLinkedIngredient( linkedIngredient, unitSystemSpans );
							
								if ( unitSystemSpans.length >= 1 ) {
								// Unit systems are shown with spans (inline ingredients) - update each separately
								// Process in order: first span is system 1, second span is system 2
								let updatedAnySpan = false;
								
								for ( let k = 0; k < unitSystemSpans.length; k++ ) {
									const unitSystemSpan = unitSystemSpans[ k ];
									
									// Skip empty spans - if the span has no content, don't update it
									if ( ! unitSystemSpan.textContent || unitSystemSpan.textContent.trim() === '' ) {
										continue;
									}
									
									// Determine system number from the span's class (wprm-recipe-ingredient-unit-system-1 or -2)
									let systemNumber = k + 1; // Default to index-based (first = 1, second = 2)
									const className = unitSystemSpan.className || '';
									
									// Check for explicit system number in class name
									const system2Match = className.match( /wprm-recipe-ingredient-unit-system-2\b/ );
									const system1Match = className.match( /wprm-recipe-ingredient-unit-system-1\b/ );
									
									if ( system2Match ) {
										systemNumber = 2;
									} else if ( system1Match ) {
										systemNumber = 1;
									}
									// Otherwise use k + 1 (already set above)
									
									// Get the correct unit system's values from currentIngredient
									const systemValues = currentIngredient[ 'unit-system-' + systemNumber ];
									
									if ( systemValues ) {
										// Check if we have a valid amount (either parsed or from amount string)
										let amountToUse = null;
										
										if ( systemValues.amountParsed && ! isNaN( systemValues.amountParsed ) && systemValues.amountParsed > 0 ) {
											amountToUse = systemValues.amountParsed;
										} else if ( systemValues.amount ) {
											// Fallback: try to parse from amount string
											const parsed = parseQuantity( systemValues.amount );
											if ( parsed && ! isNaN( parsed ) && parsed > 0 ) {
												amountToUse = parsed;
											}
										}
										
										if ( amountToUse && ! isNaN( amountToUse ) && amountToUse > 0 ) {
											// If this is a split, calculate split amount from parent amount and percentage
											if ( isSplit && splitPercentage !== null && ! isNaN( splitPercentage ) && splitPercentage >= 0 && splitPercentage <= 100 ) {
												amountToUse = ( amountToUse * splitPercentage ) / 100;
											}
											
											if ( amountToUse && ! isNaN( amountToUse ) && amountToUse > 0 ) {
												let allowFractions = wprmp_public.settings.fractions_enabled;
												
												// Maybe not allow fractions in this unit conversion system.
												if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
													allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ systemNumber }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ systemNumber }_fractions`] : true;
												}
												
												const formattedQuantity = this.format( amountToUse, allowFractions );
												const unitToDisplay = isSplit ? getUnitForAmount( systemNumber, amountToUse, systemValues.unit ) : systemValues.unit;
												
												// Update the amount span within this unit system span
												const amountSpan = unitSystemSpan.querySelector( '.wprm-recipe-ingredient-amount' );
												
												if ( amountSpan ) {
													// System 2: Has proper span structure, update the span
													amountSpan.textContent = formattedQuantity;
													updatedAnySpan = true;
													
													// Update the unit span if it exists
													const unitSpan = unitSystemSpan.querySelector( '.wprm-recipe-ingredient-unit' );
													if ( unitSpan && unitToDisplay ) {
														unitSpan.textContent = unitToDisplay;
													}
												} else {
													// System 1: Plain text content, replace entire textContent
													// Build the new text: "amount unit" or just "amount" if no unit
													let newText = formattedQuantity;
													if ( unitToDisplay ) {
														newText = formattedQuantity + ' ' + unitToDisplay;
													}
													
													unitSystemSpan.textContent = newText;
													updatedAnySpan = true;
												}
											}
										}
									}
								}
								
								// Skip the text replacement below since we've updated the spans directly (if we updated at least one)
								if ( updatedAnySpan ) {
									// Inline ingredients with unit-system spans have the name outside those spans.
									// Update that trailing name as well so singular/plural switches correctly.
									let linkedIngredientNameText = ingredient.name || '';
									const currentSystemValues = currentIngredient[ 'unit-system-' + recipe.data.currentSystem ];

									if ( isSplit && splitPercentage !== null && ! isNaN( splitPercentage ) && splitPercentage >= 0 && splitPercentage <= 100 ) {
										let amountForName = null;

										if ( currentSystemValues ) {
											if ( currentSystemValues.amountParsed && ! isNaN( currentSystemValues.amountParsed ) && currentSystemValues.amountParsed > 0 ) {
												amountForName = currentSystemValues.amountParsed;
											} else if ( currentSystemValues.amount ) {
												const parsedAmountForName = parseQuantity( currentSystemValues.amount );
												if ( parsedAmountForName && ! isNaN( parsedAmountForName ) && parsedAmountForName > 0 ) {
													amountForName = parsedAmountForName;
												}
											}
										}

										if ( amountForName && ! isNaN( amountForName ) && amountForName > 0 ) {
											const splitAmountForName = ( amountForName * splitPercentage ) / 100;
											linkedIngredientNameText = getNameForAmount( splitAmountForName, ingredient.name || '' );
										}
									} else {
										linkedIngredientNameText = currentSystemValues && currentSystemValues.needsNameChange ? currentSystemValues.name : ingredient.name;
									}

									// Maybe include notes with linked ingredient.
									if ( notesString ) {
										const notesSeparator = linkedIngredient.dataset.hasOwnProperty( 'notesSeparator' ) ? linkedIngredient.dataset.notesSeparator : false;
										if ( false !== notesSeparator ) {
											let cleanedNotesString = notesString;

											// If surrounded by parentheses, remove.
											if ( cleanedNotesString.startsWith( '(' ) && cleanedNotesString.endsWith( ')' ) ) {
												cleanedNotesString = cleanedNotesString.substring( 1, cleanedNotesString.length - 1 );
											}

											// Added notes to linked ingredient.
											switch ( notesSeparator ) {
												case 'comma':
													linkedIngredientNameText += ', ' + cleanedNotesString;
													break;
												case 'dash':
													linkedIngredientNameText += ' - ' + cleanedNotesString;
													break;
												case 'parentheses':
													linkedIngredientNameText += ' (' + cleanedNotesString + ')';
													break;
												default:
													linkedIngredientNameText += ' ' + cleanedNotesString;
											}
										}
									}

									// Maybe separator after linked ingredient.
									if ( linkedIngredient.dataset.hasOwnProperty( 'separator' ) ) {
										linkedIngredientNameText += linkedIngredient.dataset.separator;
									}

									const nameElement = linkedIngredient.querySelector( '.wprm-recipe-ingredient-name' );
									if ( nameElement ) {
										nameElement.innerHTML = linkedIngredientNameText;
									} else {
										const textNodes = Array.from( linkedIngredient.childNodes ).filter( ( node ) => Node.TEXT_NODE === node.nodeType );

										if ( 0 < textNodes.length ) {
											textNodes[ textNodes.length - 1 ].textContent = ' ' + linkedIngredientNameText;
										} else if ( linkedIngredientNameText ) {
											linkedIngredient.appendChild( document.createTextNode( ' ' + linkedIngredientNameText ) );
										}
									}

									continue;
								}
								} else if ( ! isSplit && linkedShowBothUnits ) {
									// Regular (non-split) linked ingredient that should show both systems.
									// Build this directly from current ingredient data so display style can be controlled
									// by this linked ingredient's own settings.
									const parts = [];

									for ( let systemNum of existingSystemNumbers ) {
										const systemValues = currentIngredient[ 'unit-system-' + systemNum ];
										if ( systemValues && systemValues.amount ) {
											let unitText = systemValues.amount;
											if ( systemValues.unit ) {
												unitText += ' ' + systemValues.unit;
											}
											parts.push( unitText );
										}
									}

									let ingredientName = ingredient.name || '';
									const currentSystemValues = currentIngredient[ 'unit-system-' + recipe.data.currentSystem ];
									if ( currentSystemValues && currentSystemValues.needsNameChange ) {
										ingredientName = currentSystemValues.name;
									}

									if ( parts.length > 0 ) {
										const showIdenticalBothUnits = shouldShowIdenticalBothUnits( linkedIngredient );
										const bothUnitsStyle = getBothUnitsStyleForLinkedIngredient( linkedIngredient );
										let outputParts = parts.filter( ( part ) => !! part );

										if (
											! showIdenticalBothUnits
											&& 1 < outputParts.length
											&& outputParts.every( ( part ) => outputParts[0] === part )
										) {
											outputParts = [ outputParts[0] ];
										}

										let amountUnitText = outputParts.join( ' ' );
										if ( 1 < outputParts.length ) {
											if ( 'parentheses' === bothUnitsStyle ) {
												amountUnitText = outputParts[0] + ' (' + outputParts.slice( 1 ).join( ' ' ) + ')';
											} else if ( 'slash' === bothUnitsStyle ) {
												amountUnitText = outputParts[0] + ' / ' + outputParts.slice( 1 ).join( ' ' );
											}
										}

										linkedIngredientText = amountUnitText + ( ingredientName ? ' ' + ingredientName : '' );
									}
								} else if ( ! isSplit && ! linkedShowBothUnits ) {
									// Regular (non-split) linked ingredient that should only show one unit system.
									// Build this from current ingredient data instead of reusing ingredientString,
									// because ingredientString can originate from another block showing both systems.
									const system = recipe.data.hasOwnProperty( 'currentSystem' ) ? recipe.data.currentSystem : 1;
									const systemValues = currentIngredient[ 'unit-system-' + system ];
									const singleParts = [];

									if ( systemValues ) {
										if ( systemValues.amount ) {
											singleParts.push( systemValues.amount );
										}
										if ( systemValues.unit ) {
											singleParts.push( systemValues.unit );
										}

										const ingredientName = systemValues.needsNameChange ? systemValues.name : ingredient.name;
										if ( ingredientName ) {
											singleParts.push( ingredientName );
										}
									}

									if ( singleParts.length > 0 ) {
										linkedIngredientText = singleParts.join( ' ' );
									}
								} else if ( isSplit && splitPercentage !== null && ! isNaN( splitPercentage ) && splitPercentage >= 0 && splitPercentage <= 100 && linkedShowBothUnits ) {
								// Both unit systems are shown but linked ingredient is plain text (associated ingredients)
								// Build text with both unit systems using split amounts.
								const parts = [];
								
								for ( let systemNum of existingSystemNumbers ) {
									const systemValues = currentIngredient[ 'unit-system-' + systemNum ];
									
									if ( systemValues && systemValues.amountParsed && ! isNaN( systemValues.amountParsed ) && systemValues.amountParsed > 0 ) {
										// Calculate split amount from parent amount and percentage
										const splitAmount = ( systemValues.amountParsed * splitPercentage ) / 100;
										
										if ( splitAmount && ! isNaN( splitAmount ) ) {
											let allowFractions = wprmp_public.settings.fractions_enabled;
											
											// Maybe not allow fractions in this unit conversion system.
											if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
												allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ systemNum }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ systemNum }_fractions`] : true;
											}
											
											const formattedQuantity = this.format( splitAmount, allowFractions );
											const splitUnit = getUnitForAmount( systemNum, splitAmount, systemValues.unit );
											const unitText = splitUnit ? formattedQuantity + ' ' + splitUnit : formattedQuantity;
											parts.push( unitText );
										}
									}
								}
								
								// Use ingredient name based on the split amount in the active unit system.
								let ingredientName = ingredient.name || '';
								const currentSystemValues = currentIngredient[ 'unit-system-' + recipe.data.currentSystem ];
								if ( currentSystemValues ) {
									let amountForName = null;

									if ( currentSystemValues.amountParsed && ! isNaN( currentSystemValues.amountParsed ) && currentSystemValues.amountParsed > 0 ) {
										amountForName = currentSystemValues.amountParsed;
									} else if ( currentSystemValues.amount ) {
										const parsedAmountForName = parseQuantity( currentSystemValues.amount );
										if ( parsedAmountForName && ! isNaN( parsedAmountForName ) && parsedAmountForName > 0 ) {
											amountForName = parsedAmountForName;
										}
									}

									if ( amountForName && ! isNaN( amountForName ) && amountForName > 0 ) {
										const splitAmountForName = ( amountForName * splitPercentage ) / 100;
										ingredientName = getNameForAmount( splitAmountForName, ingredientName );
									}
								}
								
								if ( parts.length > 0 ) {
									const showIdenticalBothUnits = shouldShowIdenticalBothUnits( linkedIngredient );
									const bothUnitsStyle = getBothUnitsStyleForLinkedIngredient( linkedIngredient );
									let outputParts = parts.filter( ( part ) => !! part );

									// Respect the "show identical" setting when both unit systems resolve
									// to the same formatted amount+unit.
									if (
										! showIdenticalBothUnits
										&& 1 < outputParts.length
										&& outputParts.every( ( part ) => outputParts[0] === part )
									) {
										outputParts = [ outputParts[0] ];
									}

									let amountUnitText = outputParts.join( ' ' );

									if ( 1 < outputParts.length ) {
										if ( 'parentheses' === bothUnitsStyle ) {
											amountUnitText = outputParts[0] + ' (' + outputParts.slice( 1 ).join( ' ' ) + ')';
										} else if ( 'slash' === bothUnitsStyle ) {
											amountUnitText = outputParts[0] + ' / ' + outputParts.slice( 1 ).join( ' ' );
										}
									}

									linkedIngredientText = amountUnitText + ( ingredientName ? ' ' + ingredientName : '' );
								}
							} else if ( isSplit && splitPercentage !== null && ! isNaN( splitPercentage ) && splitPercentage >= 0 && splitPercentage <= 100 ) {
								// Single unit system split - use the current system's amount
								const system = recipe.data.hasOwnProperty( 'currentSystem' ) ? recipe.data.currentSystem : 1;
								const systemValues = currentIngredient[ 'unit-system-' + system ];
								
								if ( systemValues && systemValues.amountParsed && ! isNaN( systemValues.amountParsed ) && systemValues.amountParsed > 0 ) {
									// Calculate split amount from parent amount and percentage
									const splitAmount = ( systemValues.amountParsed * splitPercentage ) / 100;
									
									if ( splitAmount && ! isNaN( splitAmount ) ) {
										let allowFractions = wprmp_public.settings.fractions_enabled;
										
										// Maybe not allow fractions in the current unit conversion system.
										if ( wprmp_public.settings.unit_conversion_enabled && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
											allowFractions = wprmp_public.settings.hasOwnProperty( `unit_conversion_system_${ system }_fractions` ) ? wprmp_public.settings[`unit_conversion_system_${ system }_fractions`] : true;
										}
										
										const formattedQuantity = this.format( splitAmount, allowFractions );

										// Build split text directly to avoid duplicated quantities when source text contains
										// dynamic quantity placeholders.
										const ingredientName = getNameForAmount( splitAmount, ingredient.name || '' );
										const splitUnit = getUnitForAmount( system, splitAmount, systemValues.unit );
										const splitParts = [ formattedQuantity ];
										if ( splitUnit ) {
											splitParts.push( splitUnit );
										}
										if ( ingredientName ) {
											splitParts.push( ingredientName );
										}
										linkedIngredientText = splitParts.join( ' ' );
									}
								}
							}

							// Maybe include notes with linked ingredient.
							if ( notesString ) {
								const notesSeparator = linkedIngredient.dataset.hasOwnProperty( 'notesSeparator' ) ? linkedIngredient.dataset.notesSeparator : false;
								if ( false !== notesSeparator ) {
									// If surrounded by parentheses, remove.
									if ( notesString.startsWith( '(' ) && notesString.endsWith( ')' ) ) {
										notesString = notesString.substring( 1, notesString.length - 1 );
									}

									// Added notes to linked ingredient.
									switch ( notesSeparator ) {
										case 'comma':
											linkedIngredientText += ', ' + notesString;
											break;
										case 'dash':
											linkedIngredientText += ' - ' + notesString;
											break;
										case 'parentheses':
											linkedIngredientText += ' (' + notesString + ')';
											break;
										default:
											linkedIngredientText += ' ' + notesString;
									}
								}
							}

							// Maybe separator after linked ingredient.
							if ( linkedIngredient.dataset.hasOwnProperty( 'separator' ) ) {
								linkedIngredientText += linkedIngredient.dataset.separator;
							}

							// Only update text if we didn't already update the spans directly (for unit systems)
							// Reuse unitSystemSpans from earlier in the scope.
							if ( unitSystemSpans.length === 0 ) {
								// Preserve dynamic quantity markup in linked ingredient text.
								linkedIngredient.innerHTML = linkedIngredientText;
							}
						}
					}
				}
			}
		}

		// Maybe need to update adjustables (for example in unit name).
		window.WPRecipeMaker.quantities.findAdjustables( recipe );
	},
	findIngredientsElements( recipe ) {
		let ingredientsElements = [];

		// Go through all ingredients first.
		for ( let i = 0; i < recipe.data.ingredients.length; i++ ) {
			const ingredient = recipe.data.ingredients[ i ];

			for ( let system of Object.keys( ingredient.unit_systems ) ) {
				const unitSystem = ingredient.unit_systems[ system ];

				const amountStringParsed = window.WPRecipeMaker.managerPremiumIngredients.parseAmountString( unitSystem.amount, recipe.data.originalServingsParsed );

				recipe.data.ingredients[i].unit_systems[ system ] = {
					...recipe.data.ingredients[i].unit_systems[ system ],
					...amountStringParsed,
				}
			}

			// There could be multiple elements for one ingredient.
			ingredientsElements[ i ] = [];
		}

		// Containers to check for adjustables.
		const containerId = recipe.data.hasOwnProperty( 'overrideContainerId' ) && false !== recipe.data.overrideContainerId ? recipe.data.overrideContainerId : recipe.id;
		const containers = document.querySelectorAll( `#wprm-recipe-container-${ containerId }, .wprm-recipe-roundup-item-${ containerId }, .wprm-print-recipe-${ containerId }, .wprm-recipe-${ containerId }-ingredients-container, .wprm-recipe-${ containerId }-instructions-container, .wprm-cook-mode-${ containerId }` );

		for ( let container of containers ) {
			// Look for ingredients.
			const selectors = [ '.wprm-recipe-ingredient', '.wprm-cook-mode-ingredient' ];
			const ingredientElems = container.querySelectorAll( selectors.join( ',' ) );

			for ( let i = 0; i < ingredientElems.length; i++ ) {
				const ingredientElem = ingredientElems[ i ];

				if ( ! ingredientElem.dataset.hasOwnProperty( 'wprmParsed' ) ) {
					let ingredient = {
						elem: ingredientElem,
						showingBothUnitSystems: false,
						amounts: [],
						units: [],
						name: false,
						notes: false,
					}
					
					// Check if showing both unit systems. If class is present, we're showing both.
					if ( ingredientElem.querySelector( '.wprm-recipe-ingredient-unit-system' ) ) {
						ingredient.showingBothUnitSystems = true;
					}

					// Find amounts. Could be multiple if displaying multiple unit systems.
					const amountElems = ingredientElem.querySelectorAll( selectors.map( selector => selector + '-amount' ).join( ',' ) );

					for ( let amountElem of amountElems ) {
						ingredient.amounts.push( {
							elem: amountElem,
							original: amountElem.innerHTML,
							unitQuantity: parseQuantity( amountElem.innerText ) / recipe.data.originalServingsParsed,
						} );
					}

					// Find units. Could be multiple if displaying multiple unit systems.
					const unitElems = ingredientElem.querySelectorAll( selectors.map( selector => selector + '-unit' ).join( ',' ) );

					for ( let unitElem of unitElems ) {
						ingredient.units.push( {
							elem: unitElem,
							original: unitElem.innerHTML,
						} );
					}

					// Find name.
					const nameElem = ingredientElem.querySelector( selectors.map( selector => selector + '-name' ).join( ',' ) );

					if ( nameElem ) {
						ingredient.name = {
							elem: nameElem,
							original: nameElem.innerHTML,
						};
					}

					// Find notes.
					const notesElem = ingredientElem.querySelector( selectors.map( selector => selector + '-notes' ).join( ',' ) );

					if ( notesElem ) {
						ingredient.notes = {
							elem: notesElem,
							original: notesElem.innerHTML,
						};
					}

					// Mark element as parsed and add to array.
					ingredientElem.dataset.wprmParsed = true;
					if ( ingredientsElements.hasOwnProperty( i ) ) {
						ingredientsElements[ i ].push( ingredient );
					}
				}
			}
		}

		window.WPRecipeMaker.manager.changeRecipeData( recipe.id, {
			ingredientsElements,
		} );
	},
	parseAmountString( amountString, servings ) {
		// Replace HTML entities added by wp_json_encode.
		amountString = amountString.replace( /&quot;/g, '"' );
		amountString = amountString.replace( /&#39;/g, "'" );
		amountString = amountString.replace( /&amp;/g, '&' );

		// Find all numbers in the amount.
		let numbers = false;

		if ( /^\.\d+\s*$/.test( amountString ) ) {
			// Check for special case: .5
			numbers = [ amountString ];
		} else {
			const fractions = '\u00BC\u00BD\u00BE\u2150\u2151\u2152\u2153\u2154\u2155\u2156\u2157\u2158\u2159\u215A\u215B\u215C\u215D\u215E';
			const number_regex = '[\\d'+fractions+']([\\d'+fractions+'.,\\/\\s]*[\\d'+fractions+'])?';

			const matches = amountString.match( new RegExp( number_regex, 'g' ) );

			if ( matches ) {
				numbers = matches;
			}
		}

		let amountParts = [];

		// Replace parts with placeholders.
		if ( numbers ) {
			for ( let i = 0; i < numbers.length; i++ ) {
				const number = numbers[ i ];
				amountString = amountString.replace( number, `%wprmtemporaryplaceholder%` ); // Only replaces first occurrence. Don't use numbers in this placeholder to prevent issues with next replacements.

				const numberParsed = parseQuantity( number );
				amountParts.push(
					{
						number,
						numberParsed,
						numberUnitQuantity: numberParsed / servings,
					}
				);
			}

			// Now replace %wprmtemporaryplaceholder% with %wprm0%, %wprm1%, etc.
			for ( let i = 0; i < numbers.length; i++ ) {
				amountString = amountString.replace( '%wprmtemporaryplaceholder%', `%wprm${i}%` ); // Only replaces first occurrence.
			}
		}

		return {
			amountString,
			amountParts,
		};
	},
	format( quantity, allowFractions = true ) {
		return formatQuantity( quantity, wprmp_public.settings.adjustable_servings_round_to_decimals, allowFractions );
	},
}

ready(() => {
	window.WPRecipeMaker.managerPremiumIngredients.load();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}
