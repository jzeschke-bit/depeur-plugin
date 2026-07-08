import React from 'react';
const { hooks } = WPRecipeMakerAdmin['wp-recipe-maker/dist/shared'];
import { __wprm } from 'Shared/Translations';

// Component for when products integration is not available
const ProductsUnavailable = () => (
    <div>
        <p>{ __wprm( 'Link your ingredients and equipment to the eCommerce products on your own site to help visitors find and purchase the exact items they need for your recipes.' ) } { __wprm( 'Currently supported integrations:' ) } WooCommerce</p>
        <p>
            <a href="https://demo.wprecipemaker.com/ecommerce-products/" target="_blank">
                { __wprm( 'Learn more about our Products feature' ) }
            </a>
        </p>
    </div>
);

// General Modal.
import Amazon from './amazon';
import Nutrient from './nutrient';
import Nutrition from './nutrition';
import NutritionSearch from './nutrition-search';
import CustomField from './custom-field';
import Product from './product';

const premiumModal = {
    amazon: Amazon,
    nutrient: Nutrient,
    nutrition: Nutrition,
    'nutrition-search': NutritionSearch,
    'custom-field': CustomField,
    product: Product,
};

hooks.addFilter( 'modal', 'wp-recipe-maker', ( modal ) => {
    Object.keys( premiumModal ).map( ( id ) => {
        // Replace if exists, add otherwise.
        if ( modal.hasOwnProperty( id ) ) {
            modal[ id ] = premiumModal[ id ];
        } else {
            modal[ id ] = premiumModal[ id ];
        }
    });

    return modal;
} );



// Recipe Modal.
import EditEquipmentAffiliate from './recipe/edit-equipment-affiliate';
import EditIngredientLinks from './recipe/edit-ingredient-links';
import NutritionCalculation from './recipe/nutrition-calculation';

const premiumModalRecipe = {
    'equipment-affiliate': EditEquipmentAffiliate,
    'ingredient-links': EditIngredientLinks,
    'nutrition-calculation': NutritionCalculation,
};

hooks.addFilter( 'modalRecipe', 'wp-recipe-maker', ( modalRecipe ) => {
    Object.keys( premiumModalRecipe ).map( ( id ) => {
        // Replace if exists, add otherwise.
        if ( modalRecipe.hasOwnProperty( id ) ) {
            modalRecipe[ id ] = premiumModalRecipe[ id ];
        } else {
            modalRecipe[ id ] = premiumModalRecipe[ id ];
        }
    });

    return modalRecipe;
} );


// Recipe Ingredients Modal
// import UnitConversion from './recipe/ingredients/UnitConversion';
import IngredientLinks from './recipe/ingredients/IngredientLinks';
import Products from './recipe/Products';

const premiumModalRecipeIngredients = {
    // 'unit-conversion': { // Added in /wp-recipe-maker/assets/js/admin-modal/recipe/edit/RecipeIngredients/index.js to prevent invariant errors.
    //     block: UnitConversion,
    // },
    'ingredient-links': {
        block: IngredientLinks,
    },
    'products': {
        block: wprm_admin_modal.products_integrations_available ? Products : ProductsUnavailable,
    },
};

hooks.addFilter( 'modalRecipeIngredients', 'wp-recipe-maker', ( modalRecipeIngredients ) => {
    Object.keys( premiumModalRecipeIngredients ).map( ( id ) => {
        // Merge if exists, add otherwise.
        if ( modalRecipeIngredients.hasOwnProperty( id ) ) {
            modalRecipeIngredients[ id ] = {
                ...modalRecipeIngredients[ id ],
                ...premiumModalRecipeIngredients[ id ],
            };
        } else {
            modalRecipeIngredients[ id ] = premiumModalRecipeIngredients[ id ];
        }
    });

    return modalRecipeIngredients;
} );

// Recipe Equipment Modal
import EquipmentAffiliate from './recipe/equipment/EquipmentAffiliate';

const premiumModalRecipeEquipment = {
    'equipment-affiliate': {
        block: EquipmentAffiliate,
    },
    'products': {
        block: wprm_admin_modal.products_integrations_available ? Products : ProductsUnavailable,
    },
};

hooks.addFilter( 'modalRecipeEquipment', 'wp-recipe-maker', ( modalRecipeEquipment ) => {
    Object.keys( premiumModalRecipeEquipment ).map( ( id ) => {
        // Merge if exists, add otherwise.
        if ( modalRecipeEquipment.hasOwnProperty( id ) ) {
            modalRecipeEquipment[ id ] = {
                ...modalRecipeEquipment[ id ],
                ...premiumModalRecipeEquipment[ id ],
            };
        } else {
            modalRecipeEquipment[ id ] = premiumModalRecipeEquipment[ id ];
        }
    });

    return modalRecipeEquipment;
} );
