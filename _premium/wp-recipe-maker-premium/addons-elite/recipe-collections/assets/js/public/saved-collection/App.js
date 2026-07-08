import React, { Component } from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';

import '../../../css/public/classic/layout.scss';
import '../../../css/public/grid/layout.scss';

import Collection from '../recipe-collections/collection';
import Recipe from '../recipe-collections/recipe';
import ShoppingList from '../recipe-collections/shopping-list';

export default class App extends Component {

    constructor(props) {
        super(props);

        const collection = window.hasOwnProperty( `wprmprc_public_collection_${props.id}` ) ? window[`wprmprc_public_collection_${props.id}`] : false;
        
        // Check if shopping list is in localStorage when not logged in.
        if ( ! collection.shoppingList ) {
            const localShoppingLists = localStorage.getItem( 'wprm-shopping-lists' );
            const shoppingLists = localShoppingLists ? JSON.parse( localShoppingLists ) : {};

            if ( shoppingLists.hasOwnProperty( `saved-${props.id}` ) ) {
                collection.shoppingList = shoppingLists[`saved-${props.id}`];
            }
        }

        if ( '' === collection.shoppingList ) {
            collection.shoppingList = false;
        }

        this.state = {
            layout: wprmprc_public.settings.recipe_collections_appearance_layout,
            collection: JSON.parse(JSON.stringify(collection)),
            recipes: {},
        }
    }

    onChangeCollection( type, id, newCollection ) {
        // Update shopping list in local storage.
        if ( newCollection.hasOwnProperty( 'shoppingList' ) && newCollection.shoppingList !== this.state.collection.shoppingList ) {
            const localShoppingLists = localStorage.getItem( 'wprm-shopping-lists' );
            let shoppingLists = localShoppingLists ? JSON.parse( localShoppingLists ) : {};

            shoppingLists[ `saved-${id}` ] = newCollection.shoppingList;
            localStorage.setItem( 'wprm-shopping-lists', JSON.stringify( shoppingLists ) );
        }

        this.setState({
            collection: {
                ...this.state.collection,
                ...newCollection
            }
        });
    }

    onUpdateRecipes( recipes ) {
        let newRecipes = JSON.parse(JSON.stringify(this.state.recipes));

        for ( let recipeId in recipes ) {
            if ( recipes.hasOwnProperty( recipeId ) ) {
                const oldRecipe = newRecipes.hasOwnProperty(recipeId) ? newRecipes[recipeId] : {};
                newRecipes[recipeId] = {
                    ...oldRecipe,
                    ...recipes[recipeId],
                }
            }
        }

        this.setState({
            recipes: newRecipes,
        });
    }

    render() {
        if ( false === this.state.collection ) {
            return null;
        }

        const recipeRoute = ( type, collectionId, recipeId, servings ) => {
            const collection = 'shopping' === type ? false : true;
            const shoppingList = 'shopping' === type ? collectionId : false;

            if ( 'recipe' === wprmprc_public.settings.recipe_collections_recipe_click && ( collection || shoppingList ) ) {
                let recipe = this.state.recipes.hasOwnProperty(recipeId) ? this.state.recipes[ recipeId ] : {};
                
                // Make sure ID is passed along.
                recipe.id = recipeId;

                if ( false === recipe.html ) {
                    if ( 'shopping' === type ) {
                        return (
                            <Redirect to={`/shopping-list/${collectionId}`} />
                        );
                    } else {
                        return (
                            <Redirect to={`/collection/${type}/${collectionId}`} />
                        );
                    }
                } else {
                    return (
                        <Recipe
                            type={type}
                            collection={collection}
                            shoppingList={shoppingList}
                            recipe={recipe}
                            servings={servings}
                            onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                        />
                    );
                }
            } else {
                return (
                    <Redirect to='/' />
                );
            }
        }
        
        return (
            <Switch>
                <Route path="/collection/:type/:collectionId/:recipeId/:servings?" render={(props) => {
                    const { type, collectionId, recipeId, servings } = props.match.params;

                    if ( 'recipe' === wprmprc_public.settings.recipe_collections_recipe_click ) {
                        let recipe = this.state.recipes.hasOwnProperty(recipeId) ? this.state.recipes[ recipeId ] : {};
                
                        // Make sure ID is passed along.
                        recipe.id = recipeId;
        
                        if ( false === recipe.html ) {
                            return (
                                <Redirect to={`/collection/${type}/${collectionId}`} />
                            );
                        } else {
                            return (
                                <Recipe
                                    layout={this.state.layout}
                                    type={type}
                                    collection={this.state.collection}
                                    recipe={recipe}
                                    servings={servings}
                                    onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                                />
                            );
                        }
                    } else {
                        return (
                            <Redirect to='/' />
                        );
                    }
                }} />
                <Route path="/shopping-list/:shoppingListId/recipe/:recipeId/:servings?" render={(props) => {
                    const { shoppingListId, recipeId, servings } = props.match.params;
                    return recipeRoute( 'shopping', shoppingListId, parseInt( recipeId ), servings );
                }} />
                <Route path="/shopping-list/:type/:collectionId" render={(props) => {
                    const { type, collectionId } = props.match.params;

                    if ( wprmprc_public.settings.recipe_collections_shopping_list ) {
                        return (
                            <ShoppingList
                                layout={this.state.layout}
                                uid={ this.state.collection ? this.state.collection.shoppingList : false }
                                collections={false}
                                type={type}
                                collection={this.state.collection}
                                onChangeCollection={this.onChangeCollection.bind(this)}
                            />
                        )
                    } else {
                        return (
                            <Redirect to='/' />
                        )
                    }
                }} />
                <Route path="/shopping-list/:shoppingListId" render={(props) => {
                    const { shoppingListId } = props.match.params;

                    if ( wprmprc_public.settings.recipe_collections_shopping_list ) {
                        return (
                            <ShoppingList
                                layout={this.state.layout}
                                uid={ shoppingListId }
                                collections={false}
                                type="shopping"
                                collection={this.state.collection}
                                onChangeCollection={this.onChangeCollection.bind(this)}
                            />
                        )
                    } else {
                        return (
                            <Redirect to='/' />
                        )
                    }
                }} />
                <Route render={() =>
                    <Collection
                        layout={this.state.layout}
                        collections={false}
                        type={'saved'}
                        collection={this.state.collection}
                        onChangeCollection={this.onChangeCollection.bind(this)}
                        recipes={this.state.recipes}
                        onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                    />
                } />
            </Switch>
        );
    }
}