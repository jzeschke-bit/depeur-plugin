import React, { Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Icon from '../../general/Icon';
import { getInteractiveServingsUnit } from '../../general/ServingsUnit';

const Item = (props) => {
    const { item, allowChange, onChangeServings } = props;
    const servingsUnit = getInteractiveServingsUnit( item );

    const changeServings = (e, plus) => {
        e.preventDefault();

        const servings = plus ? item.servings + 1 : item.servings - 1;
        onChangeServings(servings);

        return false;
    }

    // Item classes.
    let itemClass = 'wprmprc-shopping-list-item';
    itemClass += ` wprmprc-shopping-list-item-${item.type}`;
    
    if ( item.servings <= 0 ) {
        itemClass += ' wprmprc-shopping-list-item-unused';
    }

    if ( item.hasOwnProperty( 'color' ) ) {
        itemClass += ` wprmprc-shopping-list-item-color-${item.color}`;
    }

    // Item text.
    let itemText = item.name;
    if ( ! itemText && 'ingredient' === item.type ) {
        itemText = '';
        item.ingredients.map((ingredient, index) => {
            if ( 0 < index ) { itemText += '\n'; }
            if ( ingredient.amount ) { itemText += `${ ingredient.amount } `; }
            if ( ingredient.unit ) { itemText += `${ ingredient.unit } `; }
            if ( ingredient.name ) { itemText += `${ ingredient.name }`; }

            itemText = itemText.trim();
        });
    } else if ( 'nutrition-ingredient' === item.type ) {
        const prefix = `${item.amount} ${item.unit}`.trim();
        itemText = `${prefix} ${itemText}`; 
    }

    // Clickable.
    let allowClick = false;
    switch ( wprmprc_public.settings.recipe_collections_recipe_click ) {
        case 'recipe':
            if ( 'recipe' === item.type && false !== props.shoppingList ) {
                allowClick = true;
            }
            break;
        case 'parent':
            if ( item.parent_url ) {
                allowClick = true;
            }
            break;
    }


    return (
        <div className={ itemClass }>
            {
                'note' !== item.type
                &&
                <Fragment>
                    <div className="wprmprc-shopping-list-item-servings-adjust">
                        {
                            wprmprc_public.settings.recipe_collections_items_leftovers
                            && item.hasOwnProperty( 'leftovers' )
                            && item.leftovers
                            ?
                            <div className="wprmprc-shopping-list-item-servings-adjust-leftovers">
                                { __wprm( 'Leftovers' ) }
                            </div>
                            :
                            <Fragment>
                                {
                                    allowChange
                                    && wprmprc_public.settings.recipe_collections_adjustable_servings
                                    ?
                                    <Fragment>
                                        <Button
                                            className="wprmprc-shopping-list-item-servings-adjust-minus"
                                            onClick={(e) => changeServings(e, false)}
                                            aria-label={ __wprm( 'Decrease serving size by one' ) }
                                        >
                                            <Icon type="minus" />
                                        </Button>
                                        <div className="wprmprc-shopping-list-item-servings-adjust-servings-container">
                                            <Button
                                                className="wprmprc-shopping-list-item-servings-adjust-servings wprmprc-shopping-list-item-servings-adjust-servings-adjustable"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    let servings = prompt( __wprm( 'Set the number of servings' ), item.servings ).trim();
                                                    if( servings ) {
                                                        onChangeServings( parseFloat( servings ) );
                                                    }
                                                }}
                                                aria-label={ __wprm( 'Set serving size' ) }
                                            >{item.servings}</Button>
                                            {
                                                servingsUnit
                                                && <div className="wprmprc-shopping-list-item-servings-adjust-servings-unit">{servingsUnit}</div>
                                            }
                                        </div>
                                        <Button
                                            className="wprmprc-shopping-list-item-servings-adjust-plus"
                                            onClick={(e) => changeServings(e, true)}
                                            aria-label={ __wprm( 'Increase serving size by one' ) }
                                        >
                                            <Icon type="plus" />
                                        </Button>
                                    </Fragment>
                                    :
                                    <div className="wprmprc-shopping-list-item-servings-adjust-servings-container">
                                        <div
                                            className="wprmprc-shopping-list-item-servings-adjust-servings"
                                        >{item.servings}</div>
                                        {
                                            servingsUnit
                                            && <div className="wprmprc-shopping-list-item-servings-adjust-servings-unit">{servingsUnit}</div>
                                        }
                                    </div>
                                }
                            </Fragment>
                        }
                    </div>
                </Fragment>
            }
            <div className="wprmprc-shopping-list-item-details">
                <div
                    className={ `wprmprc-shopping-list-item-name${ allowClick ? ' wprmprc-shopping-list-item-name-allow-click' : ''}` }
                    onClick={(e) => {
                        if ( 'recipe' === item.type && allowClick ) {
                            switch ( wprmprc_public.settings.recipe_collections_recipe_click ) {
                                case 'recipe':
                                    if ( 'shortcode' === props.type || 'temp' === props.type ) {
                                        props.history.push(`/recipe/${item.recipeId}/${item.servings}`);
                                    } else if ( false !== props.shoppingList ) {
                                        props.history.push(`/shopping-list/${props.shoppingList}/recipe/${item.recipeId}/${item.servings}`);
                                    }
                                    break;
                                case 'parent':
                                    if ( e.metaKey || wprmprc_public.settings.recipe_collections_recipe_click_new_tab ) {
                                        window.open( item.parent_url );
                                    } else {
                                        location.href = item.parent_url;
                                    }
                                    break;
                            }
                        }
                    }}
                >{ itemText }</div>
                {
                    allowChange
                    && 'temp' === props.type
                    &&
                    <div className="wprmprc-shopping-list-item-remove">
                        <a
                            href="#"
                            onClick={ (e) => {
                                e.preventDefault();
                                props.onDeleteItem();
                            }}
                        >{ __wprm( 'remove' ) }</a>
                    </div>
                }
                {
                    item.image
                    &&
                    <div className="wprmprc-shopping-list-item-image">
                        <img className="wprmprc-shopping-list-item-image" width="50" src={item.image} alt={ item.name } />
                    </div>
                }
            </div>
        </div>
    );
}
export default withRouter(Item);
