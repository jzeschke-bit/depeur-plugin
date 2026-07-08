import { __wprm } from 'Shared/Translations';

import ColumnsCollections from './collections/Columns';
import ColumnsNutrients from './nutrients/Columns';
import ColumnsNutrition from './nutrition/Columns';
import ColumnsRecipeSubmission from './recipe-submission/Columns';
import ColumnsUserCollections from './user-collections/Columns';
import ColumnsCustomFields from './custom-fields/Columns';

let datatables = {}

// Add selected columns.
datatables.ingredient = {
    selectedColumns: ['id','name','total_count', 'link', 'link_nofollow'],
};

datatables.equipment = {
    selectedColumns: ['id','name','total_count', 'link', 'link_nofollow'],
};

datatables['custom-fields'] = {
    required: 'pro',
    parent: __wprm( 'Your Custom Fields' ),
    id: 'custom-fields',
    route: 'custom-fields',
    label: {
        singular: __wprm( 'Custom Field' ),
        plural: __wprm( 'Custom Fields' ),
    },
    bulkEdit: false,
    createButton: (datatable) => {
        WPRM_Modal.open( 'custom-field', {
            saveCallback: () => datatable.refreshData(),
        } );
    },
    selectedColumns: false,
    columns: ColumnsCustomFields,
};

datatables.nutrition = {
    required: 'pro',
    parent: __wprm( 'Your Custom Fields' ),
    id: 'nutrition_ingredient',
    route: 'taxonomy',
    label: {
        singular: __wprm( 'Custom Nutrition Ingredient' ),
        plural: __wprm( 'Custom Nutrition' ),
    },
    bulkEdit: {
        route: 'taxonomy',
        type: 'nutrition_ingredient',
    },
    createButton: (datatable) => {
        WPRM_Modal.open( 'nutrition', {
            saveCallback: () => datatable.refreshData(),
        } );
    },
    selectedColumns: false,
    columns: ColumnsNutrition,
};

datatables.nutrients = {
    required: 'premium',
    parent: __wprm( 'Your Custom Fields' ),
    id: 'nutrition_ingredient',
    route: 'nutrient',
    label: {
        singular: __wprm( 'Custom Nutrient' ),
        plural: __wprm( 'Custom Nutrients' ),
    },
    bulkEdit: false,
    selectedColumns: false,
    columns: ColumnsNutrients,
};

// Only have create custom nutrient button in Pro Bundle.
if ( wprm_admin.addons.pro ) {
    datatables.nutrients.createButton = (datatable) => {
        WPRM_Modal.open( 'nutrient', {
            saveCallback: () => datatable.refreshData(),
        } );
    };
}

datatables.collections = {
    required: 'elite',
    parent: __wprm( 'Features' ),
    id: 'collections',
    route: 'saved-collections',
    label: {
        singular: __wprm( 'Saved Collection' ),
        plural: __wprm( 'Saved Collections' ),
    },
    bulkEdit: {
        route: 'collection',
        type: 'collection',
    },
    createButton: (datatable) => {
        window.location = wprmp_admin.manage.collections_url;
    },
    selectedColumns: [ 'id', 'date', 'name', 'description', 'default', 'push', 'template', 'quick_add', 'nbrItems' ],
    columns: ColumnsCollections,
};

datatables['user-collections'] = {
    required: 'elite',
    parent: __wprm( 'Features' ),
    id: 'user-collections',
    route: 'user-collections',
    label: {
        singular: __wprm( 'User Collection' ),
        plural: __wprm( 'User Collections' ),
    },
    bulkEdit: false,
    createButton: false,
    selectedColumns: [ 'id', 'display_name', 'collections', 'inbox', 'items' ],
    columns: ColumnsUserCollections,
};

datatables['recipe-submission'] = {
    required: 'elite',
    parent: __wprm( 'Features' ),
    title: `${ __wprm( 'Recipe Submissions' ) }${ wprmp_admin.manage.recipe_submissions ? ` (${ wprmp_admin.manage.recipe_submissions })` : '' }`,
    id: 'recipe-submission',
    route: 'recipe-submission',
    label: {
        singular: __wprm( 'Recipe Submission' ),
        plural: __wprm( 'Recipe Submissions' ),
    },
    bulkEdit: {
        route: 'recipe-submission',
        type: 'recipe-submission',
    },
    createButton: false,
    selectedColumns: false,
    columns: ColumnsRecipeSubmission,
};

export default datatables;
