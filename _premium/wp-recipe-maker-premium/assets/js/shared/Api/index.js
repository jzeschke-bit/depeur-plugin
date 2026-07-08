const { hooks } = WPRecipeMakerAdmin['wp-recipe-maker/dist/shared'];

import AiAssistant from './AiAssistant';
import Amazon from './Amazon';
import Collection from './Collection';
import CustomField from './CustomField';
import CustomTaxonomy from './CustomTaxonomy';
import EquipmentAffiliate from './EquipmentAffiliate';
import IngredientLinks from './IngredientLinks';
import Nutrient from './Nutrient';
import Nutrition from './Nutrition';
import Product from './Product';
import Submission from './Submission';
import UnitConversion from './UnitConversion';

const premiumApi = {
    aiAssistant: AiAssistant,
    amazon: Amazon,
    collection: Collection,
    customField: CustomField,
    customTaxonomy: CustomTaxonomy,
    equipmentAffiliate: EquipmentAffiliate,
    ingredientLinks: IngredientLinks,
    nutrient: Nutrient,
    nutrition: Nutrition,
    product: Product,
    submission: Submission,
    unitConversion: UnitConversion,
};

hooks.addFilter( 'api', 'wp-recipe-maker', ( api ) => {
    Object.keys( premiumApi ).map( ( id ) => {
        // Merge if exists, add otherwise.
        if ( api.hasOwnProperty( id ) ) {
            api[ id ] = {
                ...api[ id ],
                ...premiumApi[ id ],
            };
        } else {
            api[ id ] = premiumApi[ id ];
        }
    });

    return api;
} );