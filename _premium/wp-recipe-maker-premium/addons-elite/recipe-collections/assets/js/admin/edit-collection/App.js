import React, { Component, Fragment } from 'react';
import { Switch, Route, Redirect } from 'react-router-dom';

import '../../../css/admin/manage.scss';

import Api from 'Shared/Api';
import Collection from '../../public/recipe-collections/collection';

export default class App extends Component {

    constructor(props) {
        super(props);

        const modal = props.hasOwnProperty( 'modal' ) ? props.modal : false;

        this.state = {
            saving: false,
            savedCollection: JSON.parse(JSON.stringify(wprmprc_admin.collection)),
            collection: JSON.parse(JSON.stringify(wprmprc_admin.collection)),
            recipes: {},
            modal,
        }
    }

    componentDidMount() {
        window.addEventListener( 'beforeunload', this.beforeWindowClose.bind(this) );
    }
    
    componentWillUnmount() {
        window.removeEventListener( 'beforeunload', this.beforeWindowClose.bind(this) );
    }

    beforeWindowClose(event) {
        if ( this.changesMade() ) {
            return false;
        }
    }

    cleanUpCollection( collection ) {
        let columnsGroups = [];
        let nbrItems = 0;

        // Find all existing column-group combinations.
        for ( let column of collection.columns ) {
            for ( let group of collection.groups ) {
                columnsGroups.push(`${column.id}-${group.id}`);
            }
        }

        for ( let columnGroup of Object.keys( collection.items ) ) {            
            if ( ! columnsGroups.includes( columnGroup ) ) {
                delete collection.items[ columnGroup ];
            } else {
                nbrItems += collection.items[ columnGroup ].length;
            }
        }

        collection.nbrItems = nbrItems;

        // Make sure 1 group always exists.
        if ( 0 === collection.groups.length ) {
            collection.groups = [{
                id: 0,
                name: '',
            }]
        }

        return { ...collection };
    }

    onChangeCollection( type, id, newCollection ) {
        const collection = this.cleanUpCollection({ ...this.state.collection, ...newCollection });

        this.setState({
            collection
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

    onSaveChanges() {
        this.setState({
            saving: true,
        });

        fetch(`${wprmp_public.endpoints.collections}/${this.state.collection.id}`, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': wprm_public.api_nonce,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                collection: this.state.collection,
            }),
        }).then((response) => {
            if ( response.ok ) {
                this.setState({
                    savedCollection: JSON.parse(JSON.stringify(this.state.collection)),
                    saving: false,
                })
            } else {
                this.setState({
                    saving: false,
                });
                alert('Something went wrong. Please try again.');
            }
        });
    }

    changesMade() {
        return JSON.stringify(this.state.collection) !== JSON.stringify(this.state.savedCollection);
    }

    render() {
        
        return (
            <Fragment>
                <div className="wprmprc-admin-collection-actions">
                    <div
                        className="button button-primary button-compact"
                        disabled={ this.state.saving || ! this.changesMade() }
                        onClick={this.onSaveChanges.bind(this)}
                    >Save Collection</div>
                    <div
                        className="button button-secondary button-compact"
                        onClick={() => {
                            Api.collection.reload(this.state.collection.id).then(() => {
                                location.reload();
                            });
                        }}
                        disabled={ this.state.saving || this.changesMade() }
                    >Reload Recipes in Collection</div>
                    <div
                        className="button button-secondary button-compact"
                        onClick={() => {
                            if ( this.changesMade() ) {
                                this.setState({
                                    collection: JSON.parse(JSON.stringify(this.state.savedCollection)),
                                })
                            } else {
                                location.href = wprmprc_admin.manage_url;
                            }
                        }}
                        disabled={ this.state.saving }
                    >{ this.changesMade() ? 'Cancel Changes' : 'Go Back' }</div>
                </div>
                <input
                    className="wprmprc-admin-collection-name"
                    type="text"
                    value={this.state.collection.name}
                    placeholder="Name your collection..."
                    onChange={(e) => {
                        this.setState({
                            collection: {
                                ...this.state.collection,
                                name: e.target.value,
                            }
                        })
                    }}
                />
                <Switch>
                    <Route render={() =>
                        <Collection
                            layout={ wprmprc_public.settings.recipe_collections_appearance_layout }
                            modal={this.state.modal}
                            collections={false}
                            type={'admin'}
                            collection={this.state.collection}
                            onChangeCollection={this.onChangeCollection.bind(this)}
                            recipes={this.state.recipes}
                            onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                        />
                    } />
                </Switch>
            </Fragment>
        );
    }
}