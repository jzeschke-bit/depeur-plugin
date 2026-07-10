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

	// Standard-Selektoren für Beitrags-Karten in Kadence-Blocks (Post Grid + Carousel),
	// auf denen automatisch ein Herz-Button injiziert wird. Bewusst auf die stabilen
	// Kadence-Container gezielt; die tatsächliche Beitrags-ID lesen wir NICHT hieraus,
	// sondern aus der WordPress-Kern-Klasse `post-<ID>` (siehe postIdFrom). Über den
	// Filter depeur_food/favorites/grid_selectors (cfg.gridSelectors) überschreibbar.
	var DEFAULT_GRID_SELECTORS =
		'.wp-block-kadence-posts article, .kt-blocks-post-grid-wrap article, ' +
		'.kb-blocks-post-carousel article, .kb-post-carousel article, .kadence-posts article';

	// Kandidaten für den Bild-Container innerhalb einer Karte (dorthin wird das Overlay-
	// Herz gehängt). Reihenfolge = Präferenz; nicht gefunden ⇒ die Karte selbst.
	var THUMB_SELECTORS =
		'.post-thumbnail-inner, .entry-featured-image-inner, .kb-blocks-post-thumbnail, ' +
		'.kt-post-image, .kb-post-thumbnail, .post-thumbnail';

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
	 * Liest die Beitrags-ID aus der WordPress-Kern-Klasse `post-<ID>` (bzw. id="post-<ID>").
	 *
	 * `post_class()`/`get_post_class()` setzt diese Klasse auf JEDER Beitrags-Karte, auch in
	 * den Kadence-Blocks. Das ist der versions-stabile Anker – im Gegensatz zu den (je
	 * Kadence-Version wechselnden) Wrapper-Klassen.
	 *
	 * @param {HTMLElement} el Die Karte (article).
	 * @return {?string} Die Beitrags-ID als String oder null.
	 */
	function postIdFrom( el ) {
		var cls = ( el.getAttribute && el.getAttribute( 'class' ) ) || '';
		var m = cls.match( /(?:^|\s)post-(\d+)(?:\s|$)/ );
		if ( m ) {
			return m[ 1 ];
		}
		var m2 = ( el.id || '' ).match( /^post-(\d+)$/ );
		return m2 ? m2[ 1 ] : null;
	}

	/**
	 * Prüft, ob die Karte einen unterstützten Post-Type trägt (Kern-Klasse `type-<pt>`).
	 *
	 * Verhindert, dass Herzen auf Karten nicht unterstützter Typen landen (der REST-Toggle
	 * würde solche ohnehin mit 400 ablehnen – hier sparen wir uns den Fehl-Button gleich).
	 *
	 * @param {HTMLElement} el Die Karte (article).
	 * @return {boolean}
	 */
	function typeSupported( el ) {
		var types = cfg.postTypes || [];
		if ( ! types.length ) {
			return true;
		}
		for ( var i = 0; i < types.length; i++ ) {
			if ( el.classList && el.classList.contains( 'type-' + types[ i ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Baut das Overlay-Herz (identisches Markup zum server-gerenderten Thumbnail-Button),
	 * damit die vorhandene Klick-Delegation, das Hydrieren und die REST-Logik 1:1 greifen.
	 *
	 * @param {string} id Beitrags-ID.
	 * @return {HTMLElement} Der Wrapper mit Button.
	 */
	function buildHeart( id ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'df-favorite-wrapper df-favorite-wrapper--injected';

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'df-favorite-button df-favorite-button--thumbnail';
		button.setAttribute( 'data-post-id', String( id ) );
		button.setAttribute( 'data-style', 'thumbnail' );
		button.setAttribute( 'aria-pressed', 'false' );
		if ( cfg.buttonLabel ) {
			button.setAttribute( 'aria-label', cfg.buttonLabel );
		}

		var icon = document.createElement( 'span' );
		icon.className = 'df-favorite-icon';
		icon.setAttribute( 'aria-hidden', 'true' );
		icon.textContent = HEART_EMPTY;

		button.appendChild( icon );
		wrapper.appendChild( button );
		return wrapper;
	}

	/**
	 * Injiziert Herz-Buttons auf Kadence-Blocks-Karten (Post Grid/Carousel), Startseite +
	 * Sidebar. Jede Karte bekommt genau ein Herz, das auf ihren eigenen Beitrag zeigt.
	 *
	 * Robust/graceful: ungültige Selektoren, fehlende ID, nicht unterstützter Typ, bereits
	 * vorhandenes Herz (z. B. Theme-Karten) oder Karten ohne Bild werden übersprungen.
	 *
	 * @param {Document|HTMLElement} root Wurzelknoten (Default: document).
	 */
	function injectGridHearts( root ) {
		if ( cfg.gridHearts === false ) {
			return;
		}

		var selectors = cfg.gridSelectors || DEFAULT_GRID_SELECTORS;
		var cards;
		try {
			cards = ( root || document ).querySelectorAll( selectors );
		} catch ( e ) {
			return; // Ungültiger Selektor (z. B. aus dem Filter) – still abbrechen.
		}

		cards.forEach( function ( card ) {
			if ( card.querySelector( '.df-favorite-button' ) ) {
				return; // Karte hat schon ein Herz (z. B. via Theme-Template-Part).
			}
			if ( ! card.querySelector( 'img' ) ) {
				return; // Kein Bild → kein sinnvolles Overlay.
			}
			if ( ! typeSupported( card ) ) {
				return;
			}

			var id = postIdFrom( card );
			if ( ! id ) {
				return;
			}

			// Bild-Container suchen; ist er ein <a>, hängen wir das Herz an dessen Elternknoten,
			// damit der Button nicht IM Link steckt (verschachtelte Interaktion vermeiden).
			var thumb = card.querySelector( THUMB_SELECTORS );
			if ( thumb && 'A' === thumb.tagName && thumb.parentNode ) {
				thumb = thumb.parentNode;
			}
			if ( ! thumb ) {
				thumb = card;
			}

			thumb.classList.add( 'df-has-favorite' );
			thumb.appendChild( buildHeart( id ) );
		} );

		hydrateButtons( root || document );
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
	 * Rendert ein Favoriten-Archiv: liest die IDs, lässt den Server sie als Kadence-Loop-
	 * Raster rendern (exakte Archiv-Karten-Optik, OE-3) und fügt das HTML ein.
	 *
	 * Das HTML stammt aus dem eigenen REST-Endpoint (server-seitig escapt) – kein Fremd-Input.
	 * Nach dem Einfügen werden die Herz-Buttons der neuen Karten hydriert (alle gemerkt).
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
			.then( function ( data ) {
				if ( ! data || ! data.html ) {
					setStatus( container.getAttribute( 'data-empty' ) );
					return;
				}

				if ( statusEl && statusEl.parentNode ) {
					statusEl.parentNode.removeChild( statusEl );
				}

				var grid = document.createElement( 'div' );
				grid.className = 'df-favorites-grid';
				// Server-gerendertes, escaptes HTML aus dem eigenen Endpoint.
				grid.innerHTML = data.html;
				container.appendChild( grid );

				// Herz-Zustand der frisch eingefügten Karten setzen (alle sind Favoriten).
				hydrateButtons( grid );
			} )
			.catch( function () {
				setStatus( container.getAttribute( 'data-error' ) );
			} );
	}

	// Öffentlicher Hook: andere Module (z. B. der Rezept-Filter) hydrieren damit die
	// Herz-Buttons frisch nachgeladener Karten (Zustand aus localStorage).
	window.dfFavoritesHydrate = function ( root ) {
		hydrateButtons( root || document );
	};

	// Öffentlicher Hook: Herzen in (evtl. später nachgeladene) Kadence-Karten injizieren.
	window.dfFavoritesInjectGrids = function ( root ) {
		injectGridHearts( root || document );
	};

	/**
	 * Initialisierung: Migration, Button-Hydration, Archive, Grid-Herzen, Klick-Delegation.
	 */
	function init() {
		migrateLegacy();
		hydrateButtons( document );
		injectGridHearts( document );
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
