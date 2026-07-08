import html2pdf from 'html2pdf.js';
import '../css/print/print.scss';
import '../css/print/pdf-download.scss';
import './print/index.js';

// Private notes on the print page.
import './public/private-notes.js';

// Allow prevent sleep on the print page;
import './public/prevent-sleep.js';

// For adjustable servings and unit conversion on the print page.
import './public/manager-premium-ingredients.js';
import './public/manager-premium.js';
import './public/servings-changer.js';
import '../../addons-pro/unit-conversion/assets/js/public.js';
import './public/advanced-servings.js';
import '../../../wp-recipe-maker/assets/js/public/temperature.js';

if ( window.WPRMPrint && 'function' === typeof window.WPRMPrint.setPdfGenerator ) {
    window.WPRMPrint.setPdfGenerator( html2pdf );
}
