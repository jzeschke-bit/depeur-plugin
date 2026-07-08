import React, { Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Icon from '../../general/Icon';

const ItemContentClassic = (props) => {
    const { item } = props;

    return (
        <div { ...props.containerAttributes }>
            {
                'saved' !== props.type
                && 'shared' !== props.type
                && ( ! props.collection.fixed || 'admin' === props.type )
                &&
                <div className="wprmprc-collection-item-actions">
                    {
                        'click' !== props.addInterface
                        && props.isDraggable
                        &&
                        <div
                            className="wprmprc-collection-item-action wprmprc-collection-item-action-order"
                            { ...props.draggable.dragHandleProps }
                        ><Icon type="drag" /></div>
                    }
                    {
                        'click' === props.addInterface
                        &&
                        <Button
                            className="wprmprc-collection-item-action wprmprc-collection-item-action-add"
                            onClick={() => {
                                if ( props.onAddItem ) {
                                    props.onAddItem( item );
                                }
                            }}
                        ><Icon type="plus" /></Button>
                    }
                    {
                        props.onDeleteItem
                        &&
                        <Button
                            className="wprmprc-collection-item-action wprmprc-collection-item-action-delete"
                            onClick={() => props.onDeleteItem(item.id, props.index)}
                        ><Icon type="delete" /></Button>
                    }
                </div>
            }
            <div
                className={`wprmprc-collection-item-details${ props.allowDetailsClick ? ' wprmprc-collection-item-details-allow-click' : ''}`}
                onClick={(e) => {
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
                    if ( ( 'ingredient' === item.type || 'note' === item.type ) && props.allowDetailsClick ) {
                        props.onEditItem();
                    }
                }}
            >
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
                                        props.hasOwnProperty( 'onChangeAmount' ) && ( ! props.collection.fixed || 'admin' === props.type )
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
                                item.name
                            }
                        </Fragment>
                    }
                </div>
                {
                    item.image
                    &&
                    <div className="wprmprc-collection-item-image">
                        <img src={item.image} alt={ item.name } />
                    </div>
                }
            </div>
            {
                'note' !== item.type
                && item.hasOwnProperty( 'servings' )
                &&
                <div className="wprmprc-collection-item-servings">
                    {
                        false !== props.changeServingsByOne
                        ?
                        <Fragment>
                            <div className="wprmprc-collection-item-servings-minus" onClick={(e) => props.changeServingsByOne(e, props.index, false)}><Icon type="minus" /></div>
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
                            >{ item.servings }</Button>
                            <div className="wprmprc-collection-item-servings-plus" onClick={(e) => props.changeServingsByOne(e, props.index, true)}><Icon type="plus" /></div>
                        </Fragment>
                        :
                        <div className="wprmprc-collection-item-servings-value">{item.servings}</div>
                    }
                </div>
            }
        </div>
    );
}
export default withRouter(ItemContentClassic);