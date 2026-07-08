/**
 * Depeur Food – Favoriten (Frontend).
 *
 * Reines Vanilla JS (kein jQuery, kein Build-Step, Asset-Convention der CLAUDE.md).
 * Favoriten sind clientseitig (localStorage `df_favorites`); der Server hält nur den
 * globalen Like-Zähler pro Post. Enthält die einmalige Migration des Legacy-Zustands
 * (Cookie `my_favorite_posts` CSV + alter localStorage-Key) nach `df_favorites`.
 *
 * Konfiguration/Nonce/REST-URLs kommen via wp_localize_script als window.dfFavorites.
 *
 * @package Depeur\Food\Modules\Favorites
 */
( function () {
	'use strict';

	var cfg = window.dfFavorites || {};
	var STORAGE_KEY = cfg.storageKey || 'df_favorites';
	var HEART_FULL = '♥'; // ♥
	var HEART_EMPTY = '♡'; // ♡

	/**
	 * Liest die Favoriten-IDs (als String-Array) aus localStorage.
	 *
	 * @return {Array<string>} Liste der Post-IDs.
	 */
	function readFavorites() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			var arr = raw ? JSON.parse( raw ) : [];
			return Array.isArray( arr ) ? arr.map( String ) : [];
		} catch ( e ) {
			return [];
		}
	}

	/**
	 * Schreibt die Favoriten-IDs zurück nach localStorage.
	 *
	 * @param {Array} ids Liste der Post-IDs.
	 */
	function writeFavorites( ids ) {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( ids.map( String ) ) );
		} catch ( e ) {
			// localStorage nicht verfügbar (Privatmodus o. Ä.) – still ignorieren.
		}
	}

	function hasFavorite( id ) {
		return readFavorites().indexOf( String( id ) ) !== -1;
	}

	function addFavorite( id ) {
		var favorites = readFavorites();
		if ( favorites.indexOf( String( id ) ) === -1 ) {
			favorites.push( String( id ) );
			writeFavorites( favorites );
		}
	}

	function removeFavorite( id ) {
		writeFavorites( readFavorites().filter( function ( item ) {
			return item !== String( id );
		} ) );
	}

	/**
	 * Einmalige Migration: Legacy-Cookie (CSV) + alter localStorage-Key → df_favorites.
	 *
	 * Nutzer des Alt-Plugins dürfen ihre Favoriten nicht verlieren. Nach der Übernahme
	 * werden die Legacy-Quellen entfernt, damit die Migration nur einmal greift.
	 */
	function migrateLegacy() {
		var merged = readFavorites();
		var changed = false;

		// (1) Alter localStorage-Key (das Legacy-JS speicherte unter my_favorite_posts).
		try {
			var legacyStorageKey = cfg.legacyStorageKey || 'my_favorite_posts';
			var legacyLs = window.localStorage.getItem( legacyStorageKey );
			if ( legacyLs ) {
				var parsed = JSON.parse( legacyLs );
				if ( Array.isArray( parsed ) ) {
					parsed.forEach( function ( id ) {
						id = String( id ).trim();
						if ( id && merged.indexOf( id ) === -1 ) {
							merged.push( id );
							changed = true;
						}
					} );
				}
				window.localStorage.removeItem( legacyStorageKey );
			}
		} catch ( e ) {
			// Defekter Legacy-Wert – überspringen.
		}

		// (2) Legacy-Cookie (CSV), z. B. "12,45,88".
		var cookieName = cfg.legacyCookie || 'my_favorite_posts';
		var escaped = cookieName.replace( /([.$?*|{}()\[\]\\\/+^])/g, '\\$1' );
		var match = document.cookie.match( new RegExp( '(?:^|; )' + escaped + '=([^;]*)' ) );
		if ( match && match[ 1 ] ) {
			decodeURIComponent( match[ 1 ] ).split( ',' ).forEach( function ( id ) {
				id = String( id ).trim();
				if ( id && merged.indexOf( id ) === -1 ) {
					merged.push( id );
					changed = true;
				}
			} );
			// Cookie löschen (auf Ablauf in der Vergangenheit setzen).
			document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
		}

		if ( changed ) {
			writeFavorites( merged );
		}
	}

	/**
	 * Setzt den Anzeige-Zustand eines Buttons (Herz gefüllt/leer + a11y-Attribute).
	 *
	 * @param {HTMLElement} button    Der Favoriten-Button.
	 * @param {boolean}     favorited Ob der zugehörige Post gemerkt ist.
	 */
	function setButtonState( button, favorited ) {
		var icon = button.querySelector( '.df-favorite-icon' );
		button.setAttribute( 'aria-pressed', favorited ? 'true' : 'false' );
		if ( favorited ) {
			button.classList.add( 'is-favorited' );
			if ( icon ) {
				icon.textContent = HEART_FULL;
			}
		} else {
			button.classList.remove( 'is-favorited' );
			if ( icon ) {
				icon.textContent = HEART_EMPTY;
			}
		}
	}

	/**
	 * Aktualisiert die sichtbare Zähler-Zahl eines Buttons (falls vorhanden).
	 *
	 * @param {HTMLElement} button Der Favoriten-Button.
	 * @param {number}      likes  Neuer Zählerstand vom Server.
	 */
	function updateCount( button, likes ) {
		var el = button.querySelector( '.df-favorite-count' );
		if ( el && typeof likes !== 'undefined' && likes !== null ) {
			el.textContent = String( likes );
		}
	}

	/**
	 * Setzt beim Laden den korrekten Zustand aller sichtbaren Buttons.
	 *
	 * @param {Document|HTMLElement} root Wurzelknoten für die Suche.
	 */
	function hydrateButtons( root ) {
		( root || document ).querySelectorAll( '.df-favorite-button' ).forEach( function ( button ) {
			setButtonState( button, hasFavorite( button.getAttribute( 'data-post-id' ) ) );
		} );
	}

	/**
	 * Toggelt einen Favoriten: optimistisch (Storage + UI), dann REST-Call mit Revert bei Fehler.
	 *
	 * @param {HTMLElement} button Der geklickte Button.
	 */
	function toggle( button ) {
		var id = button.getAttribute( 'data-post-id' );
		if ( ! id ) {
			return;
		}

		var wasFavorited = hasFavorite( id );
		var direction = wasFavorited ? 'remove' : 'add';

		// Optimistisch: Storage + UI sofort umstellen.
		if ( wasFavorited ) {
			removeFavorite( id );
		} else {
			addFavorite( id );
		}
		setButtonState( button, ! wasFavorited );
		button.disabled = true;

		function revert() {
			if ( wasFavorited ) {
				addFavorite( id );
			} else {
				removeFavorite( id );
			}
			setButtonState( button, wasFavorited );
		}

		fetch( cfg.toggleUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce || ''
			},
			body: JSON.stringify( { id: parseInt( id, 10 ), direction: direction } )
		} )
			.then( function ( response ) {
				return response.json().then( function ( data ) {
					return { ok: response.ok, data: data };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok && result.data && result.data.success ) {
					updateCount( button, result.data.likes );
				} else {
					revert();
				}
			} )
			.catch( function () {
				revert();
			} )
			.then( function () {
				button.disabled = false;
			} );
	}

	/**
	 * Rendert ein Favoriten-Archiv clientseitig: liest die IDs, holt die Post-Daten
	 * per REST (post-type-agnostisch) und baut eine einfache Kartenliste.
	 *
	 * @param {HTMLElement} container Der Archiv-Container.
	 */
	function renderArchive( container ) {
		var statusEl = container.querySelector( '.df-favorites-archive__status' );
		function setStatus( msg ) {
			if ( statusEl ) {
				statusEl.textContent = msg || '';
			}
		}

		var favorites = readFavorites();
		if ( ! favorites.length ) {
			setStatus( container.getAttribute( 'data-empty' ) );
			return;
		}

		setStatus( container.getAttribute( 'data-loading' ) );

		fetch( cfg.listUrl + '?ids=' + encodeURIComponent( favorites.join( ',' ) ), {
			credentials: 'same-origin'
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( items ) {
				if ( ! Array.isArray( items ) || ! items.length ) {
					setStatus( container.getAttribute( 'data-empty' ) );
					return;
				}

				var list = document.createElement( 'ul' );
				list.className = 'df-favorites-list';

				items.forEach( function ( item ) {
					var li = document.createElement( 'li' );
					li.className = 'df-favorites-item';

					var link = document.createElement( 'a' );
					link.className = 'df-favorites-link';
					link.href = item.url || '#';

					if ( item.thumbnail ) {
						var img = document.createElement( 'img' );
						img.className = 'df-favorites-thumb';
						img.src = item.thumbnail;
						img.alt = '';
						img.loading = 'lazy';
						link.appendChild( img );
					}

					// textContent statt innerHTML: kein HTML-Injection-Vektor aus REST-Daten.
					var title = document.createElement( 'span' );
					title.className = 'df-favorites-title';
					title.textContent = item.title || '';
					link.appendChild( title );

					li.appendChild( link );
					list.appendChild( li );
				} );

				if ( statusEl ) {
					statusEl.parentNode.removeChild( statusEl );
				}
				container.appendChild( list );
			} )
			.catch( function () {
				setStatus( container.getAttribute( 'data-error' ) );
			} );
	}

	/**
	 * Initialisierung: Migration, Button-Hydration, Archive, Klick-Delegation.
	 */
	function init() {
		migrateLegacy();
		hydrateButtons( document );
		document.querySelectorAll( '.df-favorites-archive' ).forEach( renderArchive );

		// Event-Delegation: ein Listener am Body fängt alle (auch später eingefügte) Buttons.
		document.body.addEventListener( 'click', function ( event ) {
			var button = event.target.closest ? event.target.closest( '.df-favorite-button' ) : null;
			if ( ! button ) {
				return;
			}
			event.preventDefault();
			toggle( button );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
