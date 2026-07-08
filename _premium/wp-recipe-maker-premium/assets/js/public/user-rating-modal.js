window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.userRatingModal = {
	init() {
		// Check if rate parameter is set in the URL.
		if ( wprmp_public.settings.user_ratings_open_url_parameter ) {
			const urlParams = new URLSearchParams(window.location.search);
			if ( urlParams.has( wprmp_public.settings.user_ratings_open_url_parameter ) ) {
				const elements = document.querySelectorAll( '.wprm-user-rating, .wprm-recipe-user-ratings-modal' );

				for ( let element of elements ) {
					if ( element.dataset.hasOwnProperty( 'modalUid' ) && element.dataset.hasOwnProperty( 'recipe' ) ) {
						const uid = element.dataset.modalUid;
						let recipeId = element.dataset.recipe;
						recipeId = parseInt( recipeId );

						if ( recipeId ) {
							// Make sure modal has been inited.
							setTimeout(() => {
								window.WPRecipeMaker.modal.open( uid, {
									recipe: recipeId,
									rating: 0,
								} );
							});
						}

						break;
					}
				}
			}
		}

		// Check for opening user rating modals.
		document.addEventListener( 'wprm-modal-open', ( event ) => {
			if ( 'user-rating' === event.detail.type ) {
				window.WPRecipeMaker.userRatingModal.opened( event.detail.uid, event.detail.modal, event.detail.data );
			}
		});

		// Check for closing user rating modals.
		document.addEventListener( 'wprm-modal-close', ( event ) => {
			if ( 'user-rating' === event.detail.type ) {
				window.WPRecipeMaker.userRatingModal.closed( event.detail.uid, event.detail.modal, event.detail.data );
			}
		});

		// Check for stars change.
		document.addEventListener( 'wprm-comment-rating-change', ( event ) => {
			if ( event.detail.hasOwnProperty( 'container' ) && event.detail.container.classList.contains( 'wprm-user-ratings-modal-stars' ) ) {
				window.WPRecipeMaker.userRatingModal.ratingChange( event.detail.rating );
			}
		});

		document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				// Check for clicking on Call to Action or Open User Ratings Modal button.
				if ( target.matches( '.wprm-cta-rating-modal' ) || target.matches( '.wprm-recipe-user-ratings-modal' ) ) {
					e.preventDefault();
					const uid = target.dataset.modalUid;
					let recipeId = target.dataset.recipe;
					recipeId = parseInt( recipeId );

					if ( recipeId ) {
						window.WPRecipeMaker.modal.open( uid, {
							recipe: recipeId,
							rating: 0,
						} );
					}

					break;
				}

				// Check for clicking on Comment Suggestion.
				if ( target.matches( '.wprm-user-rating-modal-comment-suggestion' ) ) {
					e.preventDefault();
					const comment = target.innerText;
					const commentInput = document.querySelector( '.wprm-user-rating-modal-comment' );

					if ( comment && commentInput ) {
						commentInput.value = comment;
						window.WPRecipeMaker.userRatingModal.checkFields(); // Trigger change.
					}

					break;
				}

				// Check for clicking on ratings summary.
				if ( target.matches( '.wprm-user-rating-summary-details-no-comments' ) ) {
					e.preventDefault();
					window.WPRecipeMaker.userRatingModal.ratingsWithoutCommentsPopup( target ); // Trigger change.
					break;
				}
			}
		}, false );
	},
	modalUid: false,
	currentRecipe: false,
	currentCommentForRecipe: {},
	opened( uid, modal, data ) {
		// Store current modal UID.
		window.WPRecipeMaker.userRatingModal.modalUid = uid;

		// Set title of modal.
		window.WPRecipeMaker.userRatingModal.setTitle( wprmp_public.settings.user_ratings_modal_title );

		// Start out with loader.
		window.WPRecipeMaker.userRatingModal.displayMessage( '<div class="wprm-loader"></div>' );

		// Set default rating passed along, but reset to 0 first.
		const starsContainer = modal.querySelector( '.wprm-user-ratings-modal-stars-container' );
		const inputs = starsContainer.querySelectorAll( 'input' );

		inputs[0].click();

		if ( data.hasOwnProperty( 'rating' ) ) {
			const rating = parseInt( data.rating );

			for ( let input of inputs ) {
				if ( rating === parseInt( input.value ) ) {
					input.click();
					break;
				}
			}
		}

		// Set recipe passed along.
		const recipeIdInput = modal.querySelector( 'input[name="wprm-user-rating-recipe-id"]' );
		recipeIdInput.value = data.hasOwnProperty( 'recipe' ) ? data.recipe : 0;

		// Load recipe first, which might be through API.
		window.WPRecipeMaker.manager.getRecipe( recipeIdInput.value ).then( ( recipe ) => {
			window.WPRecipeMaker.userRatingModal.currentRecipe = recipe;

			// Show form.
			window.WPRecipeMaker.userRatingModal.displayMessage( false );

			// Clear other fields.
			const comment = modal.querySelector( '.wprm-user-rating-modal-comment' );
			comment.value = '';

			// Set current comment if available.
			if ( recipe && window.WPRecipeMaker.userRatingModal.currentCommentForRecipe.hasOwnProperty( recipe.id ) ) {
				comment.value = window.WPRecipeMaker.userRatingModal.currentCommentForRecipe[ recipe.id ];
			}

			window.WPRecipeMaker.userRatingModal.checkFields(); // Trigger change.

			// Clear errors and waiting.
			window.WPRecipeMaker.userRatingModal.displayError( false );
			window.WPRecipeMaker.userRatingModal.displayWaiting( false );
			
			// Set the name of the recipe being rated.
			const recipeNameField = modal.querySelector( '.wprm-user-ratings-modal-recipe-name' );
			recipeNameField.innerHTML = recipe ? recipe.data.name : '';
		});
	},
	ratingChange( rating ) {
		window.WPRecipeMaker.userRatingModal.checkFields();
	},
	checkFields() {
		const modal = document.querySelector( '.wprm-popup-modal-user-rating' );
		const isLoggedIn = 0 < parseInt( wprmp_public.user );
		
		// Apply correct class.
		if ( isLoggedIn ) {
			modal.classList.add( 'wprm-user-rating-modal-logged-in' );
		}

		// Make inputs required if needed.
		const commentInput = modal.querySelector( '.wprm-user-rating-modal-comment' );
		const nameInput = modal.querySelector( 'input[name="wprm-user-rating-name"]' );
		const emailInput = modal.querySelector( 'input[name="wprm-user-rating-email"]' );

		commentInput.required = wprmp_public.settings.user_ratings_require_comment;
		nameInput.required = ! isLoggedIn && wprmp_public.settings.user_ratings_require_name;
		emailInput.required = ! isLoggedIn && wprmp_public.settings.user_ratings_require_email;

		// Check if comment suggestions should be displayed.
		const formData = new FormData( commentInput.form );
		const formProps = Object.fromEntries( formData );
		const rating = parseInt( formProps['wprm-user-rating-stars'] );

		const suggestionsContainer = modal.querySelector( '.wprm-user-rating-modal-comment-suggestions-container' );
		
		if ( suggestionsContainer ) {
			suggestionsContainer.style.display = window.WPRecipeMaker.userRatingModal.shouldSuggestionsShow( rating ) ? 'block' : 'none';
		}

		// Check if different submit button should be shown.
		if ( ! wprmp_public.settings.user_ratings_require_comment ) {
			const submitComment = modal.querySelector( '.wprm-user-rating-modal-submit-comment' );
			const submitRating = modal.querySelector( '.wprm-user-rating-modal-submit-no-comment' );

			if ( commentInput.value ) {
				submitComment.style.display = 'block';
				submitRating.style.display = 'none';
			} else {
				submitComment.style.display = 'none';
				submitRating.style.display = 'block';
			}
		}
	},
	shouldSuggestionsShow( rating ) {
		if ( 'never' !== wprmp_public.settings.user_ratings_comment_suggestions_enabled ) {
			const checkStars = {
				'always': 1,
				'2_star': 2,
				'3_star': 3,
				'4_star': 4,
				'5_star': 5,
			}

			if ( checkStars.hasOwnProperty( wprmp_public.settings.user_ratings_comment_suggestions_enabled ) ) {
				if ( rating >= checkStars[ wprmp_public.settings.user_ratings_comment_suggestions_enabled ] ) {
					return true;
				}
			}
		}

		return false;
	},
	submit( form ) {
		const formData = new FormData( form );
		const formProps = Object.fromEntries( formData );

		const recipeId = parseInt( formProps['wprm-user-rating-recipe-id'] );
		const rating = parseInt( formProps['wprm-user-rating-stars'] );
		const comment = formProps['wprm-user-rating-comment'].trim();
		const name = formProps['wprm-user-rating-name'].trim();
		const email = formProps['wprm-user-rating-email'].trim();

		// Check if rating is correct.
		if ( rating <= 0 || rating > 5 ) {
			window.WPRecipeMaker.userRatingModal.displayError( 'rating' );
			return false;
		}

		// Check if personal details are required, only when not logged in.
		if ( 0 === parseInt( wprmp_public.user ) ) {
			if ( ! name && wprmp_public.settings.user_ratings_require_name ) {
				window.WPRecipeMaker.userRatingModal.displayError( 'name' );
				return false;
			} else if ( ! email && wprmp_public.settings.user_ratings_require_email ) {
				window.WPRecipeMaker.userRatingModal.displayError( 'email' );
				return false;
			}
		}

		// All good, hide any errors and submit.
		window.WPRecipeMaker.userRatingModal.displayError( false );
		window.WPRecipeMaker.userRatingModal.displayWaiting( true );

		// Submit rating.
		const data = {
			post_id: wprm_public.post_id,
			rating,
			comment,
			name,
			email,
		};

		window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
			const showMessage = ( success ) => {
				let message = '';
		
				if ( false === success ) {
					message = wprmp_public.settings.user_ratings_problem_message;
				} else {
					message = wprmp_public.settings.user_ratings_thank_you_message_with_comment;
				}
	
				// Show message or close modal.
				if ( message ) {
					window.WPRecipeMaker.userRatingModal.setTitle( wprmp_public.settings.user_ratings_thank_you_title );
					window.WPRecipeMaker.userRatingModal.displayMessage( message );
				} else {
					window.WPRecipeMaker.modal.close( window.WPRecipeMaker.userRatingModal.modalUid );
				}
			};

			if ( recipe ) {
				recipe.addRating( data ).then( ( success ) => {
					window.WPRecipeMaker.userRatingModal.displayWaiting( false );
					showMessage( success );
				} );
			} else {
				showMessage( false );
			}
		});
	},
	displayError( error ) {
		const container = document.querySelector( '#wprm-user-rating-modal-errors' );

		if ( container ) {
			container.querySelectorAll( 'div' ).forEach( ( el ) => {
				if ( error && el.id === 'wprm-user-rating-modal-error-' + error ) {
					el.style.display = 'block';
				} else {
					el.style.display = '';
				}
			});
		}
	},
	displayWaiting( waiting ) {
		const container = document.querySelector( '#wprm-user-rating-modal-waiting' );
		if ( container ) {
			container.style.display = waiting ? 'inline-block' : '';
		}

		const buttons = document.querySelectorAll( '.wprm-user-rating-modal-submit-rating, .wprm-user-rating-modal-submit-comment' );
		for ( let button of buttons ) {
			button.disabled = waiting;
		}
	},
	displayMessage( message ) {
		const form = document.querySelector( '#wprm-user-ratings-modal-stars-form' );
		const messageContainer = document.querySelector( '#wprm-user-ratings-modal-message' );

		if ( false === message ) {
			form.style.display = 'block';
			messageContainer.style.display = 'none';
		} else {
			form.style.display = 'none';
			messageContainer.innerHTML = message;
			messageContainer.style.display = 'block';
		}
	},
	setTitle( title ) {
		const titleElement = document.querySelector( '#wprm-popup-modal-user-rating-title' );

		if ( titleElement && title ) {
			titleElement.innerHTML = title;
		}
	},
	closed( uid, modal, data ) {
		const recipe = window.WPRecipeMaker.userRatingModal.currentRecipe;

		// If recipe is set, remember current comment for that recipe.
		if ( recipe ) { 
			// Check if form is still visible.
			let currentComment = '';
			const form = document.querySelector( '#wprm-user-ratings-modal-stars-form' );

			if ( form.style.display !== 'none' ) {
				// Remember current comment.
				const comment = modal.querySelector( '.wprm-user-rating-modal-comment' );
				currentComment = comment.value;
			}

			window.WPRecipeMaker.userRatingModal.currentCommentForRecipe[ recipe.id ] = currentComment;
		}

		// Reset current modal values.
		window.WPRecipeMaker.userRatingModal.currentRecipe = false;
		window.WPRecipeMaker.userRatingModal.modalUid = false;
	},
	ratingsWithoutCommentsPopup( target ) {
		const uid = target.dataset.modalUid;

		if ( uid ) {
			const modal = document.getElementById( 'wprm-popup-modal-' + uid );
			window.WPRecipeMaker.modal.open( uid );

			// Reset modal.
			const loader = modal.querySelector( '.wprm-loader' );
			const error = modal.querySelector( '.wprm-popup-modal-user-rating-summary-error' );
			const results = modal.querySelector( '.wprm-popup-modal-user-rating-summary-ratings' );

			loader.style.display = 'block';
			error.style.display = 'none';
			results.innerHTML = '';

			// Set up API call.
			const recipeId = target.dataset.recipeId;
			const postId = target.dataset.postId;

			let headers = {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			};

			// Only require nonce when logged in to prevent caching problems for regular visitors.
			if ( 0 < parseInt( wprmp_public.user ) ) {
				headers['X-WP-Nonce'] = wprm_public.api_nonce;
			}

			return fetch(`${wprmp_public.endpoints.user_rating}/summary-popup`, {
				method: 'POST',
				headers,
				credentials: 'same-origin',
				body: JSON.stringify({
					recipeId,
					postId,
				}),
			}).then( (response) => {
				if ( response.ok ) {
					return response.json();
				}

				return false;
			}).then( ( result ) => {
				loader.style.display = 'none';

				if ( ! result || ! result.html ) {
					error.style.display = 'block';
				} else {
					results.innerHTML = result.html;
				}
			});
		}
	},
};

ready(() => {
	window.WPRecipeMaker.userRatingModal.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}