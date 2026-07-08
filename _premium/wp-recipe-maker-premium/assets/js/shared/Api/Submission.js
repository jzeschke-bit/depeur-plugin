const submissionEndpoint = wprmp_admin.endpoints.recipe_submission;

import ApiWrapper from 'Shared/ApiWrapper';

export default {
    approve(id, createPost) {
        const data = {
            createPost,
        }

        return ApiWrapper.call( `${submissionEndpoint}/approve/${id}`, 'POST', data );
    },
};
