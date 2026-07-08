import React, { Component, Fragment } from 'react';

import '../../../../css/admin/modal/recipe/nutrition-calculation.scss';

import Header from 'Modal/general/Header';
import Footer from 'Modal/general/Footer';

import Api from 'Shared/Api';
import StepSource from './StepSource';
import StepMatch from './StepMatch';
import StepCustom from './StepCustom';
import StepSummary from './StepSummary';

import  { parseQuantity, formatQuantity } from 'Shared/quantities';
import { __wprm } from 'Shared/Translations';
import Loader from 'Shared/Loader';

export default class NutritionCalculation extends Component {
    constructor(props) {
        super(props);

        // Remove ingredient groups and ingredients without a name
        let ingredients = props.ingredients.filter( ( ingredient ) => ingredient.type === 'ingredient' && ingredient.name );

        // Parse quantities.
        ingredients = ingredients.map( ( ingredient ) => {
            // Strip HTML and shortcodes from unit.
            let unit = ingredient.unit;
            unit = unit.replace( /(<([^>]+)>)/ig, '' );
	        unit = unit.replace( /(\[([^\]]+)\])/ig, '' );

            ingredient.nutrition = {
                amount: parseQuantity( ingredient.amount ),
                unit,
            }

            return ingredient;
        });

        Api.nutrition.getMatches(ingredients).then((data) => {
            if ( data ) {
                this.setState({
                    ingredients: data.ingredients,
                    calculating: false,
                });
            } else {
                this.setState({
                    calculating: false,
                });
            }
        });

        this.state = {
            step: 'source',
            stepArgs: {},
            ingredients: [],
            apiIngredients: [],
            customIngredients: [],
            calculating: true,
        };

        // Bind functions.
        this.onStepChange = this.onStepChange.bind(this);
        this.onIngredientChange = this.onIngredientChange.bind(this);
    }

    componentDidUpdate( prevProps, prevState ) {
        // Get facts for the API ingredients and check if there are any custom ingredients to do.
        if ( 'source' === prevState.step && 'summary' === this.state.step ) {
            Api.nutrition.getApiFacts(this.state.ingredients).then((data) => {
                if ( data ) {
                    this.setState({
                        calculating: false,
                        apiIngredients: data.ingredients,
                    });
                } else {
                    this.setState({
                        calculating: false,
                    });
                }
            });

            let step = 'summary';
            const customIngredients = this.state.ingredients.filter( (ingredient) => 'custom' === ingredient.nutrition.source );
            if ( 0 < customIngredients.length ) {
                step = 'custom';
            }

            this.setState({
                calculating: true,
                customIngredients,
                step,
            });
        }

        // Check if there are any custom ingredients left to do.
        if ( 'custom' === this.state.step ) {
            const customIngredientsTodo = this.state.customIngredients.filter( (ingredient) => ! ingredient.nutrition.hasOwnProperty('facts') );

            // No more custom ingredients left, go to summary.
            if ( 0 === customIngredientsTodo.length ) {
                this.setState({
                    step: 'summary',
                });
            }
        }
    }

    onStepChange(step, stepArgs = {} ) {
        this.setState({
            step,
            stepArgs,
        });
    }

    onIngredientChange(index, nutrition) {
        let ingredients = JSON.parse( JSON.stringify( this.state.ingredients ) );

        ingredients[index].nutrition = {
            ...ingredients[index].nutrition,
            ...nutrition,
        }

        this.setState({
            ingredients,
        });
    }

    getRecipeFacts() {
        let nutrients = JSON.parse( JSON.stringify( wprm_admin_modal.nutrition ) );
        delete nutrients.serving_size;

        let facts = {};

        const servings = this.props.servings && parseFloat( this.props.servings ) > 0 ? parseFloat( this.props.servings ) : 1;

        for ( let field in nutrients ) {
            let value = false;

            for ( let ingredient of this.state.apiIngredients.concat( this.state.customIngredients ) ) {
                if ( ingredient.nutrition.factsUsed && ingredient.nutrition.facts && ingredient.nutrition.facts[ field ] ) {
                    if ( value ) {
                        value += parseFloat( ingredient.nutrition.facts[ field ] );
                    } else {
                        value = parseFloat( ingredient.nutrition.facts[ field ] );
                    }
                }
            }

            if ( value ) {
                value = value / servings;

                let ignoreQuantity = wprmp_admin.settings.nutrition_facts_calculation_ignore_small_quantity;
                if ( ignoreQuantity ) {
                    const valueToCheck = parseFloat( value );
                    ignoreQuantity = parseFloat( ignoreQuantity );

                    if ( ! isNaN( valueToCheck ) && ! isNaN( ignoreQuantity ) && valueToCheck < ignoreQuantity ) {
                        value = false;
                    }
                }

                if ( value ) {
                    value = formatQuantity( value, wprmp_admin.settings.nutrition_facts_calculation_round_to_decimals );

                    // Needs to use . as decimal character to get displayed in number input.
                    const decimalSeparator = typeof window.wprmp_public !== 'undefined' ? wprmp_public.settings.decimal_separator : wprmp_admin.settings.decimal_separator;
                    const thousandsSymbol = 'comma' === decimalSeparator ? '.' : ',';

                    value = value.replace( thousandsSymbol, '' );
                    value = value.replace( ',', '.' );
                }
            }

            facts[ field ] = value;
        }

        return facts;
    }

    render() {
        let step = null;
        switch ( this.state.step ) {
            case 'source':
                step = (
                    <StepSource
                        ingredients={ this.state.ingredients }
                        onIngredientChange={ this.onIngredientChange }
                        onStepChange={ this.onStepChange }
                    />
                );
                break;
            case 'match':
                const ingredientIndex = this.state.stepArgs.index;

                step = (
                    <StepMatch
                        ingredient={ this.state.ingredients[ ingredientIndex ] }
                        onMatchChange={ (match) => {
                            this.onIngredientChange( ingredientIndex, {
                                ...match,
                            });
                            this.onStepChange('source');
                        }}
                    />
                );
                break;
            case 'custom':
                // Get the first one that doesn't have nutrition facts.
                const todoIndex = this.state.customIngredients.findIndex( (ingredient) => ! ingredient.nutrition.hasOwnProperty('facts') );

                step = (
                    <StepCustom
                        index={ todoIndex }
                        ingredient={ this.state.customIngredients[ todoIndex ] }
                        onFactsChange={ (facts) => {
                            let customIngredients = JSON.parse( JSON.stringify( this.state.customIngredients ) );
                            customIngredients[ todoIndex ].nutrition.facts = facts;

                            this.setState({
                                customIngredients,
                            });
                        }}
                    />
                );
                break;
            case 'summary':
                step = (
                    <StepSummary
                        servings={ this.props.servings }
                        recipeFactsPreview={ this.getRecipeFacts() }
                        apiIngredients={ this.state.apiIngredients }
                        customIngredients={ this.state.customIngredients }
                        onApiIngredientsChange={ (index, nutrition) => {
                            let ingredients = JSON.parse( JSON.stringify( this.state.apiIngredients ) );

                            ingredients[index].nutrition = {
                                ...ingredients[index].nutrition,
                                ...nutrition,
                            }

                            this.setState({
                                apiIngredients: ingredients,
                            });
                        }}
                        onCustomIngredientsChange={ (index, nutrition) => {
                            let ingredients = JSON.parse( JSON.stringify( this.state.customIngredients ) );

                            ingredients[index].nutrition = {
                                ...ingredients[index].nutrition,
                                ...nutrition,
                            }

                            this.setState({
                                customIngredients: ingredients,
                            });
                        }}
                    />
                );
                break;
        }

        let buttons = null;

        const backButton = (
            <button
                className="button button-secondary button-compact"
                onClick={() => {
                    this.onStepChange( 'source' );
                }}
            >
                { __wprm( 'Go Back' ) }
            </button>
        );

        switch ( this.state.step ) {
            case 'source':
                buttons = (
                    <Fragment>
                        <button
                            className="button button-secondary button-compact"
                            onClick={ this.props.maybeCloseModal }
                        >
                            { __wprm( 'Cancel Calculation' ) }
                        </button>
                        <button
                            className="button button-primary button-compact"
                            onClick={() => {
                                this.onStepChange( 'summary' );
                            }}
                        >
                            { __wprm( 'Go to Next Step' ) }
                        </button>
                    </Fragment>
                );
                break;
            case 'match':
                buttons = (
                    <Fragment>
                        { backButton }
                    </Fragment>
                );
                break;
            case 'summary':
                buttons = (
                    <Fragment>
                        { backButton }
                        <button
                            className="button button-primary button-compact"
                            onClick={() => {
                                const calculated = this.getRecipeFacts();
                                this.props.onNutritionChange( calculated );
                                this.props.maybeCloseModal();
                            }}
                        >
                            { __wprm( 'Use These Values' ) }
                        </button>
                    </Fragment>
                );
                break;
        }

        return (
            <Fragment>
                <Header
                    onCloseModal={ this.props.maybeCloseModal }
                >
                    {
                        this.props.name
                        ?
                        `${this.props.name} - ${ __wprm( 'Nutrition Calculation' ) }`
                        :
                        `${ __wprm( 'Recipe' ) } - ${ __wprm( 'Nutrition Calculation' ) }`
                    }
                </Header>
                <div className="wprm-admin-modal-recipe-nutrition-calculation">
                    {
                        this.state.calculating
                        && 'custom' !== this.state.step
                        ?
                        <Loader />
                        :
                        step
                    }
                </div>
                <Footer
                    savingChanges={ this.state.calculating && 'custom' !== this.state.step }
                    alwaysShow={ () => {
                        return (
                            <span className="wprm-modal-footer-notice">{ __wprm( 'Experiencing issues?' ) } <a href="https://help.bootstrapped.ventures/docs/wp-recipe-maker/api-status/" style={ { textDecoration: 'underline', margin: 0 }} target="_blank">{ __wprm( 'Check the API Status' ) }</a>.</span>
                        );
                    }}
                >
                    { buttons }
                </Footer>
            </Fragment>
        );
    }
}