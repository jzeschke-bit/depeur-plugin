const amazonEndpoint = wprmp_admin.endpoints.amazon;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    searchProducts( search ) {
        const data = {
            search,
        };

        return ApiWrapper.call( `${amazonEndpoint}/search`, 'POST', data );
    },
    getProducts( asins ) {
        const data = {
            asins,
        };

        return ApiWrapper.call( `${amazonEndpoint}/get`, 'POST', data );
    },
};
