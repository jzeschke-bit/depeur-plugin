import React, { Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import { __wprm } from 'Shared/Translations';
import  { formatQuantity } from 'Shared/quantities';

import Icon from '../../general/Icon';
import ItemContentClassic from './ItemContentClassic';
import ItemContentGrid from './ItemContentGrid';

const ItemContent = (props) => {
    const { item } = props;

    const addInterface = props.hasOwnProperty( 'interface' ) ? props.interface : 'drag';

    // Allow clicking on item details.
    let allowDetailsClick = false;
    const clickSetting = 'admin' === props.type ? 'parent' : wprmprc_public.settings.recipe_collections_recipe_click;
    if ( 'recipe' === item.type ) {
        allowDetailsClick = props.allowClick && 'disabled' !== clickSetting;

        switch ( clickSetting ) {
            case 'disabled':
                allowDetailsClick = false;
                break;
            case 'parent':
                if ( ! item.parent_url ) {
                    allowDetailsClick = false;
                }
                break;
        }
    }

    // Allow editing of custom recipes and notes.
    if ( ( 'ingredient' === item.type || 'note' === item.type ) && props.hasOwnProperty( 'onEditItem' ) ) {
        allowDetailsClick = true;
    }

    // Ingredient Text
    let ingredientText = false;
    if ( 'ingredient' === item.type ) {
        const ingredientsWithText = item.ingredients.filter((ingredient) => {
            return ingredient.amount || ingredient.unit || ingredient.name;
        });

        ingredientText = (
            <Fragment>
                <div className="wprmprc-collection-item-ingredients">
                    {
                        ingredientsWithText.map((ingredient, index) => {
                            let text = '';
                            if ( ingredient.amount ) { text += `${ ingredient.amount } `; }
                            if ( ingredient.unit ) { text += `${ ingredient.unit } `; }
                            if ( ingredient.name ) { text += `${ ingredient.name }`; }
                            
                            return (
                                <div className="wprmprc-collection-item-ingredients-line" key={ index }>{ text.trim() }</div>
                            )
                        })
                    }
                </div>
                {
                    item.hasOwnProperty( 'text' )
                    && item.text
                    &&
                    <Fragment>
                        {
                            0 < ingredientsWithText.length
                            &&
                            <br/>
                        }
                        <div className="wprmprc-collection-item-text">{ item.text }</div>
                    </Fragment>
                }
            </Fragment>
        );
    }

    // Nutrition fields to show.
    let nutrition = {};

    if ( props.showNutrition && wprmprc_public.settings.recipe_collections_nutrition_facts_highlight
        && 0 < wprmprc_public.settings.recipe_collections_nutrition_facts_highlight_fields.length ) {
        // Get values we want to show.
        const nutritionToShow = wprmprc_public.settings.recipe_collections_nutrition_facts_highlight_fields;

        if ( 0 < nutritionToShow.length ) {
            // Get recipe nutrition from recipes object.
            if ( 'recipe' === item.type && props.recipes.hasOwnProperty( item.recipeId ) && props.recipes[ item.recipeId ].hasOwnProperty( 'nutrition' ) ) {
                item.nutrition = props.recipes[ item.recipeId ].nutrition;
            }

            // Check if item has nutrition fields.
            if ( item.hasOwnProperty( 'nutrition' ) ) {
                for ( let nutrient of nutritionToShow ) {
                    if ( item.nutrition.hasOwnProperty( nutrient ) ) {
                        nutrition[ nutrient ] = item.nutrition[ nutrient ];

                        if ( 'total' === wprmprc_public.settings.recipe_collections_nutrition_facts_count ) {
                            const servings = item.hasOwnProperty( 'servings' ) ? item.servings : 1;
                            nutrition[ nutrient ] *= servings;
                            
                            nutrition[ nutrient ] = formatQuantity( nutrition[ nutrient ], wprmprc_public.settings.recipe_collections_nutrition_facts_round_to_decimals );
                        }

                        if ( 'serving_size' === nutrient ) {
                            nutrition['serving_unit'] = item.nutrition.hasOwnProperty( 'serving_unit' ) ? item.nutrition.serving_unit : '';
                        }
                    }
                }
            }
        }
    }

    // Ability to change servings.
    let changeServingsByOne = false;
    if ( wprmprc_public.settings.recipe_collections_adjustable_servings && props.hasOwnProperty( 'onChangeServings' ) ) {
        changeServingsByOne = (e, index, plus) => {
            e.preventDefault();

            const servings = plus ? item.servings + 1 : item.servings - 1;
            props.onChangeServings(index, servings);

            return false;
        }
    }

    // Item classes.
    let itemClass = 'wprmprc-collection-item';
    itemClass += ` wprmprc-collection-item-${wprmprc_public.settings.recipe_collections_recipe_style}`;
    itemClass += ` wprmprc-collection-item-${item.type}`;

    if ( 'recipe' === item.type ) {
        itemClass += ` wprmprc-collection-item-recipe-${item.recipeId}`;
    }
    
    if ( item.hasOwnProperty( 'color' ) ) {
        itemClass += ` wprmprc-collection-item-color-${item.color}`;
    }
    if ( item.hasOwnProperty( 'image' ) && item.image ) {
        itemClass += ` wprmprc-collection-item-has-image`;
    }

    // Need to add specific container attributes when draggable.
    let containerAttributes = {
        className: itemClass,
    };
    let isDraggable = false;
    if ( props.hasOwnProperty( 'draggable' ) && false !== props.draggable ) {
        isDraggable = true;
        containerAttributes = {
            ...containerAttributes,
            ref: props.draggable.innerRef,
            ...props.draggable.draggableProps,
        };
    }

    const childProps = {
        ...props,
        addInterface,
        clickSetting,
        allowDetailsClick,
        ingredientText,
        nutrition,
        changeServingsByOne,
        isDraggable,
        containerAttributes,
    }

    return (
        <Fragment>
            {
                'classic' === props.layout
                ?
                <ItemContentClassic {...childProps} />
                :
                <ItemContentGrid {...childProps} />
            }
        </Fragment>
    );
}
export default withRouter(ItemContent);