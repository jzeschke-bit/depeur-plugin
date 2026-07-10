/**
 * Depeur Food – „Was koche ich heute" Rezept-Filter (Frontend).
 *
 * Reines Vanilla JS (kein jQuery, kein Build-Step). Klick auf Filter-Bubbles → REST-Abfrage
 * (Filter_Controller) → Ergebnis-Raster + Titel aktualisieren + URL (?tags=) spiegeln;
 * „Mehr laden" hängt die nächste Seite an. Konfiguration via window.dfRecipeFilter.
 *
 * @package Depeur\Food\Modules\CategoryPages
 */
( function () {
	'use strict';

	var cfg = window.dfRecipeFilter || {};

	/**
	 * Verdrahtet eine Filter-Instanz.
	 *
	 * @param {HTMLElement} root Der .df-recipe-filter-Container.
	 */
	function setup( root ) {
		var results = root.querySelector( '.df-recipe-filter__results' );
		var titleEl = root.querySelector( '.df-recipe-filter__title' );
		var moreBtn = root.querySelector( '.df-recipe-filter__more-btn' );
		var match = 'or' === root.getAttribute( 'data-match' ) ? 'or' : 'and';
		var paged = 1;

		function selectedSlugs() {
			return Array.prototype.map.call(
				root.querySelectorAll( '.df-recipe-filter__bubble.is-active' ),
				function ( bubble ) {
					return bubble.getAttribute( 'data-slug' );
				}
			);
		}

		function requestUrl( page ) {
			var slugs = selectedSlugs().join( ',' );
			return cfg.restUrl + '?tags=' + encodeURIComponent( slugs ) +
				'&paged=' + page + '&match=' + match;
		}

		function syncUrl() {
			try {
				var slugs = selectedSlugs();
				var url = new window.URL( window.location.href );
				if ( slugs.length ) {
					url.searchParams.set( 'tags', slugs.join( ',' ) );
				} else {
					url.searchParams.delete( 'tags' );
				}
				window.history.replaceState( {}, '', url.toString() );
			} catch ( e ) {
				// URL-API nicht verfügbar – ignorieren.
			}
		}

		function hydrate( container ) {
			// Herz-Zustand der neu eingefügten Karten setzen (favorites-Modul stellt den Hook bereit).
			if ( typeof window.dfFavoritesHydrate === 'function' ) {
				window.dfFavoritesHydrate( container );
			}
		}

		function appendGrid( html ) {
			var tmp = document.createElement( 'div' );
			tmp.innerHTML = html;
			var newGrid = tmp.querySelector( '.post-archive' );
			var grid = results.querySelector( '.post-archive' );
			if ( grid && newGrid ) {
				while ( newGrid.firstChild ) {
					grid.appendChild( newGrid.firstChild );
				}
			} else {
				results.insertAdjacentHTML( 'beforeend', html );
			}
		}

		function load( page, append ) {
			if ( moreBtn ) {
				moreBtn.disabled = true;
			}
			fetch( requestUrl( page ), { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( data ) {
					if ( ! data ) {
						return;
					}
					if ( append ) {
						if ( data.content ) {
							appendGrid( data.content );
						}
					} else {
						// Server liefert escaptes Loop-HTML aus dem eigenen Endpoint.
						results.innerHTML = data.content || '';
						if ( titleEl && data.title ) {
							titleEl.textContent = data.title;
						}
					}
					paged = page;
					if ( moreBtn ) {
						moreBtn.hidden = ! data.hasMore;
						moreBtn.disabled = false;
					}
					hydrate( results );
					syncUrl();
				} )
				.catch( function () {
					if ( moreBtn ) {
						moreBtn.disabled = false;
					}
				} );
		}

		root.addEventListener( 'click', function ( event ) {
			var bubble = event.target.closest ? event.target.closest( '.df-recipe-filter__bubble' ) : null;
			if ( bubble && root.contains( bubble ) ) {
				event.preventDefault();
				var active = bubble.classList.toggle( 'is-active' );
				bubble.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
				load( 1, false );
				return;
			}
			if ( moreBtn && event.target === moreBtn ) {
				event.preventDefault();
				load( paged + 1, true );
			}
		} );
	}

	function init() {
		document.querySelectorAll( '.df-recipe-filter' ).forEach( setup );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
