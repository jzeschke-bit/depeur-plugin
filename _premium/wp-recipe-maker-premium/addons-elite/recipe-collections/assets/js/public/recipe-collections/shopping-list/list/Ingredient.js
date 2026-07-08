import React, { Fragment } from 'react';
import { Draggable } from 'react-beautiful-dnd';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Checkbox from '../../general/Checkbox';
import Icon from '../../general/Icon';

// Use own function instead of he library to reduce bundle size.
const decode = (str) => {
    const entities = {
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&quot;': '"',
        '&#039;': "'",
        '&ndash;': '–',
        '&mdash;': '—',
        '&deg;': '°',
        '&frac12;': '½',
        '&frac14;': '¼',
        '&frac34;': '¾',
        '&nbsp;': ' '
    };
    return str.replace(/&[^;]+;/g, (entity) => entities[entity] || entity);
};

const Ingredient = (props) => {
    const { ingredient, index, editing } = props;

    if ( ! ingredient.name && ! editing ) {
        return null;
    }

    return (
        <Draggable
            draggableId={ `ingredient-${ingredient.id}` }
            index={ index }
            key={ ingredient.id }
            type="INGREDIENT"
            isDragDisabled={ ! editing }
        >
            {(provided, snapshot) => (
                <div
                    className={`wprmprc-shopping-list-list-ingredient${ ingredient.checked && ! editing ? ' wprmprc-shopping-list-list-ingredient-checked' : ''}`}
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                >
                    <div className="wprmprc-shopping-list-list-ingredient-name-container">
                        {
                            editing
                            ?
                            <Fragment>
                                <div className="wprmprc-shopping-list-editing-actions">
                                    <div
                                        className="wprmprc-shopping-list-editing-handle"
                                        {...provided.dragHandleProps}
                                    ><Icon type="drag" /></div>
                                    <Button
                                        className="wprmprc-shopping-list-editing-delete"
                                        onClick={() => {
                                            props.onIngredientDelete();
                                        }}
                                        aria-label={ __wprm( 'Delete this ingredient from the shopping list' ) }
                                    ><Icon type="delete" /></Button>
                                </div>
                                <input
                                    type="text"
                                    value={ decode( ingredient.name ) }
                                    onChange={(event) => {
                                        props.onIngredientChange({
                                            ...ingredient,
                                            name: event.target.value,
                                        });
                                    }}
                                />
                            </Fragment>
                            :
                            <Fragment>
                                <Checkbox
                                    checked={ ingredient.checked }
                                    onChange={ ( checked ) => {
                                        props.onIngredientChange({
                                            ...ingredient,
                                            checked,
                                        });
                                    } }
                                    id={ `wprmprc-shopping-list-list-ingredient-${ ingredient.id }` }
                                />
                                <label
                                    className="wprmprc-shopping-list-list-ingredient-name"
                                    htmlFor={ `wprmprc-shopping-list-list-ingredient-${ ingredient.id }` }
                                >
                                    {
                                        ingredient.link && ingredient.link.url
                                        ?
                                        <a href={ingredient.link.url} target="_blank" rel="nofollow">{ decode( ingredient.name ) }</a>
                                        :
                                        decode( ingredient.name )
                                    }
                                </label>
                            </Fragment>
                        }
                    </div>
                    <div className="wprmprc-shopping-list-list-ingredient-variations">
                        {
                            ingredient.variations.map((variation, index) => {
                                let variationDisplay = variation.hasOwnProperty( 'display' ) ? variation.display : `${ variation.amount ? variation.amount + ' ' : '' } ${ variation.unit ? variation.unit : '' }`;

                                // Trim (but only when not editing or you can't type spaces).
                                if ( ! editing ) {
                                    variationDisplay = variationDisplay.trim();
                                }

                                if ( ! variationDisplay && ! editing ) {
                                    return null;
                                }

                                if ( editing ) {
                                    return (
                                        <div className="wprmprc-shopping-list-list-ingredient-variation" key={index}>
                                            <input
                                                type="text"
                                                value={ variationDisplay }
                                                onChange={(event) => {
                                                    let newVariations = JSON.parse( JSON.stringify( ingredient.variations ) );

                                                    newVariations[index].display = event.target.value;

                                                    props.onIngredientChange({
                                                        ...ingredient,
                                                        variations: newVariations,
                                                    });
                                                }}
                                            />
                                        </div>
                                    )
                                } else {
                                    return (
                                        <div className="wprmprc-shopping-list-list-ingredient-variation" key={index}>{ variationDisplay }</div>
                                    )
                                }
                            })
                        }
                    </div>
                </div>
            )}
        </Draggable>
    );
}
export default Ingredient;