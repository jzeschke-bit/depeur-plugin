import React, { Fragment } from 'react';
import Select from 'react-select';

import { __wprm } from 'Shared/Translations';

import EditList from '../../../general/EditList';

const EditCustom = (props) => {
    const { item } = props;

    const colorOptions = [
        { value: 'none', label: __wprm( 'None' ) },
        { value: 'blue', label: __wprm( 'Blue' ) },
        { value: 'red', label: __wprm( 'Red' ) },
        { value: 'green', label: __wprm( 'Green' ) },
        { value: 'yellow', label: __wprm( 'Yellow' ) },
    ];

    return (
        <div className="wprmprc-collection-action-edit-ingredient-form">
            <label htmlFor="wprmprc-collection-action-edit-ingredient-name">{ __wprm( 'Name' ) }</label>
            <input
                id="wprmprc-collection-action-edit-ingredient-name"
                type="text"
                value={item.name}
                onChange={(event) => 
                    props.onEdit({
                        ...item,
                        name: event.target.value,
                    })
                }
            />
            <label>{ __wprm( 'Ingredients' ) }</label>
            <EditList
                type='ingredient'
                onAdd={() => {
                    let items = [ ...item.ingredients ];
                    let maxId = Math.max.apply( Math, items.map( function(item) { return item.id; } ) );
                    maxId = maxId < 0 ? -1 : maxId;

                    items.push({ id: maxId + 1, amount: '', unit: '', name: '' });
                    props.onEdit({
                        ...item,
                        ingredients: items,
                    });
                }}
                onDelete={(id, index) => {
                    let items = [ ...item.ingredients ];
                    items.splice(index, 1);

                    props.onEdit({
                        ...item,
                        ingredients: items,
                    });
                }}
                onReorder={(newItems) => {
                    props.onEdit({
                        ...item,
                        ingredients: newItems,
                    });
                }}
                items={item.ingredients}
                item={(editing, ingredient, index) =>
                    <Fragment>
                        {
                            editing
                            ?
                            <Fragment>
                                <input
                                    type="text"
                                    value={ingredient.amount}
                                    placeholder="1"
                                    onChange={(event) => {
                                        let items = [ ...item.ingredients ];

                                        items[index] = {
                                            ...items[index],
                                            amount: event.target.value,
                                        }

                                        props.onEdit({
                                            ...item,
                                            ingredients: items,
                                        });
                                    }}
                                />
                                <input
                                    type="text"
                                    value={ingredient.unit}
                                    placeholder={ __wprm( 'cup' ) }
                                    onChange={(event) => {
                                        let items = [ ...item.ingredients ];

                                        items[index] = {
                                            ...items[index],
                                            unit: event.target.value,
                                        }

                                        props.onEdit({
                                            ...item,
                                            ingredients: items,
                                        });
                                    }}
                                />
                                <input
                                    type="text"
                                    value={ingredient.name}
                                    placeholder={ __wprm( 'olive oil' ) }
                                    onChange={(event) => {
                                        let items = [ ...item.ingredients ];

                                        items[index] = {
                                            ...items[index],
                                            name: event.target.value,
                                        }

                                        props.onEdit({
                                            ...item,
                                            ingredients: items,
                                        });
                                    }}
                                />
                            </Fragment>
                            :
                            <Fragment>
                                { `${ ingredient.amount ? `${ ingredient.amount } ` : '' } ${ ingredient.unit ? `${ ingredient.unit } ` : '' }${ ingredient.name }`.trim() }
                            </Fragment>
                        }
                    </Fragment>
                }
                labels={{
                    add: __wprm( 'Add Ingredient' ),
                    edit: __wprm( 'Edit Ingredients' ),
                }}
                editing={ true }
                skipConfirm={ true }
            />
            <label htmlFor="wprmprc-collection-action-edit-ingredient-text">{ __wprm( 'Text' ) }</label>
            <textarea
                id="wprmprc-collection-action-edit-ingredient-text"
                value={ item.hasOwnProperty( 'text' ) ? item.text : '' }
                onChange={(event) => 
                    props.onEdit({
                        ...item,
                        text: event.target.value,
                    })
                }
            />
            {
                true === wprmprc_public.settings.recipe_collections_nutrition_facts
                &&
                <Fragment>
                    <label>{ __wprm( 'Nutrition Facts (per serving)' ) }</label>
                    {
                        wprmprc_public.settings.recipe_collections_nutrition_facts_fields.map((nutritionField, index) => {
                            const value = item.nutrition.hasOwnProperty( nutritionField ) ? item.nutrition[ nutritionField ] : '';
                            const label = wprmprc_public.labels.nutrition_fields.hasOwnProperty( nutritionField ) ? wprmprc_public.labels.nutrition_fields[ nutritionField ].label : nutritionField;
                            const unit = wprmprc_public.labels.nutrition_fields.hasOwnProperty( nutritionField ) ? wprmprc_public.labels.nutrition_fields[ nutritionField ].unit : '';

                            return (
                                <div
                                    className="wprmprc-collection-action-edit-ingredient-nutrient"
                                    key={index}
                                >
                                    <div className="wprmprc-collection-action-edit-ingredient-nutrient-value">
                                        <input
                                            type="number"
                                            value={ value }
                                            onChange={(event) => {
                                                let nutrition = {
                                                    ...item.nutrition,
                                                };
                                                nutrition[ nutritionField ] = event.target.value;

                                                props.onEdit({
                                                    ...item,
                                                    nutrition,
                                                });
                                            }}
                                        /> { unit }
                                    </div>
                                    {
                                        label
                                        ?
                                        <div className="wprmprc-collection-action-edit-ingredient-nutrient-label">{ label }</div>
                                        :
                                        null
                                    }
                                </div>
                            );
                        })
                    }
                </Fragment>
            }
            <label>{ __wprm( 'Color' ) }</label>
            <Select
                className="wprmprc-collection-action-edit-item-color"
                value={colorOptions.filter(({value}) => value === item.color)}
                onChange={(option) =>
                    props.onEdit({
                        ...item,
                        color: option.value,
                    })
                }
                options={colorOptions}
                clearable={false}
                styles={{
                    control: styles => ({ ...styles, borderRadius: 5 }),
                }}
                menuPlacement="top"
            />
        </div>
    );
}
export default EditCustom;