import React, { Fragment } from 'react';

const demoData = {
    calories: 431,
    carbohydrates: 89,
    protein: 33,
    fat: 22,
    'saturated_fat': 6,
    'polyunsaturated_fat': 4,
    'monounsaturated_fat': 2,
    'trans_fat': 2,
    cholesterol: 55,
    sodium: 189,
    potassium: 430,
    fiber: 13,
    sugar: 16,
    'vitamin_a': 1023,
    'vitamin_b1': 0.6,
    'vitamin_b2': 0.3,
    'vitamin_b3': 3,
    'vitamin_b5': 4,
    'vitamin_b6': 0.3,
    'vitamin_b12': 1.1,
    'vitamin_c': 13,
    'vitamin_d': 7,
    'vitamin_e': 3.5,
    'vitamin_k': 42,
    calcium: 202,
    copper: 0.5,
    folate: 103,
    iron: 4,
    manganese: 0.3,
    magnesium: 12,
    phosphorus: 86,
    selenium: 55,
    zinc: 11,
};

const BlockNutrient = (props) => {
    if ( ! props.block ) {
        return null;
    }

    // Serving size is separate block.
    if ( 'serving_size' === props.block.nutrient ) {
        return null;
    }

    // Default values.
    let name = props.block.nutrient;
    let unit = '';
    let daily = false;
    let value = demoData.hasOwnProperty( props.block.nutrient ) ? demoData[ props.block.nutrient ] : 123;
    let extra = false;

    // Maybe show per 100g.
    if ( '100g' === props.layout.properties.display_values ) {
        // Get current number of decimals.
        let decimals = 0;
        if (Math.floor(value) !== value) decimals = value.toString().split(".")[1].length || 0;

        value = value * 100/123;
        value = Math.round( value * 10^decimals ) / 10^decimals;
    }

    // Check for nutrient data.
    const nutrient = wprm_admin_modal.nutrition.hasOwnProperty( props.block.nutrient ) ? wprm_admin_modal.nutrition[ props.block.nutrient ] : false;
    if ( nutrient ) {
        name = nutrient.label;
        unit = nutrient.unit;
        daily = nutrient.hasOwnProperty( 'daily' ) ? nutrient.daily : false;

        // Only display active nutrients.
        if ( false === nutrient.active ) {
            return null;
        }
    }

    // Daily value.
    let percentage = 0.0;
    if ( daily && value ) {
        percentage = Math.round( value / daily * 100 );
    }

    // Special case: calories.
    if ( 'calories' === props.block.nutrient ) {
        if ( 'fat' === props.block.calories ) {
            unit = '';
            daily = false;
            extra = `${ wprmp_nutrition_label_layout.text.fat_calories } 198`;
        } else if ( 'normal' === props.block.calories ) {
            daily = false;
            extra = value;
            value = false;
        }
    }

    return (
        <div className={ `wprmp-nutrition-label-block-nutrient wprmp-nutrition-label-block-nutrient-${ props.block.style }`}>
            <div className="wprmp-nutrition-label-block-nutrient-name-value-unit-container">
                <div className="wprmp-nutrition-label-block-nutrient-name">
                    { name }
                </div>
                {
                    false !== value
                    &&
                    <Fragment>
                        <div className="wprmp-nutrition-label-block-nutrient-spacer">&nbsp;</div>
                        <div className="wprmp-nutrition-label-block-nutrient-value-unit-container">
                            <div className="wprmp-nutrition-label-block-nutrient-value">{ value }</div>
                            <div className="wprmp-nutrition-label-block-nutrient-unit">{ unit }</div>
                        </div>
                    </Fragment>
                }
            </div>
            {
                false !== daily
                &&
                <div className="wprmp-nutrition-label-block-nutrient-daily-container">
                    <div className="wprmp-nutrition-label-block-nutrient-daily">{ percentage }</div>
                    <div className="wprmp-nutrition-label-block-nutrient-percentage">%</div>
                </div>
            }
            {
                false !== extra
                &&
                <div className="wprmp-nutrition-label-block-nutrient-extra-container">
                    { extra }
                </div>
            }
        </div>
    );
}

export default BlockNutrient;