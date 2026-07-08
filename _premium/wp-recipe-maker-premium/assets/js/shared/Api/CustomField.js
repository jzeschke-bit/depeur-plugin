const customFieldsEndpoint = wprmp_admin.endpoints.custom_fields;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    save( editing, field ) {
        const data = {
            ...field,
        };

        const method = editing ? 'PUT' : 'POST';

        return ApiWrapper.call( customFieldsEndpoint, method, data );
    },
    delete( key ) {
        const data = {
            key,
        };

        return ApiWrapper.call( customFieldsEndpoint, 'DELETE', data );
    },
};
