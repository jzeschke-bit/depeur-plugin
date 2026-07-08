const conversionEndpoint = wprmp_admin.endpoints.unit_conversion;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    get( ingredients, system = 'default' ) {
        const data = {
            ingredients,
            system,
        };

        return ApiWrapper.call( `${conversionEndpoint}`, 'POST', data );
    },    
};
