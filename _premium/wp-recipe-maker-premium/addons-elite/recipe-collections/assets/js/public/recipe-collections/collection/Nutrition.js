import React, { Component, Fragment } from 'react';
import  { formatQuantity } from 'Shared/quantities';

import { __wprm } from 'Shared/Translations';

import Api from '../general/Api';
import Loader from '../general/Loader';

export default class Nutrition extends Component {
    constructor(props) {
        super(props);

        this.state = {
            loadingNutrition: true,
            nutrition: [],
        }
        
        // Track pending API calls to prevent race conditions
        this.pendingNutritionRequests = new Set();
    }

    componentDidMount() {
        this.checkRecipes();
    }

    componentDidUpdate( prevProps ) {
        if ( JSON.stringify(this.props.items) !== JSON.stringify(prevProps.items) 
             || JSON.stringify(this.props.recipes) !== JSON.stringify(prevProps.recipes) ) {
            this.checkRecipes();
        }
    }

    checkRecipes() {
        let allRecipes = [];
        let allRecipesServings = [];
        let customRecipes = [];
        let recipesWithoutNutrition = [];

        for ( let item of this.props.items ) {
            if ( 'recipe' === item.type ) {
                let recipe = this.props.recipes.hasOwnProperty(item.recipeId) ? this.props.recipes[item.recipeId] : false;

                if ( ! recipe || ! recipe.hasOwnProperty('nutrition') || ! recipe.nutrition || Object.keys(recipe.nutrition).length === 0 ) {
                    if ( ! recipesWithoutNutrition.includes( item.recipeId ) ) {
                        recipesWithoutNutrition.push(item.recipeId);
                    }
                }

                let servings = item.hasOwnProperty( 'servings' ) ? item.servings : 1;

                allRecipes.push(item.recipeId);
                allRecipesServings.push( servings );
            } else if ( 'ingredient' === item.type || 'nutrition-ingredient' === item.type ) {
                if ( item.hasOwnProperty( 'nutrition' ) && Object.keys( item.nutrition ).length > 0 ) {
                    customRecipes.push(item.id);
                }
            }
        }

        if ( 0 < recipesWithoutNutrition.length ) {
            // Create a unique key for this request to track it
            const requestKey = recipesWithoutNutrition.sort().join(',');
            
            // Skip if we're already loading nutrition for these recipes
            if ( this.pendingNutritionRequests.has(requestKey) ) {
                return;
            }
            
            this.pendingNutritionRequests.add(requestKey);
            
            this.setState({
                loadingNutrition: true,
            }, () => {
                Api.getNutrition(recipesWithoutNutrition).then((recipes) => {
                    // Remove from pending requests
                    this.pendingNutritionRequests.delete(requestKey);
                    
                    if ( recipes ) {
                        this.props.onUpdateRecipes( recipes );
                        
                        // Merge the newly fetched recipes with current recipes to avoid race condition
                        // React state updates are asynchronous, so this.props.recipes might not be updated yet
                        const updatedRecipes = JSON.parse(JSON.stringify(this.props.recipes));
                        for ( let recipeId in recipes ) {
                            if ( recipes.hasOwnProperty( recipeId ) ) {
                                const oldRecipe = updatedRecipes.hasOwnProperty(recipeId) ? updatedRecipes[recipeId] : {};
                                updatedRecipes[recipeId] = {
                                    ...oldRecipe,
                                    ...recipes[recipeId],
                                }
                            }
                        }
                        
                        this.updateNutrition( allRecipes, allRecipesServings, customRecipes, updatedRecipes );
                    } else {
                        // If API call failed, still try to update with what we have
                        this.updateNutrition( allRecipes, allRecipesServings, customRecipes );
                    }
                }).catch((error) => {
                    // Remove from pending requests on error
                    this.pendingNutritionRequests.delete(requestKey);
                    // Still try to update with what we have
                    this.updateNutrition( allRecipes, allRecipesServings, customRecipes );
                });
            });
        } else {
            this.updateNutrition( allRecipes, allRecipesServings, customRecipes );
        }
    }

    updateNutrition(recipes, recipesServings, custom, recipesData = null) {
        // Use provided recipesData if available (to avoid race conditions), otherwise use props
        const recipesToUse = recipesData || this.props.recipes;
        
        let nutritionFields = {};

        for ( let nutritionField of wprmprc_public.settings.recipe_collections_nutrition_facts_fields ) {
            // Default to 0.
            if ( ! nutritionFields.hasOwnProperty( nutritionField ) ) {
                nutritionFields[ nutritionField ] = 0.0;
            }

            // Add values for all recipes.
            for ( let i = 0; i < recipes.length; i++ ) {
                const recipeId = recipes[i];
                const recipe = recipesToUse.hasOwnProperty( recipeId ) ? recipesToUse[recipeId] : {};
                const recipeNutrition = recipe.hasOwnProperty( 'nutrition' ) ? recipe['nutrition'] : {};

                if ( recipeNutrition.hasOwnProperty( nutritionField ) && recipeNutrition[ nutritionField ] ) {
                    let valueToAdd = recipeNutrition[ nutritionField ];

                    if ( 'total' === wprmprc_public.settings.recipe_collections_nutrition_facts_count ) {
                        if ( recipesServings.hasOwnProperty(i) && 0 <= recipesServings[i] ) {
                            valueToAdd *= recipesServings[i];
                        }
                    } else if ( recipesServings.hasOwnProperty(i) && 0 === recipesServings[i] ) {
                        valueToAdd = 0;
                    }

                    nutritionFields[ nutritionField ] += valueToAdd;
                }
            }

            // Add values for all custom recipes and ingredients.
            for ( let i = 0; i < custom.length; i++ ) {
                const itemId = custom[i];
                const item = this.props.items.find((item) => itemId === item.id);

                if ( item ) {
                    const itemNutrition = item.hasOwnProperty( 'nutrition' ) ? item['nutrition'] : {};

                    if ( itemNutrition.hasOwnProperty( nutritionField ) && itemNutrition[ nutritionField ] ) {
                        let value = parseFloat( itemNutrition[ nutritionField ] );

                        if ( ! isNaN( value ) ) {
                            // If nutrition ingredient also take amount into account.
                            if ( 'nutrition-ingredient' === item.type ) {
                                const amountNew = parseFloat( item.amount );
                                const amountOriginal = parseFloat( item.amountOriginal );

                                if ( ! isNaN( amountNew ) && 0 < amountOriginal && amountNew !== amountOriginal ) {
                                    value = value * ( amountNew / amountOriginal );
                                } else if ( 0.0 === amountNew ) {
                                    value = 0;
                                }
                            }

                            // Check servings.
                            const servings = item.hasOwnProperty( 'servings' ) ? item.servings : 1;

                            if ( 'total' === wprmprc_public.settings.recipe_collections_nutrition_facts_count ) {
                                if ( 0 <= servings ) {
                                    value *= servings;
                                }
                            } else if ( 0 === servings ) {
                                value = 0;
                            }

                            nutritionFields[ nutritionField ] += value;
                        }
                    }
                }
            }
        }

        // Round total values.
        for ( let nutritionField in nutritionFields ) {
            if ( nutritionFields.hasOwnProperty( nutritionField ) && 0 < nutritionFields[nutritionField] ) {
                nutritionFields[nutritionField] = formatQuantity( nutritionFields[nutritionField], wprmprc_public.settings.recipe_collections_nutrition_facts_round_to_decimals );
            }
        }

        // Check if there is a calculated value.
        let hasCalculatedValue = false;
        for ( let nutritionField in nutritionFields ) {
            if ( wprmprc_public.labels.nutrition_fields[nutritionField].hasOwnProperty( 'type' ) && 'calculated' === wprmprc_public.labels.nutrition_fields[nutritionField].type ) {
                hasCalculatedValue = true;
                break;
            }
        }

        // Need to get through API if there is a calculated value.
        if ( hasCalculatedValue ) {
            Api.getCalculated(nutritionFields).then((data) => {
                if ( data ) {
                    if ( Object.keys( data.calculated ).length > 0 ) {
                        nutritionFields = {
                            ...nutritionFields,
                            ...data.calculated,
                        }
                    }
                }

                this.setState({
                    loadingNutrition: false,
                    nutrition: nutritionFields,
                });
            });
        } else {
            this.setState({
                loadingNutrition: false,
                nutrition: nutritionFields,
            });
        }
    }

    render() {
        if ( ! wprmprc_public.settings.recipe_collections_nutrition_facts || 0 === wprmprc_public.settings.recipe_collections_nutrition_facts_fields.length ) {
            return null;
        }

        return (
            <div className="wprmprc-collection-column-nutrition">
                <div className="wprmprc-collection-column-nutrition-header">{
                    'total' === wprmprc_public.settings.recipe_collections_nutrition_facts_count
                    ?
                    __wprm( 'Nutrition Facts' )
                    :
                    __wprm( 'Nutrition Facts (per serving)' )
                }</div>
                <div className="wprmprc-collection-column-nutrition-fields">
                    {
                        this.state.loadingNutrition
                        ?
                        <Loader />
                        :
                        <Fragment>
                            {
                                Object.keys( wprmprc_public.labels.nutrition_fields ).map((nutrient) => {
                                    if ( this.state.nutrition.hasOwnProperty( nutrient ) ) {
                                        const options = wprmprc_public.labels.nutrition_fields[nutrient];
                                        const value = this.state.nutrition[ nutrient ];

                                        return (
                                            <div className="wprmprc-collection-column-nutrition-field" key={ nutrient }>
                                                <div className="wprmprc-collection-column-nutrition-field-label">{ options.label }</div>
                                                <div className="wprmprc-collection-column-nutrition-field-value-container">
                                                    <span className="wprmprc-collection-column-nutrition-field-value">{ value }</span>
                                                    <span className="wprmprc-collection-column-nutrition-field-unit">{ options.unit }</span>
                                                </div>
                                            </div>
                                        )
                                    }
                                })
                            }
                        </Fragment>
                    }
                </div>
            </div>
        );
    }
}