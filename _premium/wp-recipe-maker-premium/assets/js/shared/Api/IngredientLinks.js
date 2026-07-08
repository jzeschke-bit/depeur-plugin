const ingredientLinksEndpoint = wprmp_admin.endpoints.ingredient_links;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    getGlobal(ingredients) {
        const data = {
            ingredients,
        };

        return ApiWrapper.call( `${ingredientLinksEndpoint}`, 'POST', data );
    },
    saveGlobal(links) {
        const data = {
            links,
        };

        return ApiWrapper.call( `${ingredientLinksEndpoint}`, 'PUT', data );
    },
};
