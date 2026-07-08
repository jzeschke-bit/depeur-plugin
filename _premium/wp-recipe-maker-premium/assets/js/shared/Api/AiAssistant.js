const aiAssistantEndpoint = wprmp_admin.endpoints.ai_assistant;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    generateIdeas(options) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/generate-ideas`, 'POST', options );
    },
    startNutritionReviewBatch( options ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches`, 'POST', options );
    },
    getCurrentNutritionReviewBatch() {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/current` );
    },
    getNutritionReviewBatch( id ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/${id}` );
    },
    getNutritionReviewResults( id, status = '' ) {
        const query = status ? `?status=${encodeURIComponent( status )}` : '';
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/${id}/results${query}` );
    },
    saveNutritionReviewDecision( batchId, recipeId, ingredientIndex, decision ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/${batchId}/recipe/${recipeId}/ingredient/${ingredientIndex}`, 'POST', decision );
    },
    applyNutritionReviewRecipe( batchId, recipeId ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/${batchId}/recipe/${recipeId}/apply`, 'POST' );
    },
    applyNutritionReviewReady( batchId ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/nutrition-review/batches/${batchId}/apply-ready`, 'POST' );
    },
    startUnitConversionReviewBatch( options ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches`, 'POST', options );
    },
    getCurrentUnitConversionReviewBatch() {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/current` );
    },
    getUnitConversionReviewBatch( id ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/${id}` );
    },
    getUnitConversionReviewResults( id, status = '' ) {
        const query = status ? `?status=${encodeURIComponent( status )}` : '';
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/${id}/results${query}` );
    },
    saveUnitConversionReviewDecision( batchId, recipeId, ingredientIndex, decision ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/${batchId}/recipe/${recipeId}/ingredient/${ingredientIndex}`, 'POST', decision );
    },
    applyUnitConversionReviewRecipe( batchId, recipeId ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/${batchId}/recipe/${recipeId}/apply`, 'POST' );
    },
    applyUnitConversionReviewReady( batchId ) {
        return ApiWrapper.call( `${aiAssistantEndpoint}/unit-conversion-review/batches/${batchId}/apply-ready`, 'POST' );
    },
};
