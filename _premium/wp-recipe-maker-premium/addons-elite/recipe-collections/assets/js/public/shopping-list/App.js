import React, { Component } from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';

import '../../../css/public/classic/layout.scss';
import '../../../css/public/grid/layout.scss';

import Api from '../recipe-collections/general/Api';
import Collection from '../recipe-collections/collection';
import Recipe from '../recipe-collections/recipe';
import ShoppingList from '../recipe-collections/shopping-list';

export default class App extends Component {

    constructor(props) {
        super(props);

        let usingTemp = false;
        let collection = false;
        let collections = false;
        if ( props.id ) {
            if ( 'temp' === props.id ) {
                usingTemp = true;
                collections = Api.getCollections();
            } else {
                collection = window.hasOwnProperty( `wprmprc_public_collection_${props.id}` ) ? window[`wprmprc_public_collection_${props.id}`] : false;
            }
        }
        
        // Check if shopping list is in localStorage when not logged in.
        if ( collection && ! collection.shoppingList ) {
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
            usingTemp,
            collections,
            collection: JSON.parse(JSON.stringify(collection)),
            recipes: {},
        }
    }

    componentDidUpdate( prevProps, prevState ) {
        if ( JSON.stringify(this.state.collections) !== JSON.stringify(prevState.collections) ) {
            if ( false !== this.state.collections ) {
                Api.saveCollections( this.state.collections );
            }
        }
    }

    onChangeCollection( type, id, newCollection ) {
        if ( this.state.usingTemp ) {
            let newCollections = JSON.parse( JSON.stringify( this.state.collections ) );
            newCollections.temp = {
                ...newCollections.temp,
                ...newCollection,
            };

            this.setState({
                collections: newCollections,
            });
        } else {
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
        if ( false === this.state.collection && false === this.state.collections ) {
            return null;
        }

        // Use saved or temp collection.
        let collection = this.state.collection;
        if ( this.state.usingTemp ) {
            collection = this.state.collections.temp;
        }
        
        return (
            <Switch>
                <Route path="/recipe/:recipeId/:servings?" render={(props) => {
                    const { recipeId, servings } = props.match.params;
                    
                    if ( 'recipe' === wprmprc_public.settings.recipe_collections_recipe_click ) {
                        let recipe = this.state.recipes.hasOwnProperty(recipeId) ? this.state.recipes[ recipeId ] : {};
                        
                        // Make sure ID is passed along.
                        recipe.id = recipeId;
        
                        if ( false === recipe.html ) {
                            return (
                                <Redirect to='/' />
                            );
                        } else {
                            return (
                                <Recipe
                                    type={ this.state.usingTemp ? 'temp' : 'shortcode' }
                                    collection={ false }
                                    shoppingList={ false }
                                    recipe={ recipe }
                                    servings={ servings }
                                    onUpdateRecipes={ this.onUpdateRecipes.bind(this) }
                                />
                            );
                        }
                    } else {
                        return (
                            <Redirect to='/' />
                        );
                    }
                }} />
                <Route path="/edit/:shoppingListId" render={(props) => {
                    const { shoppingListId } = props.match.params;

                    return (
                        <ShoppingList
                            layout={this.state.layout}
                            uid={ shoppingListId }
                            collections={false}
                            type={ this.state.usingTemp ? 'temp' : 'shortcode' }
                            collection={collection}
                            onChangeCollection={this.onChangeCollection.bind(this)}
                        />
                    );
                }} />
                <Route render={() =>
                    <ShoppingList
                        layout={this.state.layout}
                        uid={ collection.hasOwnProperty( 'shoppingList' ) ? collection.shoppingList : false }
                        collections={false}
                        type={ this.state.usingTemp ? 'temp' : 'shortcode' }
                        collection={collection}
                        onChangeCollection={this.onChangeCollection.bind(this)}
                    />
                } />
            </Switch>
        );
    }
}