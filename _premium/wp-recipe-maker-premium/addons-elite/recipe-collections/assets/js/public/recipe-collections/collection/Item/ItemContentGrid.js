import React, { Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import QRCode from 'react-qr-code';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Api from '../../general/Api';
import ContextMenu from '../../general/ContextMenu';
import Icon from '../../general/Icon';
import { getInteractiveServingsUnit } from '../../general/ServingsUnit';

const ItemContentGrid = (props) => {
    const { item } = props;

    const servingsUnit = getInteractiveServingsUnit( item ) || __wprm( 'servings' );

    // View Recipe.
    const viewRecipe = (e) => {
        if ( 'recipe' === item.type && props.allowDetailsClick ) {
            switch ( props.clickSetting ) {
                case 'recipe':
                    if ( 'inbox' === props.type ) {
                        props.history.push(`/collection/inbox/${item.recipeId}/${item.servings}`);
                    } else if ( 'shared' === props.type ) {
                        props.history.push(`/share/${props.collection.sharedEncoded}/${item.recipeId}/${item.servings}`);
                    } else {
                        props.history.push(`/collection/${props.type}/${props.collection.id}/${item.recipeId}/${item.servings}`);
                    }
                    break;
                case 'parent':
                    if ( 'admin' === props.type || e.metaKey || wprmprc_public.settings.recipe_collections_recipe_click_new_tab ) {
                        window.open( item.parent_url );
                    } else {
                        location.href = item.parent_url;
                    }
                    break;
            }
        }
    }

    // Get Item Actions.
    let itemActions = [];
    const isFixed = props.collection && props.collection.fixed && 'admin' !== props.type;

    if ( 'recipe' === item.type && props.allowDetailsClick ) {
        itemActions.push( {
            label: __wprm( 'View Recipe' ),
            action: () => viewRecipe({}),
        } );
    }

    // Don't show edit/delete actions for fixed collections (except in admin mode).
    if ( ! isFixed ) {
        if ( 'ingredient' === item.type || 'note' === item.type ) {
            itemActions.push( {
                label: 'ingredient' === item.type ? __wprm( 'Edit Custom Recipe' ) : __wprm( 'Edit Note' ),
                action: () => props.onEditItem(),
            } );
        }

        itemActions.push( {
            label: __wprm( 'Duplicate Item' ),
            action: () => props.onDuplicateItem(),
        } );
    }

    // Allow serving size changes even for fixed collections (user preference).
    if ( 'note' !== item.type && item.hasOwnProperty( 'servings' ) && false !== props.changeServingsByOne ) {
        itemActions.push( {
            label: __wprm( 'Change Servings' ),
            action: () => {
                let servings = prompt( __wprm( 'Set the number of servings' ), item.servings );
                if( servings && servings.trim() ) {
                    props.onChangeServings( props.index, parseFloat( servings ) );
                }
            },
        } );

        if ( wprmprc_public.settings.recipe_collections_items_leftovers && ! isFixed ) {
            const isLeftovers = item.hasOwnProperty( 'leftovers' ) && item.leftovers;

            itemActions.push( {
                label: isLeftovers ? __wprm( 'Do not mark as leftovers' ) : __wprm( 'Mark as leftovers' ),
                action: () => {
                    props.onChangeLeftovers( props.index, ! isLeftovers );
                },
            } );
        }
    }

    if ( ! isFixed ) {
        if ( 0 < itemActions.length ) {
            itemActions.push( {
                divider: true,
            } );
        }

        itemActions.push( {
            label: __wprm( 'Remove Item' ),
            action: () => props.onDeleteItem( item.id, props.index ),
        } );
    }

    if ( 'admin' === props.type && 'recipe' === item.type ) {
        let url = item.parent_url;

        itemActions.push(
            {
                divider: true,
            },{
                label: __wprm( 'View Recipe' ),
                disabled: ! url,
                action: () => {
                    if ( url ) {
                        // Open url in new tab.
                        window.open( url, '_blank' );
                    }
                },
            },{
                label: __wprm( 'Edit Recipe' ),
                action: () => {
                    WPRM_Modal.open( 'recipe', {
                        recipeId: item.recipeId,
                        saveCallback: ( recipe ) => {
                            Api.getRecipeData( recipe.id ).then( (data) => {
                                if ( data ) {
                                    if ( data.image !== item.image || data.name !== item.name ) {
                                        alert( __wprm( 'Make sure to "Reload Recipes in Collection" after saving the collection to see these changes reflected.' ) );
                                    }
                                }
                            } );
                        },
                    } );
                },
            }
        );
    }

    // Maybe show QR code.
    const showQR = 'recipe' === item.type && item.parent_url && props.hasOwnProperty( 'showQR' ) && props.showQR;

    // Parse urls as links.
    const outputWithLinks = ( text ) => {
        let html = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        const urlRegex = /(https?:\/\/[^\s]+)/g;
        html = html.replace(urlRegex, (url) => '<a href="' + url + '" target="_blank">' + url + '</a>' );

        return html;
    }

    return (
        <div { ...props.containerAttributes }>
            {
                item.image
                && 'top' === wprmprc_public.settings.recipe_collections_grid_item_image_position
                &&
                <div
                    className={`wprmprc-collection-item-image wprmprc-collection-item-image-top${ 'recipe' === item.type && props.allowDetailsClick ? ' wprmprc-collection-item-image-allow-click' : ''}`}
                    onClick={ (e) => viewRecipe(e) }
                >
                    <img src={item.image} alt={ item.name } />
                </div>
            }
            <div className="wprmprc-collection-item-main">
                {
                    'saved' !== props.type
                    && 'shared' !== props.type
                    &&
                    <div className="wprmprc-collection-item-actions">
                        {
                            'click' === props.addInterface
                            ?
                            <Button
                                className="wprmprc-collection-item-action wprmprc-collection-item-action-add"
                                onClick={() => {
                                    if ( props.onAddItem ) {
                                        props.onAddItem( item );
                                    }
                                }}
                                isButton={ props.onAddItem }
                                tabIndex="-1"
                                aria-label={ __wprm( 'Add item to this collection group' ) }
                            ><Icon type="plus-alt" title={ __wprm( 'Add Item' ) }/></Button>
                            :
                            <Fragment>
                                {
                                    'drag' === props.addInterface && props.isDraggable
                                    &&
                                    <div
                                        className="wprmprc-collection-item-action wprmprc-collection-item-action-order"
                                        { ...props.draggable.dragHandleProps }
                                    ><Icon type="drag" /></div>
                                }
                                <div className="wprmprc-collection-item-action">
                                    <ContextMenu
                                        menu={ itemActions }
                                    />
                                </div>
                            </Fragment>
                        }
                    </div>
                }
                <div
                    className={`wprmprc-collection-item-details${ 'recipe' === item.type && props.allowDetailsClick ? ' wprmprc-collection-item-details-allow-click' : ''}`}
                    onClick={ (e) => viewRecipe(e) }
                >
                    <div className="wprmprc-collection-item-meta">
                        <div className="wprmprc-collection-item-name">
                            {
                                'ingredient' === item.type
                                && false !== props.ingredientText
                                ?
                                <Fragment>
                                    {
                                        '' !== item.name
                                        &&
                                        <Fragment>
                                            <strong>{ item.name }</strong><br/>
                                        </Fragment>
                                    }
                                    { props.ingredientText }
                                </Fragment>
                                :
                                <Fragment>
                                    {
                                        'nutrition-ingredient' === item.type
                                        ?
                                        <Fragment>
                                            {
                                                props.hasOwnProperty( 'onChangeAmount' )
                                                ?
                                                <Button
                                                    tag="a"
                                                    href="#"
                                                    onClick={ (e) => {
                                                        e.preventDefault();
                                                        let amount = prompt( __wprm( 'Set a new amount for this ingredient:' ), item.amount ).trim();
                                                        if( amount ) {
                                                            props.onChangeAmount( props.index, parseFloat( amount ) );
                                                        }
                                                    }}
                                                    aria-label={ __wprm( 'Change ingredient amount' ) }
                                                >{ `${item.amount}${ item.unit ? ` ${ item.unit }` : '' }` }</Button>
                                                :
                                                <Fragment>{ `${item.amount}${ item.unit ? ` ${ item.unit }` : '' }` }</Fragment>
                                            } { item.name }
                                        </Fragment>
                                        :
                                        <Fragment>
                                            {
                                                'note' === item.type
                                                ?
                                                <div dangerouslySetInnerHTML={{
                                                    __html: outputWithLinks( item.name )
                                                }}/>
                                                :
                                                item.name
                                            }
                                        </Fragment>
                                    }
                                </Fragment>
                            }
                            {
                                showQR
                                &&
                                <div className="wprmprc-collection-item-qr">
                                    <QRCode
                                        value={ item.parent_url }
                                        style={{ height: "auto", maxWidth: "100%" }}
                                        size={ 64 }
                                    />
                                </div>
                            }
                        </div>
                        {
                            0 < Object.keys( props.nutrition ).length
                            &&
                            <div className="wprmprc-collection-item-nutrition">
                                {
                                    Object.keys( wprmprc_public.labels.nutrition_fields ).map((nutrient) => {
                                        if ( props.nutrition.hasOwnProperty( nutrient ) ) {
                                            const options = wprmprc_public.labels.nutrition_fields[nutrient];
                                            const value = props.nutrition[ nutrient ];
                                            let unit = options.unit;

                                            if ( 'serving_size' === nutrient && props.nutrition.hasOwnProperty( 'serving_unit' ) && props.nutrition.serving_unit ) {
                                                unit = props.nutrition.serving_unit;
                                            }

                                            return (
                                                <div
                                                    className="wprmprc-collection-item-nutrition-field"
                                                    key={ nutrient }
                                                >
                                                    <div className="wprmprc-collection-item-nutrition-field-label">{ options.label }</div>
                                                    <div className="wprmprc-collection-item-nutrition-field-value-container">
                                                        <span className="wprmprc-collection-item-nutrition-field-value">{ value }</span>
                                                        <span className="wprmprc-collection-item-nutrition-field-unit">{ unit }</span>
                                                    </div>
                                                </div>
                                            )
                                        }
                                    })
                                }
                            </div>
                        }
                    </div>
                    {
                        item.image
                        && 'side' === wprmprc_public.settings.recipe_collections_grid_item_image_position
                        &&
                        <div className="wprmprc-collection-item-image wprmprc-collection-item-image-side">
                            <img src={item.image} alt={ item.name } />
                        </div>
                    }
                </div>
            </div>
            {
                'note' !== item.type
                && item.hasOwnProperty( 'servings' )
                &&
                <div className="wprmprc-collection-item-servings">
                    {
                        wprmprc_public.settings.recipe_collections_items_leftovers
                        && item.hasOwnProperty( 'leftovers' )
                        && item.leftovers
                        ?
                        <div className="wprmprc-collection-item-leftovers">{ __wprm( 'Leftovers' ) }</div>
                        :
                        <Fragment>
                            {
                                false !== props.changeServingsByOne
                                ?
                                <Fragment>
                                    <Button
                                        className="wprmprc-collection-item-servings-minus"
                                        onClick={(e) => props.changeServingsByOne(e, props.index, false)}
                                        aria-label={ __wprm( 'Decrease serving size by one' ) }
                                    >
                                        <Icon type="minus-alt"/>
                                    </Button>
                                    <Button
                                        className="wprmprc-collection-item-servings-value wprmprc-collection-item-servings-value-click"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            let servings = prompt( __wprm( 'Set the number of servings' ), item.servings );
                                            if( servings && servings.trim() ) {
                                                props.onChangeServings( props.index, parseFloat( servings ) );
                                            }
                                        }}
                                        aria-label={ __wprm( 'Set serving size' ) }
                                    >{ item.servings } <span className="wprmprc-collection-item-servings-unit">{ servingsUnit }</span></Button>
                                    <Button
                                        className="wprmprc-collection-item-servings-plus"
                                        onClick={(e) => props.changeServingsByOne(e, props.index, true)}
                                        aria-label={ __wprm( 'Increase serving size by one' ) }
                                    >
                                        <Icon type="plus-alt" />
                                    </Button>
                                </Fragment>
                                :
                                <div className="wprmprc-collection-item-servings-value">{ item.servings } <span className="wprmprc-collection-item-servings-unit">{ servingsUnit }</span></div>
                            }
                        </Fragment>
                    }
                </div>
            }
        </div>
    );
}
export default withRouter(ItemContentGrid);
