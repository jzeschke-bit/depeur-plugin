import React, { Fragment } from 'react';
import he from 'he';

import { __wprm } from 'Shared/Translations';
import FieldText from 'Modal/fields/FieldText';
import FieldDropdown from 'Modal/fields/FieldDropdown';

const StepSource = (props) => {
    if ( ! props.ingredients.length ) {
        return (
            <p>{ __wprm( 'No ingredients set for this recipe.' ) }</p>
        );
    }

    return (
        <table className="wprm-admin-modal-recipe-nutrition-calculation-source">
            <thead>
            <tr>
                <th>{ __wprm( 'Used in Recipe' ) }</th>
                <th>{ __wprm( 'Used for Calculation' ) }</th>
                <th>{ __wprm( 'Nutrition Source' ) }</th>
                <th>{ __wprm( 'Match & Units' ) }</th>
            </tr>
            </thead>
            <tbody>
            {
                props.ingredients.map( ( ingredient, index ) => {
                    let apiMatchFound = false;
                    if ( 'api' === ingredient.nutrition.source && ingredient.nutrition.match && ingredient.nutrition.match.id && 'custom' !== ingredient.nutrition.match.source ) {
                        apiMatchFound = true;
                    }

                    let customMatchFound = false;
                    if ( 'custom' === ingredient.nutrition.source && ingredient.nutrition.match && 'custom' === ingredient.nutrition.match.source && ingredient.nutrition.match.hasOwnProperty( 'ingredient' ) ) {
                        customMatchFound = true;
                    }

                    return (
                        <tr key={ index }>
                            <td>{ `${ ingredient.amount} ${ ingredient.unit }` }</td>
                            <td>
                                <FieldText
                                    type="number"
                                    value={ ingredient.nutrition.amount }
                                    onChange={ (amount) => {
                                        props.onIngredientChange( index, { amount } );
                                    }}
                                />
                                <FieldText
                                    value={ ingredient.nutrition.unit }
                                    onChange={ (unit) => {
                                        props.onIngredientChange( index, { unit } );
                                    }}
                                />
                                { he.decode( ingredient.name ) } { ingredient.notes ? ` (${ he.decode( ingredient.notes ) })` : '' }
                            </td>
                            <td>
                                <FieldDropdown
                                    options={[
                                        {
                                            value: 'api',
                                            label: __wprm( 'API' ),
                                        },
                                        {
                                            value: 'custom',
                                            label: __wprm( 'Saved/Custom' ),
                                        }
                                    ]}
                                    value={ ingredient.nutrition.source }
                                    onChange={ (source) => {
                                        props.onIngredientChange( index, { source } );
                                    }}
                                    width={ 150 }
                                />
                            </td>
                            <td>
                                {
                                    'api' === ingredient.nutrition.source
                                    &&
                                    <Fragment>
                                        <a
                                            href="#"
                                            onClick={(e) => {
                                                e.preventDefault();

                                                props.onStepChange( 'match', {
                                                    index,
                                                } );
                                            }}
                                            className={ apiMatchFound ? '' : 'wprm-admin-modal-recipe-nutrition-calculation-source-no-match' }
                                        >
                                        {
                                            apiMatchFound
                                            ?
                                            `${ingredient.nutrition.match.name}${ ingredient.nutrition.match.aisle ? ` (${ ingredient.nutrition.match.aisle.toLowerCase() })` : ''}`
                                            :
                                            __wprm( 'no match found' )
                                        }
                                        </a>
                                        {
                                            apiMatchFound
                                            &&
                                            <div className="wprm-admin-modal-recipe-nutrition-calculation-source-units">
                                                {
                                                    ingredient.nutrition.match.hasOwnProperty( 'possibleUnits' )
                                                    && 0 < ingredient.nutrition.match.possibleUnits.length
                                                    ?
                                                    <Fragment>{ ingredient.nutrition.match.possibleUnits.join( ', ' ) }</Fragment>
                                                    :
                                                    __wprm( 'Units n/a' )
                                                }
                                            </div>
                                        }
                                    </Fragment>
                                }
                                {
                                    'custom' === ingredient.nutrition.source
                                    && customMatchFound
                                    &&
                                    <span>{ ingredient.nutrition.match.ingredient.name }</span>
                                }
                            </td>
                        </tr>
                    )
                })
            }
            </tbody>
        </table>
    );
}
export default StepSource;