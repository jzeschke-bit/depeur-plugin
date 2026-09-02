/**
 * df-newsletter.js — Verhalten der EIGENEN Newsletter-Formulare (spotlight/minimal/popup).
 *
 * Absenden läuft per fetch an den Plugin-REST-Endpoint (Rest\Subscribe_Controller), NICHT an
 * Flodesks Widget → kein „I am not a robot"-Captcha. Der Endpoint trägt serverseitig über die
 * Flodesk-API ein. Vanilla, kein jQuery. Zusätzlich: Schließen-Verhalten je Modus, Spotlight-
 * Abdunklung (IntersectionObserver) und Popup-Einblendung ab Scroll-Tiefe.
 *
 * Konfiguration kommt aus window.dfNewsletter (wp_localize_script): restUrl, nonce, Meldungen.
 *
 * @package Depeur\Food\Modules\Newsletter
 */
( function () {
	'use strict';

	// Merker-Key: wer sich eingetragen (oder im Spotlight weggeklickt) hat, sieht es nicht erneut.
	var STORAGE_KEY = 'newsletter_subscribed';

	// Sichtbarkeits-Schwelle (30 %) für die Spotlight-Abdunklung (Legacy-Wert).
	var VISIBLE_RATIO = 0.3;

	// Scroll-Tiefe (Anteil der Seite), ab der die Popup-Variante eingeblendet wird.
	var POPUP_SCROLL_TRIGGER = 0.5;

	/**
	 * Lokalisierte Konfiguration (REST-URL, Nonce, Meldungstexte).
	 *
	 * @return {Object}
	 */
	function config() {
		return window.dfNewsletter || {};
	}

	/**
	 * Blendet alle Newsletter-Wrapper aus.
	 */
	function hideAll() {
		var wrappers = document.querySelectorAll( '.df-newsletter' );
		Array.prototype.forEach.call( wrappers, function ( wrapper ) {
			wrapper.style.display = 'none';
		} );
	}

	/**
	 * Blendet genau EINEN Wrapper aus (nicht-persistentes Schließen bei minimal/popup).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function hideOne( wrapper ) {
		wrapper.style.display = 'none';
	}

	/**
	 * Liest den Merker (defensiv: localStorage kann in Privat-Modi werfen).
	 *
	 * @return {boolean}
	 */
	function isDismissed() {
		try {
			return null !== window.localStorage.getItem( STORAGE_KEY );
		} catch ( e ) {
			return false;
		}
	}

	/**
	 * Setzt den Merker (defensiv gekapselt).
	 */
	function remember() {
		try {
			window.localStorage.setItem( STORAGE_KEY, 'true' );
		} catch ( e ) {
			// localStorage nicht verfügbar — Merker entfällt still.
		}
	}

	/**
	 * Setzt eine Status-/Fehlermeldung im Formular.
	 *
	 * @param {Element} el   Das .df-newsletter__message-Element.
	 * @param {string}  text Meldungstext.
	 * @param {string}  type '', 'success' oder 'error'.
	 */
	function setMessage( el, text, type ) {
		if ( ! el ) {
			return;
		}
		el.textContent = text || '';
		el.className = 'df-newsletter__message' + ( type ? ' is-' + type : '' );
	}

	/**
	 * Verdrahtet den Schließen-Button (Spotlight persistent, minimal/popup nur für die Seite).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 * @param {string}  mode    'spotlight', 'minimal' oder 'popup'.
	 */
	function bindClose( wrapper, mode ) {
		var button = wrapper.querySelector( '.df-newsletter__close' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			if ( 'spotlight' === mode ) {
				remember();
				hideAll();
				return;
			}
			hideOne( wrapper );
		} );
	}

	/**
	 * Verdrahtet den Submit: sendet per fetch an den Plugin-REST-Endpoint (kein Flodesk-Widget).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function bindSubmit( wrapper ) {
		var form = wrapper.querySelector( '.df-newsletter__form' );
		if ( ! form ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var cfg     = config();
			var msgEl   = form.querySelector( '.df-newsletter__message' );
			var button  = form.querySelector( 'button[type="submit"]' );
			var emailEl = form.querySelector( 'input[name="email"]' );
			var hpEl    = form.querySelector( 'input[name="df_hp"]' );
			var email   = emailEl ? emailEl.value.trim() : '';

			if ( ! email ) {
				return;
			}
			if ( ! cfg.restUrl || typeof window.fetch !== 'function' ) {
				setMessage( msgEl, cfg.error, 'error' );
				return;
			}

			if ( button ) {
				button.disabled = true;
			}
			setMessage( msgEl, cfg.sending, '' );

			window.fetch( cfg.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( {
					email: email,
					nonce: cfg.nonce || '',
					hp: hpEl ? hpEl.value : ''
				} )
			} ).then( function ( resp ) {
				return resp.json().then( function ( data ) {
					return { ok: resp.ok, data: data };
				} );
			} ).then( function ( result ) {
				var data = result.data || {};
				if ( result.ok && data.success ) {
					setMessage( msgEl, data.message || cfg.success, 'success' );
					remember();
					var field  = form.querySelector( '.df-newsletter__field' );
					var footer = form.querySelector( '.df-newsletter__footer' );
					if ( field ) {
						field.hidden = true;
					}
					if ( footer ) {
						footer.hidden = true;
					}
				} else {
					setMessage( msgEl, data.message || cfg.error, 'error' );
					if ( button ) {
						button.disabled = false;
					}
				}
			} ).catch( function () {
				setMessage( msgEl, cfg.error, 'error' );
				if ( button ) {
					button.disabled = false;
				}
			} );
		} );
	}

	/**
	 * Beobachtet den Wrapper und schaltet `.in-view` je nach Sichtbarkeit (Spotlight-Abdunklung).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function observe( wrapper ) {
		if ( typeof window.IntersectionObserver !== 'function' ) {
			return;
		}
		var observer = new window.IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.intersectionRatio > VISIBLE_RATIO ) {
					entry.target.classList.add( 'in-view' );
				} else {
					entry.target.classList.remove( 'in-view' );
				}
			} );
		}, {
			threshold: [ 0, VISIBLE_RATIO ],
			rootMargin: '0px 0px -20% 0px'
		} );
		observer.observe( wrapper );
	}

	/**
	 * Aktuelle Scroll-Tiefe als Anteil (0..1) der scrollbaren Gesamthöhe.
	 *
	 * @return {number}
	 */
	function scrollProgress() {
		var doc = document.documentElement;
		var max = doc.scrollHeight - window.innerHeight;
		if ( max <= 0 ) {
			return 1;
		}
		return ( window.pageYOffset || doc.scrollTop || 0 ) / max;
	}

	/**
	 * Blendet die Popup-Variante erst ab POPUP_SCROLL_TRIGGER ein (dann einmalig).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper (Popup-Variante).
	 */
	function armPopupReveal( wrapper ) {
		function reveal() {
			wrapper.classList.add( 'is-visible' );
			window.removeEventListener( 'scroll', onScroll );
		}
		function onScroll() {
			if ( scrollProgress() >= POPUP_SCROLL_TRIGGER ) {
				reveal();
			}
		}

		if ( scrollProgress() >= POPUP_SCROLL_TRIGGER ) {
			reveal();
			return;
		}
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/**
	 * Einstiegspunkt: bereits abonniert/weggeklickt → ausblenden, sonst verdrahten.
	 */
	function ready() {
		if ( isDismissed() ) {
			hideAll();
			return;
		}

		var wrappers = document.querySelectorAll( '.df-newsletter' );
		Array.prototype.forEach.call( wrappers, function ( wrapper ) {
			var mode = wrapper.getAttribute( 'data-df-mode' ) || 'spotlight';
			bindClose( wrapper, mode );
			bindSubmit( wrapper );

			if ( 'spotlight' === mode ) {
				observe( wrapper );
			} else if ( 'popup' === mode ) {
				armPopupReveal( wrapper );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
