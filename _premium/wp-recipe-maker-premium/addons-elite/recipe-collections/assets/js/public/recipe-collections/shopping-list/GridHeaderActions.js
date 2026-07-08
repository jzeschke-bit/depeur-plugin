import React, { Fragment } from 'react';
import CopyToClipboard from 'react-copy-to-clipboard';

import Button from '../../../shared/Button';
import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';

import { __wprm } from 'Shared/Translations';

const GridHeaderActions = (props) => {
    const allowPrintShoppingList = wprmprc_public.settings.recipe_collections_shopping_list_print;
    const allowPrintRecipes = wprmprc_public.settings.recipe_collections_shopping_list_print_recipes;

    const hasPrintOptions = allowPrintShoppingList || allowPrintRecipes;
    const morePrintOptions = allowPrintShoppingList && allowPrintRecipes;

    return (
        <div className="wprmprc-container-header-actions">
            {
                props.hasShoppingList
                ?
                <Fragment>
                    <Button
                        className="wprmprc-container-header-action wprmprc-container-header-action-regenerate-shopping-list"
                        onClick={ props.onRegenerateShoppingList }
                        tabIndex="-1"
                        aria-label={ __wprm( 'Regenerate this shopping list' ) }
                    ><Icon type="cart-refresh" title={ __wprm( 'Regenerate Shopping List' ) } /></Button>
                    {
                        hasPrintOptions
                        &&
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-print-shopping-list"
                            onClick={() => {
                                if ( ! morePrintOptions ) {
                                    if ( allowPrintShoppingList ) {
                                        props.onPrintShoppingList();
                                    } else {
                                        props.onPrintRecipes();
                                    }
                                }
                            }}
                            isButton={ ! morePrintOptions }
                            tabIndex="-1"
                            aria-label={ allowPrintShoppingList ? __wprm( 'Print this shpopping list' ) : __wprm( 'Print recipes in this shopping list' ) }
                        >
                            {
                                morePrintOptions
                                ?
                                <ContextMenu
                                    icon="printer"
                                    title={ __wprm( 'Print' ) }
                                    menu={ [
                                        {
                                            label: __wprm( 'Print Shopping List' ),
                                            action: () => {
                                                props.onPrintShoppingList();
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
                                <Icon type="printer" title={ allowPrintShoppingList ? __wprm( 'Print Shopping List' ) : __wprm( 'Print Recipes' )  } />
                            }
                        </Button>
                    }
                    {
                        wprmprc_public.settings.recipe_collections_shopping_list_share
                        &&
                        <CopyToClipboard
                            text={ props.shareLink }
                            onCopy={(text, result) => {
                                if ( result ) {
                                    alert( __wprm( 'The link copied to your clipboard will allow others to edit this shopping list.' ) );
                                } else {
                                    prompt( __wprm( 'Copy this link to allow others to edit this shopping list:' ), props.shareLink );
                                }
                            }}
                        >
                            <div className="wprmprc-container-header-action wprmprc-container-header-action-share-shopping-list">
                                <Icon type="link" title={ __wprm( 'Share Edit Link' ) } />
                            </div>
                        </CopyToClipboard>
                    }
                    {
                        props.editing
                        ?
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-edit-shopping-list-stop"
                            onClick={ () => props.onChangeEditing( false ) }
                            tabIndex="-1"
                            aria-label={ __wprm( 'Stop editing this shopping list' ) }
                        ><Icon type="check" title={ __wprm( 'Stop Editing' ) } /></Button>
                        :
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-edit-shopping-list"
                            onClick={ () => props.onChangeEditing( true ) }
                            tabIndex="-1"
                            aria-label={ __wprm( 'Start editing this shopping list' ) }
                        ><Icon type="edit" title={ __wprm( 'Edit Shopping List' ) } /></Button>
                    }
                    {
                        wprmprc_public.settings.integration_instacart_shopping_list
                        &&
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-shop"
                            onClick={ props.onShopList }
                            disabled={ props.shopping }
                            tabIndex="-1"
                            aria-label={ __wprm( 'Shop this list with Instacart' ) }
                        ><Icon type="basket-add" title={ __wprm( 'Shop with Instacart' ) } /></Button>
                    }
                </Fragment>
                :
                <Fragment>
                    <Button
                        className="wprmprc-container-header-action wprmprc-container-header-action-generate-shopping-list"
                        onClick={ props.onGenerateShoppingList }
                        tabIndex="-1"
                        aria-label={ __wprm( 'Generate a shopping list for these recipes' ) }
                    ><Icon type="cart" title={ __wprm( 'Generate Shopping List' ) } /></Button>
                    {
                        false !== props.onClearAll
                        &&
                        <Button
                            className="wprmprc-container-header-action wprmprc-container-header-action-clear-shopping-list"
                            onClick={ props.onClearAll }
                            tabIndex="-1"
                            aria-label={ __wprm( 'Remove all recipes from this shopping list' ) }
                        ><Icon type="trash" title={ __wprm( 'Remove All' ) } /></Button>
                    }
                </Fragment>
            }
        </div>
    );
}

export default GridHeaderActions;