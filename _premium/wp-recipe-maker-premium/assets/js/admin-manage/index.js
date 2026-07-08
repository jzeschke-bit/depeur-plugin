const { hooks } = WPRecipeMakerAdmin['wp-recipe-maker/dist/shared'];
import premiumDatatables from './DataTableConfig';

hooks.addFilter( 'datatables', 'wp-recipe-maker', ( datatables ) => {
    Object.keys( premiumDatatables ).map( ( id ) => {
        // Merge if exists, add otherwise.
        if ( datatables.hasOwnProperty( id ) ) {
            datatables[ id ] = {
                ...datatables[ id ],
                ...premiumDatatables[ id ],
            };
        } else {
            datatables[ id ] = premiumDatatables[ id ];
        }
    });

    return datatables;
} );