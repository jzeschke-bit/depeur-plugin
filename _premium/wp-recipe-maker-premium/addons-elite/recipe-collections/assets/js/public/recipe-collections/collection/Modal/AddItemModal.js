import React from 'react';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import SearchIngredient from '../ActionsClassic/AddItem/SearchIngredient';
import SearchRecipe from '../ActionsClassic/AddItem/SearchRecipe';
import SelectCollection from '../ActionsClassic/AddItem/SelectCollection';
import ModalAddCustom from './ModalAddCustom';
import ModalAddNote from './ModalAddNote';
import ModalItems from './ModalItems';

const AddItemModal = (props) => {
    // Available add item modes.
    let addItemModes = [];

    if ( 'admin' === props.type || wprmprc_public.settings.recipe_collections_items_allow_recipe_search ) {
        addItemModes.push( { value: 'search', label: __wprm( 'Search Recipes' )} );
    }

    if ( wprmprc_public.settings.recipe_collections_items_allow_ingredient ) {
        addItemModes.push( { value: 'ingredient', label: __wprm( 'Search Ingredients' )} );
    }

    if ( wprmprc_public.settings.recipe_collections_items_allow_custom_recipe ) {
        addItemModes.push( { value: 'custom', label: __wprm( 'Add Custom Recipe' ) } );
    }

    if ( wprmprc_public.settings.recipe_collections_items_allow_note ) {
        addItemModes.push( { value: 'note', label: __wprm( 'Add Note' ) } );
    }

    if ( 'admin' !== props.type ) {
        addItemModes.unshift(
            { value: 'collection', label: __wprm( 'Add from Collection' )}
        );
    }

    let mode = props.options.mode;
    if ( undefined === addItemModes.find( ( option ) => option.value === mode ) ) {
        if ( addItemModes.length > 0 ) {
            mode = addItemModes[0].value;
        } else {
            mode = false;
        }
    }

    return (
        <div className="wprm-recipe-collections-layout-grid">
            {
                1 < addItemModes.length
                &&
                <div className="wprmprc-collection-modal-add-item-modes">
                    {
                        addItemModes.map( ( option, index ) => {
                            return (
                                <Button
                                    className={ `wprmprc-collection-modal-add-item-mode wprm-popup-modal__btn ${ option.value === mode ? ' active' : '' }` }
                                    onClick={ () => {
                                        props.onChangeModeOptions({
                                            ...props.options,
                                            mode: option.value,
                                        });
                                    } }
                                    key={ index }
                                >{ option.label }</Button>
                            );
                        } )
                    }
                </div>
            }
            {
                'search' === mode
                &&
                <SearchRecipe
                    onChangeAddItems={props.onChangeAddItems}
                    search={props.options.searchRecipe}
                    onChangeSearch={(search) => props.onChangeModeOptions({
                        ...props.options,
                        searchRecipe: search,
                    })}
                />
            }
            {
                'collection' === mode
                &&
                <SelectCollection
                    collections={props.collections}
                    onChangeAddItems={props.onChangeAddItems}
                    collection={props.options.collection}
                    onChangeCollection={(collection) => props.onChangeModeOptions({
                        ...props.options,
                        collection,
                    })}
                />
            }
            {
                'ingredient' === mode
                &&
                <SearchIngredient
                    onChangeAddItems={props.onChangeAddItems}
                    search={props.options.searchIngredient}
                    onChangeSearch={(search) => props.onChangeModeOptions({
                        ...props.options,
                        searchIngredient: search,
                    })}
                />
            }
            {
                'custom' === mode
                &&
                <ModalAddCustom
                    type={props.type}
                    collection={props.collection}
                    addItems={props.addItems}
                    onChangeAddItems={props.onChangeAddItems}
                    interface={props.interface}
                    onAddItem={props.onAddItem}
                />
            }
            {
                'note' === mode
                &&
                <ModalAddNote
                    type={props.type}
                    collection={props.collection}
                    addItems={props.addItems}
                    onChangeAddItems={props.onChangeAddItems}
                    interface={props.interface}
                    onAddItem={props.onAddItem}
                />
            }
            {
                (
                    'collection' === mode
                    || 'search' === mode
                    || 'ingredient' === mode
                )
                &&
                <ModalItems
                    layout={props.layout}
                    type={props.type}
                    collection={props.collection}
                    addItems={props.addItems}
                    interface={props.interface}
                    onAddItem={props.onAddItem}
                />
            }
        </div>
    );
}
export default AddItemModal;