const customTaxonomiesEndpoint = wprm_admin.endpoints.custom_taxonomies;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    delete( key ) {
        const data = {
            key,
        };

        return ApiWrapper.call( customTaxonomiesEndpoint, 'DELETE', data );
    },
};
