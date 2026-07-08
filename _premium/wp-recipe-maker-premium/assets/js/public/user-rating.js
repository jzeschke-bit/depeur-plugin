import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import animateScrollTo from 'animated-scroll-to';
import '../../css/public/user-rating.scss';

import { formatQuantity } from 'Shared/quantities';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.userRating = {
	init() {
		// Listen for rating changes.
		document.addEventListener( 'wprm-recipe-change', ( event ) => {
			if ( 'rating' === event.detail.type ) {
				const recipeId = event.detail.id;

				window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
					if ( recipe ) {
						window.WPRecipeMaker.userRating.updateRatingDisplay( recipeId, recipe.data.rating );
					}
				});
			}
		});
	},
	settings: {
		color: wprmp_public.settings.template_color_icon,
	},
	enter ( el ) {
		el.parentNode.classList.add( 'wprm-user-rating-voting' );

		let color = window.WPRecipeMaker.userRating.settings.color;

		if ( 'modern' === wprmp_public.settings.recipe_template_mode && el.dataset.color ) {
			color = el.dataset.color;
		}

		// Fill current and previous.
		let prev = el;
		while ( prev ) {
			prev.classList.add( 'wprm-rating-star-selecting-filled' );

			const polygons = prev.querySelectorAll( 'polygon, path' );
			for ( let polygon of polygons ) {
				polygon.style.fill = color;
			}

			prev = prev.previousSibling;
		}

		// Get next.
		let next = el.nextSibling;
		while ( next ) {
			next.classList.add( 'wprm-rating-star-selecting-empty' );

			const polygons = next.querySelectorAll( 'polygon, path' );
			for ( let polygon of polygons ) {
				polygon.style.fill = 'none';
			}
		
			next = next.nextSibling;
		}
	},
	leave ( el ) {
		el.parentNode.classList.remove( 'wprm-user-rating-voting' );

		let star = el.parentNode.firstChild;

		while ( star ) {
			star.classList.remove( 'wprm-rating-star-selecting-filled' );
			star.classList.remove( 'wprm-rating-star-selecting-empty' );

			const polygons = star.querySelectorAll( 'polygon, path' );
			for ( let polygon of polygons ) {
				polygon.style.fill = '';
			}
		
			star = star.nextSibling;
		}
	},
	click ( el, e ) {
		const key = e.which || e.keyCode || 0;

		// Rate recipe on click, ENTER or SPACE.
		if ( 'click' === e.type || ( 13 === key || 32 === key ) ) {
			e.preventDefault();

			const container = el.parentNode;

			let rating = parseInt( el.dataset.rating );
			let recipeId = parseInt( container.dataset.recipe );

			// Backwards compatibility.
			if ( ! recipeId ) {
				for ( var parent = el.parentNode; parent && parent != document; parent = parent.parentNode ) {
					if ( parent.matches( '.wprm-recipe-container' ) ) {
						recipeId = parseInt( parent.dataset.recipeId );
						break;
					}
				}
			}

			// Check if scroll to comments is preferred and possible.
			if ( 'scroll' === wprmp_public.settings.user_ratings_type && window.WPRecipeMaker.userRating.canJumpToComments() ) {
				// If the star is inside a popup modal (e.g. cook mode thank-you screen), close it first so the user sees the scroll.
				const modalElement = container.closest( '.wprm-popup-modal' );
				const openModalUid = window.WPRecipeMaker.modal && window.WPRecipeMaker.modal.currentOpenUid;

				if ( modalElement && openModalUid ) {
					const doScroll = () => {
						window.WPRecipeMaker.userRating.jumpToCommentRating( rating );
					};

					document.addEventListener( 'wprm-modal-close', function onClose() {
						document.removeEventListener( 'wprm-modal-close', onClose );
						doScroll();
					} );

					window.WPRecipeMaker.modal.close( openModalUid );
				} else {
					window.WPRecipeMaker.userRating.jumpToCommentRating( rating );
				}
			} else {
				// Open modal.
				const uid = container.dataset.modalUid;

				window.WPRecipeMaker.modal.open( uid, {
					recipe: recipeId,
					rating,
				} );
			}
		}
	},
	updateRatingDisplay( recipeId, rating ) {
		const containers = document.querySelectorAll( '.wprm-recipe-rating-recipe-' + recipeId );

		for ( let container of containers ) {
			let decimals = container.dataset.hasOwnProperty( 'decimals' ) ? parseInt( container.dataset.decimals ) : 2;
			decimals = 0 <= decimals ? decimals : 2;

			rating.roundedAverage = Number( rating.average.toFixed( decimals ) );
			rating.formattedAverage = formatQuantity( rating.average, decimals );

			// Update details.
			const detailsContainer = container.querySelector( '.wprm-recipe-rating-details' );

			if ( detailsContainer ) {
				detailsContainer.innerHTML = window.WPRecipeMaker.userRating.getRatingDetailsText( rating );
			} else {
				const averageContainer = container.querySelector('.wprm-recipe-rating-average');
				const countContainer = container.querySelector('.wprm-recipe-rating-count');
	
				if ( averageContainer ) { averageContainer.innerText = rating.formattedAverage; }
				if ( countContainer ) { countContainer.innerText = rating.count; }
			}

			// Update stars.
			const stars = rating.roundedAverage;

			for ( let i = 1; i <= 5; i++ ) {
				let star = container.querySelector( '.wprm-rating-star-' + i );

				if ( star ) {
					star.classList.remove( 'wprm-rating-star-full' );
					star.classList.remove( 'wprm-rating-star-empty' );
					star.classList.remove( 'wprm-rating-star-33' );
					star.classList.remove( 'wprm-rating-star-50' );
					star.classList.remove( 'wprm-rating-star-66' );
	
					if ( i <= stars ) {
						star.classList.add( 'wprm-rating-star-full' );
					} else {
						const difference = 0.0 + stars - i + 1;
	
						if ( 0 < difference && difference <= 0.33 ) {
							star.classList.add( 'wprm-rating-star-33' );
						} else if ( 0 < difference && difference <= 0.50 ) {
							star.classList.add( 'wprm-rating-star-50' );
						} else if ( 0 < difference && difference <= 0.66 ) {
							star.classList.add( 'wprm-rating-star-66' );
						} else if ( 0 < difference && difference <= 1 ) {
							star.classList.add( 'wprm-rating-star-full' );
						} else {
							star.classList.add( 'wprm-rating-star-empty' );
						}	
					}
				}
			}

			// Update container class for voteable stars.
			if ( container.classList.contains( 'wprm-user-rating' ) ) {
				if ( 0 < rating.user ) {
					container.classList.remove( 'wprm-user-rating-not-voted' );
					container.classList.add( 'wprm-user-rating-has-voted' );	
				} else {
					container.classList.add( 'wprm-user-rating-not-voted' );
					container.classList.remove( 'wprm-user-rating-has-voted' );
				}
			}
		}
	},
	// JS equivalent of WPRM_Rating::get_formatted_rating() in PHP.
	getRatingDetailsText( rating ) {
		let details = '';

		let text = '';
		if ( 0 === rating.count ) {
			text = wprmp_public.settings.rating_details_zero;
		} else if ( 1 === rating.count ) {
			text = wprmp_public.settings.rating_details_one;
		} else {
			text = wprmp_public.settings.rating_details_multiple;
		}

		if ( 0 < rating.user ) {
			const userVotedText = wprmp_public.settings.rating_details_user_voted;
			text = text.replace( '%not_voted%', '' );
			text = text.replace( '%voted%', userVotedText );
		} else {
			const userNotVotedText = wprmp_public.settings.rating_details_user_not_voted;
			text = text.replace( '%voted%', '' );
			text = text.replace( '%not_voted%', userNotVotedText );
		}

		// Replace placeholders.
		text = text.replace( '%average%', '<span class="wprm-recipe-rating-average">' + rating.formattedAverage + '</span>' );
		text = text.replace( '%votes%', '<span class="wprm-recipe-rating-count">' + rating.count + '</span>' );
		text = text.replace( '%user%', '<span class="wprm-recipe-rating-user">' + rating.user + '</span>' );

		details = text.trim();

		return details;
	},
	addRatingForRecipe( data, recipeId ) {
		let headers = {
			'Accept': 'application/json',
			'Content-Type': 'application/json',
		};

		// Only require nonce when logged in to prevent caching problems for regular visitors.
		if ( 0 < parseInt( wprmp_public.user ) ) {
			headers['X-WP-Nonce'] = wprm_public.api_nonce;
		}

		return fetch(`${wprmp_public.endpoints.user_rating}/${recipeId}`, {
			method: 'POST',
			headers,
			credentials: 'same-origin',
			body: JSON.stringify({
				data,
			}),
		}).then( (response) => {
			if ( response.ok ) {
				return response.json();
			} else {
				// API request failed, try AJAX.
				return window.WPRecipeMaker.userRating.addRatingForRecipeThroughAjax( data, recipeId );
			}
		}).then( ( result ) => {
			return result;
		});
	},
	addRatingForRecipeThroughAjax( data, recipeId ) {
		return fetch( wprm_public.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			body: 'action=wprm_user_rate_recipe&security=' + encodeURIComponent( wprm_public.nonce ) + '&recipe_id=' + encodeURIComponent( recipeId ) + '&data=' + encodeURIComponent( JSON.stringify( data ) ),
			headers: {
				'Accept': 'application/json, text/plain, */*',
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
			},
		}).then( (response) => {
			if ( response.ok ) {
				return response.json();
			}
			return false;
		}).then( ( result ) => {
			if ( result.success ) {
				return result.data;
			}
			return false;
		});
	},
	getCommentContainerElement() {
		let containerOptions = [
			'.comment-form-wprm-rating',
			'#llc_comments',
		];

		if ( wprmp_public.settings.user_ratings_force_comment_scroll_to ) {
			containerOptions.unshift( wprmp_public.settings.user_ratings_force_comment_scroll_to );
		}

		for ( let containerOption of containerOptions ) {
			const container = document.querySelector( containerOption );

			if ( container ) {
				return container;
			}
		}

		return false;
	},
	canJumpToComments() {
		if ( wprmp_public.settings.features_comment_ratings ) {
			return ! ! window.WPRecipeMaker.userRating.getCommentContainerElement();
		}

		return false;
	},
	jumpToCommentRating( rating ) {
		// Scroll to comment form.
		let scrollToElement = window.WPRecipeMaker.userRating.getCommentContainerElement();
		if ( scrollToElement ) {
			// What to do after jumping to the comment form.
			const afterJump = () => {
				// User rating not allowed, click on star in comment rating. Do after scroll so that content might have been lazy loaded.
				const commentRatingContainer = document.querySelector('.comment-form-wprm-rating');

				if ( commentRatingContainer ) {
					const inputs = commentRatingContainer.querySelectorAll( 'input' );

					for ( let input of inputs ) {
						if ( rating === parseInt( input.value ) ) {
							input.click();
							break;
						}
					}
				}

				// Focus on comment field.
				const commentInput = document.getElementById('comment');
				if ( commentInput ) {
					commentInput.focus();
				}
			};

			// Check if we should be smooth scrolling or not.
			const useSmoothScroll = !! wprmp_public.settings.user_ratings_force_comment_scroll_to_smooth;

			if ( useSmoothScroll ) {
				animateScrollTo( scrollToElement, {
					verticalOffset: -100,
					speed: 250,
				} ).then( afterJump );
			} else {
				scrollToElement.scrollIntoView( {
					behavior: 'instant',
				} );

				afterJump();
			}
		}
	},
};

ready(() => {
	window.WPRecipeMaker.userRating.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}