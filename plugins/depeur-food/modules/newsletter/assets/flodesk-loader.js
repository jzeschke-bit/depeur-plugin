/**
 * flodesk-loader.js — lädt das Flodesk-Universal-Script (offizieller Loader).
 *
 * WARUM: Unsere Formulare (Providers\Flodesk) sind portiertes Flodesk-Inline-Markup, das erst
 * durch das Flodesk-Universal-Script „lebendig" wird — es fängt den Submit ab (AJAX statt
 * roher POST) UND hängt Flodesks Anti-Bot-Token an. OHNE dieses Script macht der Browser einen
 * nackten POST auf die Formular-Action; Flodesks Backend sieht keinen Token und verlangt dann
 * das „I am not a robot"-Captcha (Conversion-Killer). Die Alt-Plugin-Version lud das Script
 * NICHT selbst und funktionierte nur zufällig mit, wenn ein natives Flodesk-Popup auf derselben
 * Seite es geladen hatte. Hier laden wir es selbst — 1:1 der offizielle Flodesk-Loader.
 *
 * Der Loader legt `window.fd` als Queue an; nachfolgende `window.fd('form:handle', …)`-Aufrufe
 * (df-newsletter.js) werden gepuffert und ausgeführt, sobald das Universal-Bundle geladen ist.
 * Idempotent: ein bereits vorhandenes `window.fd` (z. B. von einem nativen Popup) wird NICHT
 * überschrieben (`w[n] = w[n] || fn`), es entsteht also kein Konflikt.
 *
 * @package Depeur\Food\Modules\Newsletter
 */
( function ( w, d, t, h, s, n ) {
	w.FlodeskObject = n;
	var fn = function () {
		( w[ n ].q = w[ n ].q || [] ).push( arguments );
	};
	w[ n ] = w[ n ] || fn;
	var f = d.getElementsByTagName( t )[ 0 ];
	var v = '?v=' + Math.floor( new Date().getTime() / ( 120 * 1000 ) ) * 60;
	var sm = d.createElement( t );
	sm.async = true;
	sm.type = 'module';
	sm.src = h + s + '.mjs' + v;
	f.parentNode.insertBefore( sm, f );
	var sn = d.createElement( t );
	sn.async = true;
	sn.noModule = true;
	sn.src = h + s + '.js' + v;
	f.parentNode.insertBefore( sn, f );
}( window, document, 'script', 'https://assets.flodesk.com', '/universal', 'fd' ) );
