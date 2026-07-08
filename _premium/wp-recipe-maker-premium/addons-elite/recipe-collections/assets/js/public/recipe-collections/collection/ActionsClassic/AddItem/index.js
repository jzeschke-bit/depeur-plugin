import React, { Component, Fragment } from 'react';
import Select from 'react-select';

import { __wprm } from 'Shared/Translations';

import SearchIngredient from './SearchIngredient';
import SearchRecipe from './SearchRecipe';
import SelectCollection from './SelectCollection';
import AddCustom from './AddCustom';
import AddNote from './AddNote';
import AddItems from './AddItems';

const AddItem = (props) => {
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
        <Fragment>
            <Select
                className="wprmprc-collection-action-add-item-mode"
                value={addItemModes.filter(({value}) => value === mode)}
                onChange={(option) => props.onChangeModeOptions({
                    ...props.options,
                    mode: option.value
                })}
                options={addItemModes}
                clearable={false}
                styles={{
                    control: styles => ({ ...styles, borderRadius: 5 }),
                }}
            />
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
                <AddCustom
                    layout={props.layout}
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
                <AddNote
                    layout={props.layout}
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
                <AddItems
                    layout={props.layout}
                    type={props.type}
                    collection={props.collection}
                    addItems={props.addItems}
                    interface={props.interface}
                    onAddItem={props.onAddItem}
                />
            }
        </Fragment>
    );
}

export default AddItem;