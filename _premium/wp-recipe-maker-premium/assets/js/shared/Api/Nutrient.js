const nutrientEndpoint = wprmp_admin.endpoints.nutrient;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    update(editing, nutrient) {
        const data = {
            key: nutrient.key,
            nutrient,
        }

        const method = editing ? 'PUT' : 'POST';

        return ApiWrapper.call( nutrientEndpoint, method, data );
    },
    delete(key) {
        const data = {
            key,
        };

        return ApiWrapper.call( nutrientEndpoint, 'DELETE', data );
    },
};
