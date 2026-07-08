import React, { Component } from 'react';

import '../../../../../css/admin/modal/recipe/unit-conversion.scss';

import Api from 'Shared/Api';
import { __wprm } from 'Shared/Translations';
import  { parseQuantity, formatQuantity } from 'Shared/quantities';

import FieldDropdown from 'Modal/fields/FieldDropdown';
import UnitConversionIngredient from './UnitConversionIngredient';

export default class UnitConversion extends Component {
    constructor(props) {
        super(props);

        this.state = {
            isConverting: {},
            methods: {},
            fieldVersions: {}, // Track versions for each ingredient to force FieldRichText updates on external changes
        }

        this.convert = this.convert.bind(this);
        this.convertAll = this.convertAll.bind(this);
    }

    // Helper function to create a snapshot from an ingredient
    createSnapshot(ingredient) {
        return {
            amount: ingredient.amount || '',
            unit: ingredient.unit || '',
            timestamp: Date.now()
        };
    }

    updateIngredients(updater) {
        this.props.onIngredientsChange((ingredients) => {
            let newIngredients = JSON.parse( JSON.stringify( ingredients ) );
            updater( newIngredients );
            return newIngredients;
        });
    }

    convertAll( method, system = 'default' ) {
        let indexesToConvert = [];

        for ( let i = 0; i < this.props.ingredients.length; i++ ) {
            const ingredient = this.props.ingredients[ i ];

            if ( 'ingredient' === ingredient.type ) {
                indexesToConvert.push( i );
            }
        }

        if ( indexesToConvert ) {
            this.convert( indexesToConvert, method, system );
        }
    }

    convert( indexes, method, system = 'default' ) {
        let isConverting = this.state.isConverting;
        let methods = this.state.methods;

        if ( 'none' === method ) {
            const fieldVersions = { ...this.state.fieldVersions };
            this.updateIngredients((newIngredients) => {
                for ( let index of indexes ) {
                    const ingredient = newIngredients[ index ];

                    if ( ! ingredient.hasOwnProperty('converted') ) {
                        ingredient.converted = { 2: {} };
                    }

                    ingredient.converted[2].amount = ingredient.amount;
                    ingredient.converted[2].unit = ingredient.unit;

                    // Create snapshot of original ingredient values when conversion is set
                    ingredient.conversion_item_snapshot = this.createSnapshot( ingredient );

                    // Increment version to force FieldRichText remount
                    fieldVersions[ index ] = (fieldVersions[ index ] || 0) + 1;

                    isConverting[ index ] = false;
                    methods[ index ] = method;
                }
            });
            this.setState({ fieldVersions });
        } else {
            let ingredientsToConvert = {};

            for ( let index of indexes ) {
                const ingredient = this.props.ingredients[ index ];

                ingredientsToConvert[ index ] = {
                    index,
                    amount: parseQuantity( ingredient.amount ),
                    unit: ingredient.unit,
                    name: ingredient.name,
                };

                // Force conversion to specific unit.
                if ( 'automatic' !== method ) {
                    ingredientsToConvert[ index ].units_to = [ method ];
                }

                isConverting[ index ] = true;
                methods[ index ] = method;
            }

            Api.unitConversion.get( ingredientsToConvert, system ).then((data) => {
                if ( data && data.conversions ) {
                    let isConverting = this.state.isConverting;
                    let methods = this.state.methods;
                    const fieldVersions = { ...this.state.fieldVersions };
                    this.updateIngredients((newIngredients) => {
                        for ( let index in data.conversions ) {
                            const ingredient = newIngredients[ index ];
                            const conversion = data.conversions[ index ];

                            if ( ! ingredient.hasOwnProperty('converted') ) {
                                ingredient.converted = { 2: {} };
                            }

                            if ( 'failed' === conversion.type ) {
                                ingredient.converted[2].amount = ingredient.amount;
                                ingredient.converted[2].unit = ingredient.unit;
                                methods[ index ] = conversion.type;
                            } else {
                                let allowFractions = wprmp_admin.settings.unit_conversion_system_2_fractions;
                                ingredient.converted[2].amount = formatQuantity( conversion.amount, wprmp_admin.settings.unit_conversion_round_to_decimals, allowFractions );
                                ingredient.converted[2].unit = conversion.alias;
                                methods[ index ] = 'none' === conversion.type ? 'none' : method;
                            }

                            // Create snapshot of original ingredient values when conversion is calculated
                            ingredient.conversion_item_snapshot = this.createSnapshot( ingredient );

                            // Increment version to force FieldRichText remount
                            fieldVersions[ index ] = (fieldVersions[ index ] || 0) + 1;

                            isConverting[ index ] = false;
                        }
                    });
                    this.setState({
                        isConverting,
                        methods,
                        fieldVersions,
                    });
                }
            });
        }

        this.setState({
            isConverting,
            methods,
        });
    }

    render() {
        if ( ! wprm_admin.addons.pro ) {
            return (
                <p>{ __wprm( 'This feature is only available in' ) } <a href="https://bootstrapped.ventures/wp-recipe-maker/get-the-plugin/" target="_blank">WP Recipe Maker Pro Bundle</a>.</p>
            );
        }

        if ( ! wprm_admin_modal.unit_conversion ) {
            return (
                <p>{ __wprm( 'You need to set up this feature on the WP Recipe Maker > Settings > Unit Conversion page first.' ) }</p>
            );
        }

        const ingredients = this.props.ingredients.filter((field) => 'ingredient' === field.type && field.name );
        if ( ! ingredients.length ) {
            return (
                <p>{ __wprm( 'No ingredients set for this recipe.' ) }</p>
            );
        }

        // Get Unit System for the recipe.
        let recipeUnitSystem = 'default' === this.props.system ? wprm_admin_modal.unit_conversion.default_system : this.props.system;
        recipeUnitSystem = parseInt( recipeUnitSystem );
        
        // Prevent issues.
        if ( recipeUnitSystem !== 1 && recipeUnitSystem !== 2 ) {
            recipeUnitSystem = 1;
        }
        const convertedUnitSystem = 2 === recipeUnitSystem ? 1 : 2;
    
        return (
            <div
                className="wprm-admin-modal-field-ingredient-unit-conversion-container"
            >
                <div
                    className="wprm-admin-modal-field-ingredient-unit-conversion-system"
                >
                    <label>{ __wprm( 'Original Unit System for this recipe' ) }:</label>
                    <FieldDropdown
                        options={ [
                            {
                                value: 'default',
                                label: `${ __wprm( 'Use Default' ) } (${ wprm_admin_modal.unit_conversion.systems[ parseInt( wprm_admin_modal.unit_conversion.default_system ) ].label })`,
                            },
                            {
                                value: '1',
                                label: `${ __wprm( 'First Unit System' ) } (${ wprm_admin_modal.unit_conversion.systems[1].label })`,
                            },
                            {
                                value: '2',
                                label: `${ __wprm( 'Second Unit System' ) } (${ wprm_admin_modal.unit_conversion.systems[2].label })`,
                            },
                        ] }
                        value={ this.props.system }
                        onChange={ (system) => {
                            this.props.onSystemChange( system );
                        }}
                        width={ 255 }
                    />
                </div>
                <table
                    className="wprm-admin-modal-field-ingredient-unit-conversion"
                >
                    <thead>
                    <tr>
                        <th>{ __wprm( 'Conversion' ) }</th>
                        <th>{ __wprm( 'Converted' ) } ({ wprm_admin_modal.unit_conversion.systems[ convertedUnitSystem ].label })</th>
                        <th>{ __wprm( 'Original' ) } ({ wprm_admin_modal.unit_conversion.systems[ recipeUnitSystem ].label })</th>
                    </tr>
                    </thead>
                    <tbody>
                    {
                        this.props.ingredients.map((field, index) => {
                            if ( 'group' === field.type || ! field.name ) {
                                return null;
                            }
        
                            // Use stable key based on index to prevent remounting on value changes
                            // This prevents input fields from losing focus when editing
                            return (
                                <UnitConversionIngredient
                                    key={ index }
                                    ingredient={ field }
                                    allIngredients={ this.props.ingredients }
                                    isConverting={ this.state.isConverting[ index ] }
                                    method={ this.state.methods[ index ] }
                                    onMethodChange={(method) => {
                                        if ( ! this.state.isConverting[ index ] ) {
                                            this.convert( [ index ], method, convertedUnitSystem );
                                        }
                                    }}
                                    onConvertedFieldChange={(field, value) => {
                                        this.updateIngredients((newIngredients) => {
                                            const ingredient = newIngredients[ index ];

                                            // Ensure converted structure exists
                                            if ( ! ingredient.converted ) {
                                                ingredient.converted = { 2: {} };
                                            }
                                            if ( ! ingredient.converted[2] ) {
                                                ingredient.converted[2] = {};
                                            }

                                            // Update only the specific field
                                            ingredient.converted[2][field] = value;

                                            // Create or update snapshot when converted values are manually changed
                                            if ( ingredient.converted[2].amount || ingredient.converted[2].unit ) {
                                                ingredient.conversion_item_snapshot = this.createSnapshot( ingredient );
                                            }
                                        });
                                    }}
                                    onRecalculateProportionally={(newConvertedAmount) => {
                                        this.updateIngredients((newIngredients) => {
                                            const ingredient = newIngredients[ index ];

                                            // Ensure converted structure exists
                                            if ( ! ingredient.converted ) {
                                                ingredient.converted = { 2: {} };
                                            }
                                            if ( ! ingredient.converted[2] ) {
                                                ingredient.converted[2] = {};
                                            }

                                            // Update converted amount proportionally, keep the same unit
                                            ingredient.converted[2].amount = newConvertedAmount;
                                            ingredient.converted[2].unit = ingredient.converted[2].unit || '';

                                            // Update snapshot to match current values
                                            ingredient.conversion_item_snapshot = this.createSnapshot( ingredient );
                                        });

                                        // Increment version to force FieldRichText remount
                                        const fieldVersions = { ...this.state.fieldVersions };
                                        fieldVersions[index] = (fieldVersions[index] || 0) + 1;

                                        this.setState({ fieldVersions });
                                    }}
                                    onClearConversion={() => {
                                        this.updateIngredients((newIngredients) => {
                                            // Ensure converted structure exists and is properly cleared
                                            newIngredients[ index ].converted = { 2: { amount: '', unit: '' } };
                                            delete newIngredients[ index ].conversion_item_snapshot;
                                        });

                                        // Increment version to force FieldRichText remount
                                        const fieldVersions = { ...this.state.fieldVersions };
                                        fieldVersions[index] = (fieldVersions[index] || 0) + 1;

                                        this.setState({ fieldVersions });
                                    }}
                                    onMarkAsOK={() => {
                                        this.updateIngredients((newIngredients) => {
                                            const ingredient = newIngredients[ index ];
                                            // Update snapshot to match current values
                                            ingredient.conversion_item_snapshot = this.createSnapshot( ingredient );
                                        });
                                    }}
                                    fieldVersion={this.state.fieldVersions[index] || 0}
                                    convertedUnitSystem={ convertedUnitSystem }
                                />
                            )
                        })
                    }
                    </tbody>
                </table>
                <button
                    className="button button-primary button-compact"
                    onClick={(e) => {
                        e.preventDefault();
                        this.convertAll( 'automatic', convertedUnitSystem );
                    } }
                >{ __wprm( 'Convert All Automatically' ) }</button>
            </div>
        );
    }
}