import React, { Fragment } from 'react';
import Hashids from 'hashids'

import Button from '../../../shared/Button';
import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';
import SaveCollection from './ActionsClassic/SaveCollection';

import { __wprm } from 'Shared/Translations';

const GridHeaderActions = (props) => {
    const allowPrintCollection = wprmprc_public.settings.recipe_collections_print;
    const allowPrintRecipes = wprmprc_public.settings.recipe_collections_print_recipes;

    const hasPrintOptions = allowPrintCollection || allowPrintRecipes;
    const morePrintOptions = allowPrintCollection && allowPrintRecipes;

    // Can only share collection if setting enabled and current user is logged in.
    const canShareCollection = wprmprc_public.settings.recipe_collections_share_collection && 0 < wprmp_public.user;
    const collectionIsShared = props.collection.hasOwnProperty( 'shared' ) && props.collection.shared;
    let collectionShareLink = '';

    if ( canShareCollection && collectionIsShared ) {
        const hashids = new Hashids( 'wp-recipe-maker' );

        let encoded;
        if ( 'inbox' === props.type ) {
            encoded = hashids.encode( [ wprmp_public.user ] );
        } else {
            encoded = hashids.encode( [ wprmp_public.user, props.collection.id ] );
        }
            
        // Construct share link.
        if ( encoded ) {
            const parts = window.location.href.split( '#' );
            collectionShareLink = `${ parts[0] }#share/${ encoded }`;
        }
    }

    const currentDescription = props.collection.hasOwnProperty( 'description' ) ? props.collection.description : '';

    return (
        <div className="wprmprc-container-header-actions">
            {
                wprmprc_public.settings.recipe_collections_shopping_list
                &&
                'admin' !== props.type
                &&
                <Button
                    className="wprmprc-container-header-action wprmprc-container-header-action-shopping-list"
                    onClick={() => {
                        props.openShoppingList();
                    }}
                    tabIndex="-1"
                    aria-label={ __wprm( 'Open the shopping list' ) }
                ><Icon type="cart" title={ __wprm( 'Shopping List' ) } /></Button>
            }
            {
                hasPrintOptions
                &&
                <Button
                    className="wprmprc-container-header-action wprmprc-container-header-action-print-collection"
                    onClick={() => {
                        if ( ! morePrintOptions ) {
                            if ( allowPrintCollection ) {
                                props.onPrintCollection();
                            } else {
                                props.onPrintRecipes();
                            }
                        }
                    }}
                    isButton={ ! morePrintOptions }
                    aria-label={ allowPrintCollection ? __wprm( 'Print this collection' ) : __wprm( 'Print recipes in this collection' ) }
                    tabIndex="-1"
                >
                    {
                        morePrintOptions
                        ?
                        <ContextMenu
                            icon="printer"
                            title={ __wprm( 'Print' ) }
                            menu={ [
                                {
                                    label: __wprm( 'Print Collection' ),
                                    action: () => {
                                        props.onPrintCollection();
                                    }
                                },
                                {
                                    label: __wprm( 'Print Recipes' ),
                                    action: () => {
                                        props.onPrintRecipes();
                                    }
                                }
                            ] }
                        />
                        :
                        <Icon type="printer" title={ allowPrintCollection ? __wprm( 'Print Collection' ) : __wprm( 'Print Recipes' )  } />
                    }
                </Button>
            }
            {
                wprmprc_public.settings.recipe_collections_nutrition_facts
                &&
                <Button
                    className="wprmprc-container-header-action wprmprc-container-header-action-nutrition"
                    onClick={() => {
                        props.onChangeShowNutrition( ! props.showNutrition );
                    }}
                    tabIndex="-1"
                    aria-label={ props.showNutrition ? __wprm( 'Hide Nutrition Facts' ) : __wprm( 'Show Nutrition Facts' ) }
                >
                    {
                        props.showNutrition
                        ?
                        <Icon type="no-nutrition" title={ __wprm( 'Hide Nutrition Facts' ) } />
                        :
                        <Icon type="nutrition" title={ __wprm( 'Show Nutrition Facts' ) } />
                    }
                </Button>
            }
            {
                ( 'saved' === props.type || 'shared' === props.type )
                &&
                <SaveCollection
                    layout="grid"
                    type={props.type}
                    collection={props.collection}
                />
            }
            {
                'saved' !== props.type
                && 'shared' !== props.type
                &&
                <Fragment>
                    {
                        canShareCollection
                        &&
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-share-collection"
                            isButton={ false }
                            aria-label={ __wprm( 'Share This Collection' ) }
                            tabIndex="-1"
                        >
                            <ContextMenu
                                icon={ collectionIsShared ? 'cloud-upload-alt' : 'cloud-upload' }
                                title={ collectionIsShared ? __wprm( 'Shared Collection' ) : __wprm( 'Share this Collection' ) }
                                menu={ [
                                    {
                                        label: __wprm( 'Copy Share Link' ),
                                        disabled: ! collectionIsShared,
                                        copyToClipboard: {
                                            text: collectionShareLink,
                                            message: __wprm( 'The link copied to your clipboard will allow others to access (but not edit) this collection.' ),
                                        },
                                    },
                                    {
                                        label: collectionIsShared ? __wprm( 'Stop Sharing Collection' ) : __wprm( 'Start Sharing Collection' ),
                                        action: () => {
                                            props.onChangeCollection( { shared: ! collectionIsShared } );
                                        },
                                        closeOnAction: collectionIsShared,
                                    }
                                ] }
                            />
                        </Button>
                    }
                    {
                        ( ! props.collection.fixed || 'admin' === props.type )
                        && wprmprc_public.settings.recipe_collections_history
                        &&
                        (
                            <Button
                                className="wprmprc-container-header-action wprmprc-container-header-action-history"
                                isButton={ false }
                                aria-label={ __wprm( 'Undo/Redo History' ) }
                                tabIndex="-1"
                            >
                                <ContextMenu
                                    icon="history"
                                    title={ __wprm( 'Undo/Redo History' ) }
                                    menu={ [
                                        {
                                            label: __wprm( 'Undo' ),
                                            disabled: props.historyCursor < 0,
                                            action: props.onHistoryUndo,
                                        },
                                        {
                                            label: __wprm( 'Redo' ),
                                            disabled: props.historyCursor >= props.historyEntries.length - 1,
                                            action: props.onHistoryRedo,
                                        },
                                        {
                                            label: `${ __wprm( 'Show History' ) } (${ props.historyEntries.length })`,
                                            action: props.onShowHistory,
                                        },
                                    ] }
                                />
                            </Button>
                        )
                    }
                    {
                        ( ! props.collection.fixed || 'admin' === props.type )
                        &&
                        (
                            'modal' === wprmprc_public.settings.recipe_collections_appearance_structure_layout
                            ?
                            <Button
                                className="wprmprc-container-header-action wprmprc-container-header-action-structure"
                                onClick={() => {
                                    props.onEditStructure();
                                }}
                                tabIndex="-1"
                                aria-label={ __wprm( 'Change Collection Structure' ) }
                            >
                                <Icon type="grid" title={ __wprm( 'Change Collection Structure' ) } />
                            </Button>
                            :
                            <Fragment>
                                <Button
                                    className="wprmprc-container-header-action wprmprc-container-header-action-clear"
                                    onClick={() => {
                                        if ( confirm( __wprm( 'Are you sure you want to remove all items from this collection?' ) ) ) {
                                            props.onChangeCollection( { items: {} } );
                                        }
                                    }}
                                    tabIndex="-1"
                                    aria-label={ __wprm( 'Clear all items in this collection' ) }
                                >
                                    <Icon type="clear" title={ __wprm( 'Clear Items' ) } />
                                </Button>
                                <Button
                                    className="wprmprc-container-header-action wprmprc-container-header-action-description"
                                    onClick={() => {
                                        const description = prompt( __wprm( 'Description for this collection:' ), currentDescription );
                                        if ( null !== description ) {
                                            props.onChangeCollection( { description } );
                                        }
                                    }}
                                    tabIndex="-1"
                                    aria-label={ currentDescription ? __wprm( 'Change the description for this collection' ) : __wprm( 'Set a description for this collection' ) }
                                >
                                    <Icon type="info" title={ currentDescription ? __wprm( 'Change Description' ) : __wprm( 'Set Description' ) } />
                                </Button>
                            </Fragment>
                        )
                    }
                </Fragment>
            }
        </div>
    );
}

export default GridHeaderActions;
