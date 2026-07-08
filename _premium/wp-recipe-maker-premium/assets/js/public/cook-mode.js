import '../../css/public/cook-mode.scss';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.cookMode = {
	currentModalElement: null,
	currentModalUid: null,
	currentScreen: 'overview',
	currentStepIndex: 0,
	totalSteps: 0,
	completedSteps: new Set(),
	currentRecipeId: null,
	savedProgress: {}, // Store progress per recipe ID during session
	touchStartX: 0,
	touchStartY: 0,
	touchEndX: 0,
	touchEndY: 0,
	eventHandlers: [],

	init() {
		// Check for opening cook mode modals.
		document.addEventListener( 'wprm-modal-open', ( event ) => {
			if ( 'cook-mode' === event.detail.type ) {
				window.WPRecipeMaker.cookMode.opened( event.detail.uid, event.detail.modal, event.detail.data );
			}
		});

		// Check for closing cook mode modals.
		document.addEventListener( 'wprm-modal-close', ( event ) => {
			if ( 'cook-mode' === event.detail.type ) {
				window.WPRecipeMaker.cookMode.closed( event.detail.uid, event.detail.modal, event.detail.data );
			}
		});

		document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				// Check for clicking on Cook Mode button.
				if ( target.matches( '.wprm-recipe-cook-mode' ) ) {
					e.preventDefault();
					const uid = target.dataset.modalUid;
					let recipeId = target.dataset.recipeId;
					recipeId = parseInt( recipeId );

					if ( uid && recipeId ) {
						window.WPRecipeMaker.modal.open( uid, {
							recipe: recipeId,
						} );
					}

					break;
				}
			}
		}, false );
	},

	async opened( uid, modal, data ) {
		const recipeId = data.recipe;
		if ( ! recipeId ) {
			return;
		}

		// Find the modal container
		const modalElement = document.getElementById( 'wprm-popup-modal-' + uid );
		if ( ! modalElement ) {
			return;
		}

		const cookModeContainer = modalElement.querySelector( '.wprm-cook-mode' );
		if ( ! cookModeContainer ) {
			return;
		}

		// Store the modal element, UID, and recipe ID
		this.currentModalElement = modalElement;
		this.currentModalUid = uid;
		this.currentRecipeId = recipeId;

		if ( window.WPRecipeMaker.timer ) {
			window.WPRecipeMaker.timer.clearCookModeTimers( modalElement );
			window.WPRecipeMaker.timer.bindTimers( modalElement );
		}

		// Count total steps from rendered HTML
		const instructionElements = modalElement.querySelectorAll( '.wprm-cook-mode-instruction-step' );
		this.totalSteps = instructionElements.length;

		// Check if we have saved progress for this recipe in this session
		const savedProgress = this.savedProgress[ recipeId ];
		if ( savedProgress ) {
			// Restore saved progress
			this.currentScreen = savedProgress.screen || 'overview';
			// Ensure step index is valid (not beyond total steps)
			const savedStepIndex = savedProgress.stepIndex || 0;
			this.currentStepIndex = Math.min( savedStepIndex, this.totalSteps > 0 ? this.totalSteps - 1 : 0 );
			this.completedSteps = new Set( savedProgress.completedSteps || [] );
		} else {
			// No saved progress, start fresh
			this.currentScreen = 'overview';
			this.currentStepIndex = 0;
			this.completedSteps.clear();
		}

		// Get recipe from manager and set current values
		try {
			const recipe = await window.WPRecipeMaker.manager.getRecipe( recipeId );
			if ( recipe && recipe.data ) {
				const currentServings = recipe.data.currentServingsParsed;
				const currentSystem = recipe.data.currentSystem;

				// Set servings input value
				if ( currentServings !== undefined && currentServings !== null ) {
					const servingsInput = modalElement.querySelector( '.wprm-cook-mode-servings-input' );
					if ( servingsInput ) {
						servingsInput.value = currentServings;
					}

					// Update servings button states
					const servingsDecrease = modalElement.querySelector( '.wprm-cook-mode-servings-decrease' );
					const servingsIncrease = modalElement.querySelector( '.wprm-cook-mode-servings-increase' );
					if ( servingsDecrease && servingsIncrease ) {
						this.updateServingsButtons( servingsDecrease, servingsIncrease, currentServings );
					}
				}

				// Set unit system button active state
				if ( currentSystem !== undefined && currentSystem !== null ) {
					const unitButtons = modalElement.querySelectorAll( '.wprm-cook-mode-unit-button' );
					unitButtons.forEach( button => {
						if ( parseInt( button.dataset.system ) === currentSystem ) {
							button.classList.add( 'active' );
						} else {
							button.classList.remove( 'active' );
						}
					});
				}

				if ( window.WPRecipeMaker.managerPremiumIngredients && window.WPRecipeMaker.managerPremiumIngredients.updateIngredientsDisplay ) {
					window.WPRecipeMaker.managerPremiumIngredients.updateIngredientsDisplay( recipe );
				}
			}
		} catch ( error ) {
			console.error( 'Error getting recipe data:', error );
		}

		// Restore screen state if we have saved progress
		if ( savedProgress ) {
			if ( savedProgress.screen === 'cooking' ) {
				// Switch to cooking screen
				const overviewScreen = modalElement.querySelector( '.wprm-cook-mode-screen-overview' );
				const cookingScreen = modalElement.querySelector( '.wprm-cook-mode-screen-cooking' );

				if ( overviewScreen ) {
					overviewScreen.style.display = 'none';
				}

				if ( cookingScreen ) {
					cookingScreen.style.display = 'flex';
				}

				// Hide start button and show navigation/progress in footer
				const startButton = modalElement.querySelector( '.wprm-cook-mode-start-button' );
				const cookingFooter = modalElement.querySelector( '.wprm-cook-mode-cooking-footer' );

				if ( startButton ) {
					startButton.style.display = 'none';
				}

				if ( cookingFooter ) {
					cookingFooter.style.display = 'block';
				}
			} else if ( savedProgress.screen === 'thank-you' ) {
				// Switch to thank-you screen
				this.showThankYouScreen();
			}
		}

		// Initialize
		this.setupEventListeners();
		this.setupBeforeUnloadWarning();
		this.updateStepDisplay();
		this.updateProgress();
	},

	closed( uid, modal, data ) {
		// Save progress to memory before closing
		if ( this.currentRecipeId ) {
			this.savedProgress[ this.currentRecipeId ] = {
				screen: this.currentScreen,
				stepIndex: this.currentStepIndex,
				completedSteps: Array.from( this.completedSteps ),
			};
		}

		// Clean up event listeners
		this.cleanupEventListeners();
		this.removeBeforeUnloadWarning();

		if ( window.WPRecipeMaker.timer ) {
			window.WPRecipeMaker.timer.clearCookModeTimers( this.currentModalElement );
		}
		
		// Release wake lock if it was set by cook mode
		if ( window.WPRecipeMaker.preventSleep ) {
			window.WPRecipeMaker.preventSleep.unlock();
		}

		// Reset current state (but keep savedProgress in memory)
		this.currentModalElement = null;
		this.currentModalUid = null;
		this.currentRecipeId = null;
		this.currentScreen = 'overview';
		this.currentStepIndex = 0;
		this.totalSteps = 0;
		this.completedSteps.clear();
	},

	setupEventListeners() {
		if ( ! this.currentModalElement ) {
			return;
		}

		// Clear any existing handlers first
		this.cleanupEventListeners();

		// Servings controls
		const servingsDecrease = this.currentModalElement.querySelector( '.wprm-cook-mode-servings-decrease' );
		const servingsIncrease = this.currentModalElement.querySelector( '.wprm-cook-mode-servings-increase' );
		const servingsInput = this.currentModalElement.querySelector( '.wprm-cook-mode-servings-input' );

		if ( servingsDecrease ) {
			const handler = () => {
				const current = parseFloat( servingsInput.value ) || 1;
				const newValue = Math.max( 1, current - 1 );
				servingsInput.value = newValue;
				this.updateServings( newValue );
				this.updateServingsButtons( servingsDecrease, servingsIncrease, newValue );
			};
			servingsDecrease.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: servingsDecrease, event: 'click', handler: handler } );
		}

		if ( servingsIncrease ) {
			const handler = () => {
				const current = parseFloat( servingsInput.value ) || 1;
				const newValue = current + 1;
				servingsInput.value = newValue;
				this.updateServings( newValue );
				this.updateServingsButtons( servingsDecrease, servingsIncrease, newValue );
			};
			servingsIncrease.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: servingsIncrease, event: 'click', handler: handler } );
		}

		if ( servingsInput ) {
			const handler = () => {
				const newValue = parseFloat( servingsInput.value ) || 1;
				this.updateServings( newValue );
				this.updateServingsButtons( servingsDecrease, servingsIncrease, newValue );
			};
			servingsInput.addEventListener( 'change', handler );
			this.eventHandlers.push( { element: servingsInput, event: 'change', handler: handler } );

			// Update button states on initial load
			if ( servingsDecrease && servingsIncrease ) {
				const initialValue = parseFloat( servingsInput.value ) || 1;
				this.updateServingsButtons( servingsDecrease, servingsIncrease, initialValue );
			}
		}

		// Unit system buttons
		const unitButtons = this.currentModalElement.querySelectorAll( '.wprm-cook-mode-unit-button' );
		unitButtons.forEach( button => {
			const handler = () => {
				const system = parseInt( button.dataset.system );
				this.updateUnitSystem( system );
			};
			button.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: button, event: 'click', handler: handler } );
		});

		// Start cooking button
		const startButton = this.currentModalElement.querySelector( '.wprm-cook-mode-start-button' );
		if ( startButton ) {
			const handler = () => {
				this.startCooking();
			};
			startButton.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: startButton, event: 'click', handler: handler } );
		}

		// Navigation buttons
		const prevButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-prev' );
		const nextButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-next' );

		if ( prevButton ) {
			const handler = () => {
				this.goToPreviousStep();
			};
			prevButton.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: prevButton, event: 'click', handler: handler } );
		}

		if ( nextButton ) {
			const handler = () => {
				this.goToNextStep();
			};
			nextButton.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: nextButton, event: 'click', handler: handler } );
		}

		// Close button
		const closeButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-close' );
		if ( closeButton ) {
			const handler = () => {
				this.closeModal();
			};
			closeButton.addEventListener( 'click', handler );
			this.eventHandlers.push( { element: closeButton, event: 'click', handler: handler } );
		}

		// X button in modal header (data-micromodal-close)
		const modalXButton = this.currentModalElement.querySelector( '.wprm-popup-modal__close, [data-micromodal-close]' );
		if ( modalXButton ) {
			const handler = ( e ) => {
				e.preventDefault();
				e.stopPropagation();
				this.closeModal();
			};
			modalXButton.addEventListener( 'click', handler, true ); // Use capture phase to intercept before MicroModal
			this.eventHandlers.push( { element: modalXButton, event: 'click', handler: handler, options: true } );
		}

		// Swipe support
		this.setupSwipeSupport();

		// Keyboard support for arrow keys
		this.setupKeyboardSupport();
	},

	setupSwipeSupport() {
		// Swipe support for overview screen
		const overviewScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-overview' );
		if ( overviewScreen ) {
			const touchStartHandler = ( e ) => {
				this.touchStartX = e.changedTouches[0].screenX;
				this.touchStartY = e.changedTouches[0].screenY;
			};
			const touchEndHandler = ( e ) => {
				this.touchEndX = e.changedTouches[0].screenX;
				this.touchEndY = e.changedTouches[0].screenY;
				this.handleSwipe();
			};
			const touchOptions = { passive: true };
			overviewScreen.addEventListener( 'touchstart', touchStartHandler, touchOptions );
			overviewScreen.addEventListener( 'touchend', touchEndHandler, touchOptions );
			this.eventHandlers.push( { element: overviewScreen, event: 'touchstart', handler: touchStartHandler, options: touchOptions } );
			this.eventHandlers.push( { element: overviewScreen, event: 'touchend', handler: touchEndHandler, options: touchOptions } );
		}

		// Swipe support for cooking screen
		const instructionsContainer = this.currentModalElement.querySelector( '.wprm-cook-mode-instructions-container' );
		if ( instructionsContainer ) {
			const touchStartHandler = ( e ) => {
				this.touchStartX = e.changedTouches[0].screenX;
				this.touchStartY = e.changedTouches[0].screenY;
			};
			const touchEndHandler = ( e ) => {
				this.touchEndX = e.changedTouches[0].screenX;
				this.touchEndY = e.changedTouches[0].screenY;
				this.handleSwipe();
			};
			const touchOptions = { passive: true };
			instructionsContainer.addEventListener( 'touchstart', touchStartHandler, touchOptions );
			instructionsContainer.addEventListener( 'touchend', touchEndHandler, touchOptions );
			this.eventHandlers.push( { element: instructionsContainer, event: 'touchstart', handler: touchStartHandler, options: touchOptions } );
			this.eventHandlers.push( { element: instructionsContainer, event: 'touchend', handler: touchEndHandler, options: touchOptions } );
		}
	},

	setupKeyboardSupport() {
		// Handle keyboard navigation with arrow keys
		const keyboardHandler = ( e ) => {
			// Only handle if modal is open
			if ( ! this.currentModalElement ) {
				return;
			}

			// Handle ESC key - check for running timers before allowing close
			if ( e.key === 'Escape' ) {
				if ( this.hasRunningTimers() ) {
					e.preventDefault();
					e.stopPropagation();
					const confirmed = window.confirm( 'You have running timers. Closing cook mode will stop all timers. Do you want to continue?' );
					if ( confirmed ) {
						// User confirmed, close the modal (skip the check since we already confirmed)
						this.closeModal( true );
					}
					return;
				}
				// No running timers, allow ESC to work normally
				return;
			}

			// Don't interfere if user is typing in an input field or textarea
			const activeElement = document.activeElement;
			if ( activeElement && ( activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.isContentEditable ) ) {
				return;
			}

			// Handle arrow keys based on current screen
			if ( this.currentScreen === 'overview' ) {
				// On overview screen, right arrow starts cooking
				// Allow this if the "Start Cooking" button is focused, or body/modal/nothing is focused
				const startButton = this.currentModalElement.querySelector( '.wprm-cook-mode-start-button' );
				const isSafeToStart = activeElement === startButton || 
				                      activeElement === document.body || 
				                      activeElement === this.currentModalElement ||
				                      !activeElement;
				
				if ( e.key === 'ArrowRight' && isSafeToStart ) {
					e.preventDefault();
					this.startCooking();
				}
			} else if ( this.currentScreen === 'cooking' ) {
				// On cooking screen, check if we should allow arrow key navigation
				// Don't interfere if user is navigating through focusable elements (like buttons)
				if ( activeElement && activeElement !== document.body && activeElement !== this.currentModalElement ) {
					// Check if the focused element is an interactive element (button, link, etc.)
					const isInteractive = activeElement.tagName === 'BUTTON' || 
					                      activeElement.tagName === 'A' || 
					                      activeElement.hasAttribute( 'tabindex' ) ||
					                      activeElement.getAttribute( 'role' ) === 'button';
					
					if ( isInteractive ) {
						// Allow standard keyboard navigation - don't intercept arrow keys
						return;
					}
				}

				// On cooking screen, left/right arrows navigate steps
				if ( e.key === 'ArrowLeft' ) {
					e.preventDefault();
					this.goToPreviousStep();
				} else if ( e.key === 'ArrowRight' ) {
					e.preventDefault();
					this.goToNextStep();
				}
			} else if ( this.currentScreen === 'thank-you' ) {
				// On thank-you screen, left arrow goes back to last step
				if ( e.key === 'ArrowLeft' ) {
					e.preventDefault();
					this.goToPreviousStep();
				}
			}
		};

		document.addEventListener( 'keydown', keyboardHandler, true ); // Use capture phase to intercept before MicroModal
		this.eventHandlers.push( { element: document, event: 'keydown', handler: keyboardHandler, options: true } );
	},

	cleanupEventListeners() {
		// Remove all stored event listeners
		this.eventHandlers.forEach( ( { element, event, handler, options } ) => {
			if ( element && element.removeEventListener ) {
				// Remove with the same options that were used to add (capture phase, etc.)
				element.removeEventListener( event, handler, options );
			}
		});
		// Clear the handlers array
		this.eventHandlers = [];
	},

	setupBeforeUnloadWarning() {
		// Add beforeunload event to warn users when trying to leave the page
		// This helps prevent accidental loss of cooking progress
		const beforeUnloadHandler = ( e ) => {
			// Only show warning if modal is actually open
			if ( this.currentModalElement ) {
				// Modern browsers ignore custom messages, but we can still trigger the default dialog
				e.preventDefault();
				// For older browsers, set returnValue
				e.returnValue = '';
				return '';
			}
		};

		window.addEventListener( 'beforeunload', beforeUnloadHandler );
		this.eventHandlers.push( { element: window, event: 'beforeunload', handler: beforeUnloadHandler } );
	},

	removeBeforeUnloadWarning() {
		// The cleanupEventListeners will handle removing the beforeunload handler
		// This method is here for clarity and potential future use
	},

	handleSwipe() {
		const deltaX = this.touchEndX - this.touchStartX;
		const deltaY = this.touchEndY - this.touchStartY;

		// Only handle horizontal swipes (more horizontal than vertical)
		if ( Math.abs( deltaX ) > Math.abs( deltaY ) && Math.abs( deltaX ) > 50 ) {
			if ( this.currentScreen === 'overview' ) {
				// On overview screen, swipe left starts cooking
				if ( deltaX < 0 ) {
					this.startCooking();
				}
			} else {
				// On cooking screen, swipe left/right navigates steps
				if ( deltaX > 0 ) {
					// Swipe right - previous step
					this.goToPreviousStep();
				} else {
					// Swipe left - next step
					this.goToNextStep();
				}
			}
		}
	},

	updateServingsButtons( decreaseButton, increaseButton, servingsValue ) {
		if ( decreaseButton ) {
			decreaseButton.disabled = servingsValue <= 1;
		}
		// Increase button can always be enabled (no upper limit)
	},

	async updateServings( servings ) {
		if ( ! this.currentModalElement ) {
			return;
		}

		const cookModeContainer = this.currentModalElement.querySelector( '.wprm-cook-mode' );
		if ( ! cookModeContainer ) {
			return;
		}

		const recipeId = parseInt( cookModeContainer.getAttribute( 'data-recipe-id' ) );
		if ( ! recipeId ) {
			return;
		}

		// Track action for analytics.
		if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
			window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'adjust-servings', {
				type: 'cook-mode',
			});
		}

		// Use recipe manager to update servings
		try {
			const recipe = await window.WPRecipeMaker.manager.getRecipe( recipeId );
			if ( recipe && recipe.setServings ) {
				recipe.setServings( servings );
			}

			// Update ingredients display via recipe manager events
			// The recipe manager will trigger updates to adjustable quantities
		} catch ( error ) {
			console.error( 'Error updating servings:', error );
		}

		// Save progress when servings change
		this.saveProgress();
	},

	updateUnitSystem( system ) {
		if ( ! this.currentModalElement ) {
			return;
		}

		const cookModeContainer = this.currentModalElement.querySelector( '.wprm-cook-mode' );
		if ( ! cookModeContainer ) {
			return;
		}

		const recipeId = parseInt( cookModeContainer.getAttribute( 'data-recipe-id' ) );
		if ( ! recipeId ) {
			return;
		}

		// Track action for analytics.
		if ( window.WPRecipeMaker.hasOwnProperty( 'analytics' ) ) {
			window.WPRecipeMaker.analytics.registerActionOnce( recipeId, wprm_public.post_id, 'unit-conversion', {
				type: 'cook-mode',
			});
		}

		// Update active button
		const unitButtons = this.currentModalElement.querySelectorAll( '.wprm-cook-mode-unit-button' );
		unitButtons.forEach( button => {
			if ( parseInt( button.dataset.system ) === system ) {
				button.classList.add( 'active' );
			} else {
				button.classList.remove( 'active' );
			}
		});

		// Use recipe manager to update unit system
		window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
			if ( recipe && recipe.setUnitSystem ) {
				recipe.setUnitSystem( system );
			}
		}).catch( ( error ) => {
			console.error( 'Error updating unit system:', error );
		});

		// Save progress when unit system changes
		this.saveProgress();
	},

	startCooking() {
		if ( this.totalSteps === 0 ) {
			return;
		}

		// Request wake lock when user starts cooking (requires user gesture)
		if ( window.WPRecipeMaker.preventSleep && window.WPRecipeMaker.preventSleep.wakeLockApi ) {
			window.WPRecipeMaker.preventSleep.lock();
		}

		// Switch to cooking screen
		const overviewScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-overview' );
		const cookingScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-cooking' );

		if ( overviewScreen ) {
			overviewScreen.style.display = 'none';
		}

		if ( cookingScreen ) {
			cookingScreen.style.display = 'flex';
		}

		// Hide start button and show navigation/progress in footer
		const startButton = this.currentModalElement.querySelector( '.wprm-cook-mode-start-button' );
		const cookingFooter = this.currentModalElement.querySelector( '.wprm-cook-mode-cooking-footer' );

		if ( startButton ) {
			startButton.style.display = 'none';
		}

		if ( cookingFooter ) {
			cookingFooter.style.display = 'block';
		}

		this.currentScreen = 'cooking';
		this.currentStepIndex = 0;
		this.completedSteps.clear();
		this.updateStepDisplay();
		this.updateProgress();
		this.saveProgress();
	},

	goBackToOverview() {
		// Switch back to overview screen
		const overviewScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-overview' );
		const cookingScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-cooking' );

		if ( overviewScreen ) {
			overviewScreen.style.display = 'flex';
		}

		if ( cookingScreen ) {
			cookingScreen.style.display = 'none';
		}

		// Show start button and hide navigation/progress in footer
		const startButton = this.currentModalElement.querySelector( '.wprm-cook-mode-start-button' );
		const cookingFooter = this.currentModalElement.querySelector( '.wprm-cook-mode-cooking-footer' );

		if ( startButton ) {
			startButton.style.display = 'block';
		}

		if ( cookingFooter ) {
			cookingFooter.style.display = 'none';
		}

		this.currentScreen = 'overview';
		this.currentStepIndex = 0;
		this.completedSteps.clear();
		this.updateProgress();
		this.saveProgress();
	},

	goToPreviousStep() {
		if ( this.currentScreen === 'thank-you' ) {
			// Go back to the last step from thank-you screen
			this.currentScreen = 'cooking';
			this.currentStepIndex = this.totalSteps - 1;
			this.hideThankYouScreen();
			this.updateStepDisplay();
			this.saveProgress();
		} else if ( this.currentStepIndex > 0 ) {
			// Remove current step and any steps after it from completed
			for ( let i = this.currentStepIndex; i < this.totalSteps; i++ ) {
				this.completedSteps.delete( i );
			}
			this.currentStepIndex--;
			this.updateStepDisplay();
			this.saveProgress();
		} else if ( this.currentStepIndex === 0 ) {
			// Go back to overview screen
			this.goBackToOverview();
		}
	},

	goToNextStep() {
		if ( this.currentStepIndex < this.totalSteps - 1 ) {
			// Mark current step as completed
			this.completedSteps.add( this.currentStepIndex );
			this.currentStepIndex++;
			this.updateStepDisplay();
			this.saveProgress();
		} else if ( this.currentStepIndex === this.totalSteps - 1 ) {
			// Mark last step as completed
			this.completedSteps.add( this.currentStepIndex );
			// Show thank-you screen
			this.showThankYouScreen();
			this.saveProgress();
		}
	},

	updateStepDisplay() {
		if ( ! this.currentModalElement ) {
			return;
		}

		// Show only the current step, hide all others
		const allSteps = this.currentModalElement.querySelectorAll( '.wprm-cook-mode-instruction-step' );
		allSteps.forEach( ( step, index ) => {
			if ( index === this.currentStepIndex ) {
				step.style.display = 'block';
			} else {
				step.style.display = 'none';
			}
		});

		// Update navigation
		const prevButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-prev' );
		const nextButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-next' );
		const currentStepEl = this.currentModalElement.querySelector( '.wprm-cook-mode-step-current' );

		if ( currentStepEl ) {
			currentStepEl.textContent = this.currentStepIndex + 1;
		}

		this.updateProgress();
	},

	updateProgress() {
		if ( ! this.currentModalElement ) {
			return;
		}

		const progressStepCurrent = this.currentModalElement.querySelectorAll( '.wprm-cook-mode-progress-step-current' );
		const progressBarFills = this.currentModalElement.querySelectorAll( '.wprm-cook-mode-progress-bar-fill' );

		progressStepCurrent.forEach( progressStep => {
			progressStep.textContent = this.currentStepIndex + 1;
		});

		if ( progressBarFills.length > 0 && this.totalSteps > 0 ) {
			// Progress based on current step position: (currentStepIndex + 1) / totalSteps
			// This gives slightly filled at first step (1/totalSteps) and 100% at last step
			const progress = ( ( this.currentStepIndex + 1 ) / this.totalSteps ) * 100;
			progressBarFills.forEach( progressBarFill => {
				progressBarFill.style.width = progress + '%';
			});
		}
	},

	showThankYouScreen() {
		if ( ! this.currentModalElement ) {
			return;
		}

		// Hide cooking screen
		const cookingScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-cooking' );
		if ( cookingScreen ) {
			cookingScreen.style.display = 'none';
		}

		// Show thank-you screen
		const thankYouScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-thank-you' );
		if ( thankYouScreen ) {
			thankYouScreen.style.display = 'flex';
		}

		// Update footer: hide progress, hide Next button, show Close button
		const progress = this.currentModalElement.querySelector( '.wprm-cook-mode-progress' );
		const nextButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-next' );
		const closeButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-close' );
		const prevButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-prev' );

		if ( progress ) {
			progress.style.display = 'none';
		}

		if ( nextButton ) {
			nextButton.style.display = 'none';
		}

		if ( closeButton ) {
			closeButton.style.display = 'block';
		}

		this.currentScreen = 'thank-you';
	},

	hideThankYouScreen() {
		if ( ! this.currentModalElement ) {
			return;
		}

		// Hide thank-you screen
		const thankYouScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-thank-you' );
		if ( thankYouScreen ) {
			thankYouScreen.style.display = 'none';
		}

		// Show cooking screen
		const cookingScreen = this.currentModalElement.querySelector( '.wprm-cook-mode-screen-cooking' );
		if ( cookingScreen ) {
			cookingScreen.style.display = 'flex';
		}

		// Update footer: show progress, show Next button, hide Close button
		const progress = this.currentModalElement.querySelector( '.wprm-cook-mode-progress' );
		const nextButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-next' );
		const closeButton = this.currentModalElement.querySelector( '.wprm-cook-mode-nav-close' );

		if ( progress ) {
			progress.style.display = 'block';
		}

		if ( nextButton ) {
			nextButton.style.display = 'block';
		}

		if ( closeButton ) {
			closeButton.style.display = 'none';
		}
	},

	hasRunningTimers() {
		if ( ! window.WPRecipeMaker.timer || ! this.currentModalElement ) {
			return false;
		}

		// Check if there are any running timers for this modal
		let hasRunning = false;
		window.WPRecipeMaker.timer.cookModeTimers.forEach( ( timerData ) => {
			// Timer is running if it belongs to this modal and has an active interval
			if ( timerData.cookModeModal === this.currentModalElement && timerData.interval && ! timerData.finished ) {
				hasRunning = true;
			}
		});

		return hasRunning;
	},

	closeModal( skipCheck = false ) {
		if ( ! this.currentModalUid || ! window.WPRecipeMaker || ! window.WPRecipeMaker.modal ) {
			return;
		}

		// Check if there are running timers (unless we're skipping the check)
		if ( ! skipCheck && this.hasRunningTimers() ) {
			// Show confirmation dialog
			const confirmed = window.confirm( 'You have running timers. Closing cook mode will stop all timers. Do you want to continue?' );
			if ( ! confirmed ) {
				return;
			}
		}

		window.WPRecipeMaker.modal.close( this.currentModalUid );
	},

	saveProgress() {
		// Save progress to memory (not localStorage)
		if ( ! this.currentRecipeId ) {
			return;
		}

		this.savedProgress[ this.currentRecipeId ] = {
			screen: this.currentScreen,
			stepIndex: this.currentStepIndex,
			completedSteps: Array.from( this.completedSteps ),
		};
	},
};

ready(() => {
	window.WPRecipeMaker.cookMode.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}
