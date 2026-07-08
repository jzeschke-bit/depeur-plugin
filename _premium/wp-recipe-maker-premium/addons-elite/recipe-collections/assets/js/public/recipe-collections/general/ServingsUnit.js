import Helpers from 'Shared/Helpers';
import { formatQuantity, parseQuantity } from 'Shared/quantities';

const adjustableRegex = /\[adjustable\]([\s\S]*?)\[\/adjustable\]/ig;

const getPlainTextServingsUnit = ( item ) => {
    if ( item.servingsUnit ) {
        return Helpers.stripAdjustableShortcodes( item.servingsUnit );
    }

    if ( item.servingsUnitRaw ) {
        return Helpers.stripAdjustableShortcodes( item.servingsUnitRaw );
    }

    return '';
};

const getOriginalServingsParsed = ( item ) => {
    const candidates = [
        item.originalServingsParsed,
        item.originalServings,
        item.servings,
    ];

    for ( const candidate of candidates ) {
        const parsed = parseQuantity( candidate );

        if ( false !== parsed && ! Number.isNaN( parsed ) && 0 < parsed ) {
            return parsed;
        }
    }

    return false;
};

const getAdjustedQuantity = ( quantity, currentServings, originalServingsParsed ) => {
    if ( 0 >= originalServingsParsed ) {
        return quantity;
    }

    return quantity * ( currentServings / originalServingsParsed );
};

const getAdjustableServingsDecimals = () => {
    const settings = typeof window.wprm_public !== 'undefined' && window.wprm_public.settings
        ? window.wprm_public.settings
        : {};
    const decimals = parseInt( settings.adjustable_servings_round_to_decimals, 10 );

    return Number.isNaN( decimals ) ? 2 : Math.max( 0, decimals );
};

export const getInteractiveServingsUnit = ( item ) => {
    const plainText = getPlainTextServingsUnit( item );
    const raw = item && item.servingsUnitRaw ? String( item.servingsUnitRaw ) : '';

    if ( ! raw || ! raw.match( adjustableRegex ) ) {
        adjustableRegex.lastIndex = 0;
        return plainText;
    }

    const currentServings = parseQuantity( item.servings );
    const originalServingsParsed = getOriginalServingsParsed( item );

    if ( false === currentServings || Number.isNaN( currentServings ) || ! originalServingsParsed ) {
        adjustableRegex.lastIndex = 0;
        return plainText;
    }

    let hasAdjustedContent = false;
    const rendered = raw.replace( adjustableRegex, ( match, content ) => {
        const parsedQuantity = parseQuantity( content );

        if ( false === parsedQuantity || Number.isNaN( parsedQuantity ) ) {
            return content;
        }

        hasAdjustedContent = true;

        return formatQuantity(
            getAdjustedQuantity( parsedQuantity, currentServings, originalServingsParsed ),
            getAdjustableServingsDecimals(),
            true
        );
    } ).trim();

    adjustableRegex.lastIndex = 0;

    return hasAdjustedContent ? rendered : plainText;
};

export default {
    getInteractiveServingsUnit,
};
