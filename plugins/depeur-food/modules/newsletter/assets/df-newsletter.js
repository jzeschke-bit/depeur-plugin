/**
 * df-newsletter.js — Frontend-Verhalten des Newsletter-Moduls (Vanilla, kein jQuery).
 *
 * Portiert aus spotlight-subscribe/subscribe.js (vollständig): Schließen-Button mit
 * localStorage-Merker, „bereits abonniert" -> Formulare ausblenden, IntersectionObserver
 * für die `.in-view`-Grau-Überblendung (>30 % sichtbar), Submit-Merker. Zusätzlich der
 * Flodesk-Init (window.fd), der im Legacy als Inline-<script> pro Formular stand und hier
 * — gemäß Asset-Convention (kein Inline-<script>) — in die Datei gezogen ist. Alle
 * Selektoren auf die df-Klassen umgestellt (.spotlight-subscribe-wrapper -> .df-newsletter,
 * .spotlight-close-button -> .df-newsletter__close).
 *
 * @package Depeur\Food\Modules\Newsletter
 */
( function () {
	'use strict';

	// Merker-Key bewusst aus dem Legacy übernommen: Nutzer, die den Newsletter bereits
	// weggeklickt/abonniert haben, sollen ihn nach der Migration nicht erneut sehen.
	var STORAGE_KEY = 'newsletter_subscribed';

	// Sichtbarkeits-Schwelle (30 %) für den Grau-Überblendungs-Effekt (Legacy-Wert).
	var VISIBLE_RATIO = 0.3;

	// Scroll-Tiefe (Anteil der Seite), ab der die Popup-Variante eingeblendet wird.
	var POPUP_SCROLL_TRIGGER = 0.5;

	/**
	 * Blendet alle Newsletter-Wrapper aus (App-Promotion bleibt sichtbar — Legacy-Verhalten).
	 */
	function hideAll() {
		var wrappers = document.querySelectorAll( '.df-newsletter' );
		Array.prototype.forEach.call( wrappers, function ( wrapper ) {
			wrapper.style.display = 'none';
		} );
	}

	/**
	 * Liest den Merker (defensiv: localStorage kann in Privat-Modi werfen).
	 *
	 * @return {boolean} true, wenn der Newsletter bereits abonniert/weggeklickt wurde.
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
	 * Initialisiert das Flodesk-Inline-Formular, sofern das Universal-Script geladen ist.
	 * Die Form-ID stammt aus dem data-Attribut (der Provider bleibt inline-<script>-frei).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function initFlodesk( wrapper ) {
		var formId = wrapper.getAttribute( 'data-df-flodesk-form-id' );
		if ( ! formId || typeof window.fd !== 'function' ) {
			return;
		}
		window.fd( 'form:handle', {
			formId: formId,
			rootEl: '.ff-' + formId,
			embedType: 'inline'
		} );
	}

	/**
	 * Blendet genau EINEN Wrapper aus (für den nicht-persistenten Minimal-Schließen-Fall).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function hideOne( wrapper ) {
		wrapper.style.display = 'none';
	}

	/**
	 * Verdrahtet den Schließen-Button.
	 *
	 * Spotlight: merkt die Ablehnung (localStorage) und blendet alle Formulare aus — einmal
	 * weg, bleibt weg. Minimal + Popup: blenden NUR diesen Wrapper für die aktuelle Seite aus,
	 * OHNE Merker → beim nächsten Seitenaufruf/Refresh erscheint das Formular erneut (bewusst,
	 * für mehr Sichtbarkeit/Conversions). Ein tatsächliches Abo (Submit) setzt in ALLEN
	 * Varianten den Merker (siehe bindSubmit), sodass Abonnenten künftig nichts mehr sehen.
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
			// Nur Spotlight merkt sich das Wegklicken dauerhaft.
			if ( 'spotlight' === mode ) {
				remember();
				hideAll();
				return;
			}
			hideOne( wrapper );
		} );
	}

	/**
	 * Aktuelle Scroll-Tiefe als Anteil (0..1) der scrollbaren Gesamthöhe.
	 *
	 * @return {number} 0 (oben) bis 1 (unten). Bei nicht-scrollbaren Seiten: 1.
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
	 * Blendet die Popup-Variante erst ab POPUP_SCROLL_TRIGGER ein (dann einmalig, Listener weg).
	 * Ist die Schwelle beim Laden schon überschritten (kurze Seite / Deep-Link), sofort zeigen.
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
	 * Verdrahtet den Formular-Submit: setzt den Merker (nach Abo nicht erneut zeigen).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function bindSubmit( wrapper ) {
		var form = wrapper.querySelector( 'form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener( 'submit', function () {
			remember();
		} );
	}

	/**
	 * Beobachtet den Wrapper und schaltet `.in-view` je nach Sichtbarkeit (Grau-Überblendung).
	 *
	 * @param {Element} wrapper Ein .df-newsletter-Wrapper.
	 */
	function observe( wrapper ) {
		if ( typeof window.IntersectionObserver !== 'function' ) {
			return;
		}
		var observer = new window.IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				// Effekt erst ab 30 % Sichtbarkeit an, darunter wieder aus.
				if ( entry.intersectionRatio > VISIBLE_RATIO ) {
					entry.target.classList.add( 'in-view' );
				} else {
					entry.target.classList.remove( 'in-view' );
				}
			} );
		}, {
			threshold: [ 0, VISIBLE_RATIO ],
			rootMargin: '0px 0px -20% 0px' // Effekt etwas früher beenden (unterer Rand).
		} );
		observer.observe( wrapper );
	}

	/**
	 * Einstiegspunkt: bei bereits erfolgter Ablehnung/Abo direkt ausblenden, sonst verdrahten.
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
				// Nur Spotlight bekommt die `.in-view`-Vollbild-Abdunklung.
				observe( wrapper );
			} else if ( 'popup' === mode ) {
				// Popup: fest positioniert, erst ab Scroll-Tiefe einblenden.
				armPopupReveal( wrapper );
			}
			// 'minimal': inline + sticky wie Spotlight, ohne Abdunklung → keine Zusatz-Logik.

			initFlodesk( wrapper );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
