import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import Button from '../../../shared/Button';
import { __wprm } from 'Shared/Translations';
import Api from '../general/Api';
import Loader from '../general/Loader';
import AddToCollection from '../../add-to-collection';
import AddToShoppingList from '../../add-to-shopping-list';

import '../../../../css/public/recipe.scss';
class Recipe extends Component {

    constructor(props) {
        super(props);

        if ( ! props.recipe.hasOwnProperty('html') || ! props.recipe.html ) {
            this.getRecipeHtml(props.recipe.id);
        }

        this.initRecipeFeatures = this.initRecipeFeatures.bind(this);
    }

    componentDidMount() {
        AddToCollection.checkInbox(this.props.recipe.id);
        AddToShoppingList.checkTemp(this.props.recipe.id);
        this.initRecipeFeatures();
    }

    componentDidUpdate() {
        AddToCollection.checkInbox(this.props.recipe.id);
        AddToShoppingList.checkTemp(this.props.recipe.id);
        this.initRecipeFeatures();
    }

    initRecipeFeatures() {
        if ( this.props.recipe.html ) {
            if ( window.WPRecipeMaker && window.WPRecipeMaker.hasOwnProperty( 'recipe' ) ) {
                window.WPRecipeMaker.recipe.initFeatures({
                    id: this.props.recipe.id,
                    servings: this.props.servings ? this.props.servings : 0,
                });
            }

            // Trigger init event.
            document.dispatchEvent( new Event( 'wprmCollectionRecipeInit' ) );
        }
    }

    getRecipeHtml(recipeId) {
        Api.getRecipe(recipeId).then((recipe) => {
            let recipes = {}
            recipes[recipeId] = recipe;

            this.props.onUpdateRecipes(recipes);
        });
    }

    render() {
        const { type, collection, shoppingList, recipe } = this.props;

        if ( recipe && recipe.javascript ) {
            for ( let key of Object.keys( recipe.javascript ) ) {
                window[key] = recipe.javascript[ key ];
            }
        }
        
        return (
            <Fragment>
                <div className="wprmprc-container-header">
                    <Button
                        tag="span"
                        className="wprmprc-header-link"
                        onClick={() => {
                            if ( 'shopping' === type ) {
                                this.props.history.push(`/shopping-list/${shoppingList}`);
                            } else if ( 'inbox' === type ) {
                                this.props.history.push(`/collection/inbox/`);
                            } else if ( 'shared' === type ) {
                                this.props.history.push(`/share/${collection.sharedEncoded}`);
                            } else {
                                this.props.history.push(`/collection/${type}/${collection.id}`);
                            }
                        }}
                    >{ 'shared' === this.props.type && `${ __wprm( 'Shared Collection:' ) } ` }{ collection ? collection.name : __wprm( 'Go Back' ) }</Button>
                    <span className="wprmprc-header-link-separator">&gt;</span>
                    <span className="wprmprc-container-header-name">{ __wprm( 'Recipe' ) }</span>
                </div>
                <div className="wprmprc-recipe">
                    {
                        recipe.html
                        ?
                        <div dangerouslySetInnerHTML={{__html: recipe.html}} />
                        :
                        <Loader />
                    }
                </div>
            </Fragment>
        );
    }
}

export default withRouter(Recipe);