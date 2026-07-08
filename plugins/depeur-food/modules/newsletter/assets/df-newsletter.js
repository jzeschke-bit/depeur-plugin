/**
 * df-newsletter.js — Frontend-Verhalten des Newsletter-Moduls (Vanilla, kein jQuery).
 *
 * Zwei Aufgaben:
 *   1. Schließen-Button: blendet den Formular-Wrapper aus.
 *   2. Flodesk-Init: bindet das Inline-Formular via window.fd(...), sofern das Flodesk-
 *      Universal-Script geladen ist (die Form-ID kommt aus dem data-Attribut — so bleibt
 *      der Provider frei von Inline-<script>).
 *
 * @package Depeur\Food\Modules\Newsletter
 */
( function () {
	'use strict';

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

	function bindClose( wrapper ) {
		var button = wrapper.querySelector( '.df-newsletter__close' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', function () {
			wrapper.hidden = true;
			wrapper.style.display = 'none';
		} );
	}

	function ready() {
		var wrappers = document.querySelectorAll( '.df-newsletter' );
		Array.prototype.forEach.call( wrappers, function ( wrapper ) {
			bindClose( wrapper );
			initFlodesk( wrapper );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
