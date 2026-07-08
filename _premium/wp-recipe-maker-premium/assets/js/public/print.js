window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.premiumPrint = {
	init: () => {
		document.addEventListener( 'click', function( e ) {
			for ( var target = e.target; target && target !== this; target = target.parentNode ) {
				if ( target.matches( '.wprm-recipe-download-pdf' ) ) {
					window.WPRecipeMaker.premiumPrint.onClick( target, e );
					break;
				}
			}
		}, false );
	},
	onClick: ( el, e ) => {
		let recipeId = el.dataset.recipeId;

		// Backwards compatibility.
		if ( ! recipeId ) {
			const container = el.closest( '.wprm-recipe-container' );

			if ( container ) {
				recipeId = container.dataset.recipeId;
			}
		}

		if ( recipeId ) {
			e.preventDefault();
			recipeId = parseInt( recipeId );

			// Optional template to use for the PDF.
			const template = el.dataset.hasOwnProperty( 'template' ) ? el.dataset.template : '';

			// Analytics.
			let location = 'other';
			const parent = el.closest( '.wprm-recipe' );
			if ( parent ) {
				if ( parent.classList.contains( 'wprm-recipe-snippet' ) ) {
					location = 'snippet';
				} else if ( parent.classList.contains( 'wprm-recipe-roundup-item' ) ) {
					location = 'roundup';
				} else {
					location = 'recipe';
				}
			}

			window.WPRecipeMaker.analytics.registerAction( recipeId, wprm_public.post_id, 'print', {
				location,
			} );

			window.WPRecipeMaker.premiumPrint.recipeAsIs( recipeId, template );
		}
	},
	recipeAsIs: ( id, template = '' ) => {
		let servings = false;
		let system = 1;
		let advancedServings = false;

		window.WPRecipeMaker.manager.getRecipe( id ).then( ( recipe ) => {
			if ( recipe ) {
				if ( recipe.data.hasOwnProperty( 'currentSystem' ) ) {
					system = recipe.data.currentSystem;
				}

				if ( recipe.data.currentServingsParsed !== recipe.data.originalServingsParsed ) {
					servings = recipe.data.currentServingsParsed;
				}

				advancedServings = recipe.data.currentAdvancedServings;
			}

			const normalizedArgs = window.WPRecipeMaker.premiumPrint.normalizePrintArgs({
				servings,
				system,
				advancedServings,
			});

			window.WPRecipeMaker.premiumPrint.recipePdf(
				id,
				normalizedArgs.servings,
				normalizedArgs.system,
				normalizedArgs.advancedServings,
				template
			);
		} );
	},
	normalizePrintArgs: ( args = {} ) => {
		let servings = false;
		if ( false !== args.servings && null !== args.servings && '' !== args.servings ) {
			const parsedServings = parseFloat( args.servings );
			servings = isNaN( parsedServings ) || parsedServings <= 0 ? false : parsedServings;
		}

		let system = parseInt( args.system );
		system = isNaN( system ) || system < 0 ? 1 : system;

		return {
			servings,
			system,
			advancedServings: args.hasOwnProperty( 'advancedServings' ) ? args.advancedServings : false,
		};
	},
	recipePdf: async ( id, servings = false, system = 1, advancedServings = false, template = '' ) => {
		if ( ! wprm_public.settings.pdf_download_enabled ) {
			return;
		}

		const normalizedArgs = window.WPRecipeMaker.premiumPrint.normalizePrintArgs({
			servings,
			system,
			advancedServings,
		});

		const target = wprm_public.settings.print_new_tab ? '_blank' : '_self';
		const printArgs = {
			id,
			system: normalizedArgs.system,
			servings: normalizedArgs.servings,
			advancedServings: normalizedArgs.advancedServings,
			output: 'pdf',
		};
		localStorage.setItem( 'wprmPrintArgs', JSON.stringify( printArgs ) );

		const pdfWindow = window.WPRecipeMaker.premiumPrint.openPdfDownloadPlaceholder( target );

		const url = await window.WPRecipeMaker.premiumPrint.getPdfDownloadUrl( id, template );
		if ( ! url ) {
			window.WPRecipeMaker.premiumPrint.showPdfDownloadError( pdfWindow );
			return;
		}

		if ( '_blank' === target && pdfWindow && ! pdfWindow.closed ) {
			pdfWindow.location = url;
			pdfWindow.focus();
		} else if ( '_blank' === target ) {
			const openedWindow = window.open( url, '_blank' );

			if ( ! openedWindow ) {
				window.location = url;
			}
		} else {
			window.open( url, '_self' );
		}
	},
	openPdfDownloadPlaceholder: ( target = '_blank' ) => {
		if ( '_blank' !== target ) {
			return false;
		}

		const pdfWindow = window.open( '', '_blank' );
		if ( ! pdfWindow || pdfWindow.closed ) {
			return false;
		}

		try {
			pdfWindow.document.title = 'Preparing PDF';
			pdfWindow.document.body.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;padding:20px;text-align:center;">Preparing PDF Download...</div>';
		} catch ( e ) {}

		return pdfWindow;
	},
	showPdfDownloadError: ( pdfWindow = false ) => {
		if ( pdfWindow && ! pdfWindow.closed ) {
			try {
				pdfWindow.document.title = 'PDF Download';
				pdfWindow.document.body.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;padding:20px;text-align:center;">Could not generate the PDF download URL. Please try again.</div>';
			} catch ( e ) {}
		}
	},
	getPdfDownloadUrl: async ( recipeId, template = '' ) => {
		let headers = {
			'Accept': 'application/json',
			'Content-Type': 'application/json',
		};

		// Only require nonce when logged in to prevent caching problems for regular visitors.
		if ( 0 < parseInt( wprm_public.user ) ) {
			headers['X-WP-Nonce'] = wprm_public.api_nonce;
		}

		const body = {
			recipeId,
			nonce: wprm_public.nonce,
		};
		if ( template ) {
			body.template = template;
		}

		try {
			const response = await fetch( `${wprm_public.endpoints.utilities}/pdf-download-url`, {
				method: 'POST',
				headers,
				credentials: 'same-origin',
				body: JSON.stringify( body ),
			} );

			if ( ! response.ok ) {
				return false;
			}

			const result = await response.json();
			return result && result.url ? result.url : false;
		} catch ( e ) {
			return false;
		}
	},
};

ready( () => {
	if ( window.WPRecipeMaker && window.WPRecipeMaker.print ) {
		window.WPRecipeMaker.premiumPrint.init();
	}
} );

function ready( fn ) {
	if ( document.readyState !== 'loading' ) {
		fn();
	} else {
		document.addEventListener( 'DOMContentLoaded', fn );
	}
}
