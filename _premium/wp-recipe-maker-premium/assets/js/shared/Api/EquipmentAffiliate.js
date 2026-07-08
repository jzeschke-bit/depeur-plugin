const equipmentAffiliateEndpoint = wprmp_admin.endpoints.equipment_affiliate;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    get(equipment) {
        const data = {
            equipment,
        };

        return ApiWrapper.call( `${equipmentAffiliateEndpoint}`, 'POST', data );
    },
    save(equipment) {
        const data = {
            equipment,
        };

        return ApiWrapper.call( `${equipmentAffiliateEndpoint}`, 'PUT', data );
    },
};
