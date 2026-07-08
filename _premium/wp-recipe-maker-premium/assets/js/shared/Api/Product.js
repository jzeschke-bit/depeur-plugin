const productEndpoint = wprmp_admin.endpoints.product;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    search( search ) {
        const data = {
            search,
        };

        return ApiWrapper.call( `${productEndpoint}/search`, 'POST', data );
    },
    getAll( taxonomy, items ) {
        const data = {
            taxonomy,
            items,
        };

        return ApiWrapper.call( `${productEndpoint}/bulk`, 'POST', data );
    },
    getVariations( productId ) {
        const data = {
            product_id: productId,
        };

        return ApiWrapper.call( `${productEndpoint}/variations`, 'POST', data );
    },
};
