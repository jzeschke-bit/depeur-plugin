import React, { Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import Button from '../../../shared/Button';
import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';

const GridCollection = (props) => {
    const { collection } = props;

    // Helpers.
    const draggable = props.hasOwnProperty( 'draggable' ) && false !== props.draggable;
    const clickable = props.editing || ! props.hasOwnProperty( 'onClick' ) ? false : true;

    // Collection images.
    let images = [];
    const collectionItems = collection.hasOwnProperty( 'items' ) ? collection.items : [];

    imagesLoop:
    for ( let columnGroup of Object.keys( collectionItems ) ) {
        const items = collectionItems[ columnGroup ];

        for ( let item of items ) {
            if ( item.hasOwnProperty( 'image' ) && item.image ) {
                images.push( item.image );

                if ( 10 <= images.length ) {
                    break imagesLoop;
                }
            }
        }
    }

    // Actions.
    let actions = [];
    const currentDescription = collection.hasOwnProperty( 'description' ) ? collection.description : '';

    // Don't allow editing fixed collections.
    if ( ! collection.fixed ) {
        actions.push(
            {
                label: __wprm( 'Change Name' ),
                action: () => props.onEditing( true ),
            },
            {
                label: '' !== currentDescription ? __wprm( 'Change Description' ) : __wprm( 'Set Description' ),
                action: () => {
                    const description = prompt( __wprm( 'Description for this collection:' ), currentDescription );
                    if ( null !== description ) {
                        props.onChange( { description } );
                    }
                },
            }
        );
    }
    if ( props.hasOwnProperty( 'onDuplicate' ) && false !== props.onDuplicate && ! collection.fixed ) {
        actions.push(
            {
                label: __wprm( 'Duplicate' ),
                action: () => props.onDuplicate(),
            }
        );
    }
    if ( props.hasOwnProperty( 'onDelete' ) && false !== props.onDelete && ! collection.fixed ) {
        actions.push(
            {
                divider: true,
            },
            {
                label: __wprm( 'Delete' ),
                confirm: __wprm( 'Are you sure you want to delete?' ),
                action: () => props.onDelete(),
            }
        );
    }

    // Container.
    let containerProps = {
        className: `wprmprc-overview-grid-collection${ props.editing ? ' wprmprc-overview-grid-collection-editing' : '' }${ clickable ? ' wprmprc-overview-grid-collection-clickable' : '' }`,
    }

    if ( draggable ) {
        containerProps = {
            ...containerProps,
            ref: props.innerRef,
            ...props.draggable.draggableProps,
        }
    }

    return (
        <div
            ref={ draggable.innerRef } { ...containerProps }
            onClick={ () => {
                if ( props.editing ) {
                    props.onEditing( false );
                }
            } }
        >
            {
                draggable
                ?
                <div className="wprmprc-overview-grid-collection-handle" { ...props.draggable.dragHandleProps }><Icon type="drag" /></div>
                :
                <div className="wprmprc-overview-grid-collection-nohandle"/>
            }
            <Button
                className="wprmprc-overview-grid-collection-name"
                onClick={ () => {
                    if ( clickable ) {
                        props.onClick();
                    }
                } }
                isButton={ clickable }
                aria-label={ __wprm( 'Change collection name' ) }
            >
                {
                    props.editing && ! collection.fixed
                    ?
                    <input
                        type="text"
                        value={ collection.name }
                        onClick={ (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                        } }
                        onChange={ (e) => {
                            props.onChange( { name: e.target.value } );
                        } }
                        onKeyDown={ (e) => {
                            if( 'Enter' === e.key || 'Escape' === e.key || 'Tab' === e.key ) {
                                props.onEditing( false );
                            }
                        } }
                        autoFocus
                    />
                    :
                    <Fragment>
                        { collection.name }
                        {
                            collection.hasOwnProperty( 'description' )
                            && '' !== collection.description
                            &&
                            <div
                                onClick={ (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                }}
                            >
                                <Icon type="info" title={ collection.description } />
                            </div>
                        }
                    </Fragment>
                }
            </Button>
            <div
                className="wprmprc-overview-grid-collection-images-container"
                onClick={ () => {
                    if ( clickable ) {
                        props.onClick();
                    }
                } }
            >
                <div className="wprmprc-overview-grid-collection-images">
                    {
                        images.map( ( img, index ) => (
                            <div className="wprmprc-overview-grid-collection-image" key={ index }>
                                <img src={ img } />
                            </div>
                        ))
                    }
                </div>
                <div className="wprmprc-overview-grid-collection-count">{ collection.nbrItems }</div>
            </div>
            {
                wprmprc_public.settings.recipe_collections_shopping_list
                && wprmprc_public.settings.recipe_collections_shopping_list_shortcut
                &&
                <div
                    className="wprmprc-overview-grid-collection-actions-shopping-list"
                    onClick={ props.onOpenShoppingList }
                >
                   <Icon type="cart" title={ __wprm( 'Go to Shopping List' ) } />
                </div>
            }
            <div className="wprmprc-overview-grid-collection-actions">
                <ContextMenu
                    menu={ actions }
                />
            </div>
        </div>
    );
}
export default GridCollection;