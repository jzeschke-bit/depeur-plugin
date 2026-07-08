import React, { Component, Fragment } from 'react';

import '../../../css/admin/modal/nutrition.scss';

import { __wprm } from 'Shared/Translations';
import Loader from 'Shared/Loader';
import Header from 'Modal/general/Header';
import Footer from 'Modal/general/Footer';

import FieldText from 'Modal/fields/FieldText';
import SelectRecipe from 'Modal/select/SelectRecipe';
import Api from 'Shared/Api';
import Nutrients from '../recipe/nutrition-calculation/Nutrients';

export default class Menu extends Component {
    constructor(props) {
        super(props);

        let ingredient = {
            id: 0,
            amount: '',
            unit: '',
            name: '',
            facts: {},
        }
        let loading = false;

        if ( props.args.hasOwnProperty( 'ingredient' ) ) {
            ingredient = JSON.parse( JSON.stringify( props.args.ingredient ) );
        } else if ( props.args.hasOwnProperty( 'ingredientId' ) ) {
            loading = true;
            Api.nutrition.getCustomIngredient(props.args.ingredientId).then((data) => {
                if ( data ) {
                    const savedIngredient = JSON.parse( JSON.stringify( data.ingredient ) );

                    if ( savedIngredient ) {
                        const ingredient = {
                            id: savedIngredient.id,
                            amount: savedIngredient.nutrition.amount,
                            unit: savedIngredient.nutrition.unit,
                            name: savedIngredient.name,
                            facts: savedIngredient.nutrition.nutrients,
                        }

                        this.setState({
                            ingredient,
                            originalIngredient: JSON.parse( JSON.stringify( ingredient ) ),
                            loading: false,
                        });
                    }
                }
            });
        }

        this.state = {
            mode: 'nutrition',
            importRecipe: false,
            ingredient,
            originalIngredient: JSON.parse( JSON.stringify( ingredient ) ),
            loading,
            savingChanges: false,
        };

        this.changesMade = this.changesMade.bind(this);
        this.saveChanges = this.saveChanges.bind(this);
        this.importNutrition = this.importNutrition.bind(this);
    }

    importNutrition() {
        if ( ! this.state.importRecipe ) {
            this.setState({
                mode: 'nutrition',
            });
        } else {
            this.setState({
                loading: true,
            }, () => {
                Api.recipe.get( this.state.importRecipe.id ).then((data) => {
                    let newIngredient = {
                        ...this.state.ingredient,
                        facts: {},
                    };

                    if ( data && data.recipe && data.recipe.nutrition ) {
                        let nutrients = JSON.parse( JSON.stringify( wprm_admin_modal.nutrition ) );
                        delete nutrients.serving_size;

                        for ( let nutrient of Object.keys( nutrients ) ) {
                            if ( 'calculated' !== nutrients[ nutrient ].type ) {
                                if ( data.recipe.nutrition.hasOwnProperty( nutrient ) ) {
                                    newIngredient.facts[ nutrient ] = '' + data.recipe.nutrition[ nutrient ];
                                }
                            }
                        }

                        newIngredient.amount = data.recipe.nutrition.hasOwnProperty('serving_size') && data.recipe.nutrition['serving_size'] ? data.recipe.nutrition['serving_size'] : '';
                        newIngredient.unit = data.recipe.nutrition.hasOwnProperty('serving_unit') && data.recipe.nutrition['serving_unit'] ? data.recipe.nutrition['serving_unit'] : 'g';
                        newIngredient.name = data.recipe.name
                    }

                    this.setState({
                        loading: false,
                        mode: 'nutrition',
                        ingredient: newIngredient,
                    });
                });
            });
        }
    }

    saveChanges() {
        if ( '' === this.state.ingredient.name.trim() ) {
            alert( __wprm( 'A name is required for this saved nutrition ingredient.' ) );
        } else {
            this.setState({
                savingChanges: true,
            }, () => {
                Api.nutrition.saveCustomIngredient(this.state.ingredient.id, this.state.ingredient.amount, this.state.ingredient.unit, this.state.ingredient.name, this.state.ingredient.facts ).then(() => {
                    this.setState({
                        originalIngredient: JSON.parse( JSON.stringify( this.state.ingredient ) ),
                        savingChanges: false,
                    },() => {
                        if ( 'function' === typeof this.props.args.saveCallback ) {
                            this.props.args.saveCallback( this.state.ingredient );
                        }
                        this.props.maybeCloseModal();
                    });
                });
            })
        }
    }

    allowCloseModal() {
        return ! this.state.savingChanges && ( ! this.changesMade() || confirm( __wprm( 'Are you sure you want to close without saving changes?' ) ) );
    }

    changesMade() {
        return JSON.stringify( this.state.ingredient ) !== JSON.stringify( this.state.originalIngredient );
    }

    render() {
        return (
            <Fragment>
                <Header
                    onCloseModal={ this.props.maybeCloseModal }
                >
                    {
                        this.state.loading
                        ?
                        __wprm( 'Loading...' )
                        :
                        <Fragment>
                            {
                                this.state.ingredient.id
                                ?
                                
                                `${ __wprm( 'Editing Nutrition Ingredient' ) } #${this.state.ingredient.id}${this.state.ingredient.name ? ` - ${this.state.ingredient.name}` : ''}`
                                :
                                `${ __wprm( 'Creating new Nutrition Ingredient' ) }${this.state.ingredient.name ? ` - ${this.state.ingredient.name}` : ''}`
                            }
                        </Fragment>
                    }
                </Header>
                <div className="wprm-admin-modal-nutrition-container">
                    {
                        this.state.loading
                        ?
                        <Loader />
                        :
                        <Fragment>
                            {
                                'import' === this.state.mode
                                ?
                                <SelectRecipe
                                    options={ [] }
                                    value={ this.state.importRecipe }
                                    onValueChange={(importRecipe) => {
                                        this.setState({ importRecipe });
                                    }}
                                />
                                :
                                <Fragment>
                                    <div className="wprm-admin-modal-nutrition-custom-ingredient">
                                        <FieldText
                                            type="number"
                                            placeholder={ __wprm( 'Amount' ) }
                                            value={ this.state.ingredient.amount }
                                            onChange={ (amount) => {
                                                this.setState({
                                                    ingredient: {
                                                        ...this.state.ingredient,
                                                        amount,
                                                    }
                                                });
                                            }}
                                        />
                                        <FieldText
                                            placeholder={ __wprm( 'Unit' ) }
                                            value={ this.state.ingredient.unit }
                                            onChange={ (unit) => {
                                                this.setState({
                                                    ingredient: {
                                                        ...this.state.ingredient,
                                                        unit,
                                                    }
                                                });
                                            }}
                                        />
                                        <FieldText
                                            placeholder={ __wprm( 'Name (required)' ) }
                                            value={ this.state.ingredient.name }
                                            onChange={ (name) => {
                                                this.setState({
                                                    ingredient: {
                                                        ...this.state.ingredient,
                                                        name,
                                                    }
                                                });
                                            }}
                                        />
                                    </div>
                                    <Nutrients
                                        id="modal"
                                        facts={ this.state.ingredient.facts }
                                        onChange={ (nutrient, value) => {
                                            let facts = { ...this.state.ingredient.facts };
                                            facts[ nutrient ] = value;

                                            this.setState({
                                                ingredient: {
                                                    ...this.state.ingredient,
                                                    facts,
                                                }
                                            });
                                        }}
                                    />
                                    <br/>
                                    <button
                                        className="button button-secondary button-compact"
                                        onClick={ () => {
                                            // Check if there are any existing values set.
                                            let hasValuesSet = false;
                                            for ( let value of Object.values( this.state.ingredient.facts ) ) {
                                                if ( value ) {
                                                    hasValuesSet = true;
                                                }
                                            }

                                            if ( ! hasValuesSet || confirm( __wprm( 'Are you sure you want to overwrite the existing values?' ) ) ) {
                                                this.setState({
                                                    mode: 'import',
                                                });
                                            }
                                        } }
                                    >
                                        { __wprm( 'Import values from recipe' ) }
                                    </button>
                                </Fragment>
                            }
                        </Fragment>
                    }
                </div>
                <Footer
                    savingChanges={ this.state.savingChanges }
                >
                    {
                        'import' === this.state.mode
                        ?
                        <Fragment>
                            <button
                                className="button button-secondary button-compact"
                                onClick={ () => {
                                    this.setState({
                                        importRecipe: false,
                                        mode: 'nutrition',
                                    });
                                } }
                            >
                                { __wprm( 'Cancel import' ) }
                            </button>
                            <button
                                className="button button-primary button-compact"
                                onClick={ this.importNutrition }
                                disabled={ ! this.state.importRecipe }
                            >
                                { __wprm( 'Use this recipe' ) }
                            </button>
                        </Fragment>
                        :
                        <button
                            className="button button-primary button-compact"
                            onClick={ this.saveChanges }
                            disabled={ ! this.changesMade() }
                        >
                            { __wprm( 'Save' ) }
                        </button>
                    }
                </Footer>
            </Fragment>
        );
    }
}