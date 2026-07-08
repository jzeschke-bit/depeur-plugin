import React, { Component } from 'react';
import { Switch, Route, Redirect, withRouter } from 'react-router-dom';
import Hashids from 'hashids'

import { __wprm } from 'Shared/Translations';

import Api from './general/Api';
import Loader from './general/Loader';
import Collection from './collection';
import Overview from './overview';
import Recipe from './recipe';
import ShoppingList from './shopping-list';

import '../../../css/public/classic/layout.scss';
import '../../../css/public/grid/layout.scss';

class App extends Component {

    constructor(props) {
        super(props);

        const modal = props.hasOwnProperty( 'modal' ) ? props.modal : false;

        this.state = {
            layout: wprmprc_public.settings.recipe_collections_appearance_layout,
            sharedCollections: {},
            collections: Api.getCollections(),
            recipes: {},
            collectionHistories: {},
            modal,
        } 
    }

    getCollectionHistoryKey( type, collectionId, extraIdentifier = false ) {
        if ( extraIdentifier ) {
            return `${ type }-${ extraIdentifier }`;
        }

        return `${ type }-${ collectionId }`;
    }

    getCollectionHistory( type, collectionId, extraIdentifier = false ) {
        const key = this.getCollectionHistoryKey( type, collectionId, extraIdentifier );
        const history = this.state.collectionHistories.hasOwnProperty( key ) ? this.state.collectionHistories[ key ] : false;

        return history ? JSON.parse( JSON.stringify( history ) ) : false;
    }

    onChangeCollectionHistory( key, historyState ) {
        this.setState( ( prevState ) => {
            const nextCollectionHistories = {
                ...prevState.collectionHistories,
            };

            if ( historyState && 'object' === typeof historyState ) {
                nextCollectionHistories[ key ] = JSON.parse( JSON.stringify( historyState ) );
            } else {
                delete nextCollectionHistories[ key ];
            }

            return {
                collectionHistories: nextCollectionHistories,
            };
        } );
    }

    componentDidMount() {
        // Listen for collections refresh events to update state with refreshed data.
        this.collectionsRefreshHandler = ( event ) => {
            if ( event.detail && event.detail.collections ) {
                // Merge refreshed items into current state instead of replacing it.
                // This preserves any user changes made between refresh start and completion.
                const refreshedCollections = JSON.parse( JSON.stringify( event.detail.collections ) );
                const currentCollections = this.state.collections;
                
                // Deep merge function to update only refreshed items while preserving user changes.
                // This ensures user changes made during refresh are not lost.
                const mergeRefreshedItems = ( current, refreshed ) => {
                    if ( ! current || ! refreshed ) {
                        return refreshed || current;
                    }
                    
                    // If both are objects (not arrays), merge them recursively.
                    if ( typeof current === 'object' && typeof refreshed === 'object' && 
                         ! Array.isArray( current ) && ! Array.isArray( refreshed ) ) {
                        const merged = { ...current };
                        
                        // Iterate through all keys in refreshed object.
                        for ( const key in refreshed ) {
                            if ( refreshed.hasOwnProperty( key ) ) {
                                // Special handling for items objects - these contain columnGroup keys with item arrays.
                                if ( key === 'items' && typeof refreshed[ key ] === 'object' && typeof current[ key ] === 'object' ) {
                                    // Merge items object: for each columnGroup, merge the item arrays.
                                    merged[ key ] = { ...current[ key ] };
                                    
                                    for ( const columnGroup in refreshed[ key ] ) {
                                        if ( refreshed[ key ].hasOwnProperty( columnGroup ) ) {
                                            const currentItems = current[ key ][ columnGroup ];
                                            const refreshedItems = refreshed[ key ][ columnGroup ];
                                            
                                            if ( Array.isArray( currentItems ) && Array.isArray( refreshedItems ) ) {
                                                // Merge item arrays by matching recipeId/ingredientId.
                                                merged[ key ][ columnGroup ] = mergeItemArray( currentItems, refreshedItems );
                                            } else {
                                                // Use refreshed if current doesn't have this columnGroup.
                                                merged[ key ][ columnGroup ] = refreshedItems || currentItems;
                                            }
                                        }
                                    }
                                } else {
                                    // Recursively merge other object properties.
                                    merged[ key ] = mergeRefreshedItems( current[ key ], refreshed[ key ] );
                                }
                            }
                        }
                        
                        return merged;
                    }
                    
                    // For arrays (that aren't item arrays), prefer current to preserve user changes.
                    if ( Array.isArray( current ) && Array.isArray( refreshed ) ) {
                        // Use current array to preserve any additions/removals user made.
                        return current;
                    }
                    
                    // For primitive values, prefer current to preserve user changes.
                    return current;
                };
                
                // Helper function to merge item arrays by matching IDs.
                const mergeItemArray = ( currentItems, refreshedItems ) => {
                    // Start with current items to preserve order and any additions.
                    const merged = [ ...currentItems ];
                    
                    // Update items that exist in both arrays by matching recipeId/ingredientId.
                    refreshedItems.forEach( ( refreshedItem ) => {
                        if ( ! refreshedItem || typeof refreshedItem !== 'object' ) {
                            return;
                        }
                        
                        const itemId = refreshedItem.recipeId || refreshedItem.ingredientId;
                        if ( ! itemId ) {
                            return;
                        }
                        
                        // Find matching item in current array.
                        const currentIndex = merged.findIndex( ( item ) => {
                            if ( ! item || typeof item !== 'object' ) {
                                return false;
                            }
                            return ( item.recipeId && item.recipeId === refreshedItem.recipeId ) ||
                                   ( item.ingredientId && item.ingredientId === refreshedItem.ingredientId );
                        } );
                        
                        if ( currentIndex >= 0 ) {
                            // Merge: use refreshed data but preserve custom fields from current item.
                            const currentItem = merged[ currentIndex ];
                            merged[ currentIndex ] = {
                                ...refreshedItem,
                                // Preserve servings and id from current item if they exist.
                                servings: currentItem.servings !== undefined ? currentItem.servings : refreshedItem.servings,
                                id: currentItem.id !== undefined ? currentItem.id : refreshedItem.id,
                            };
                        }
                        // Note: If item not found in current, it means user removed it, so we don't add it back.
                    } );
                    
                    return merged;
                };
                
                const mergedCollections = mergeRefreshedItems( currentCollections, refreshedCollections );
                
                // Update state with merged collections.
                this.setState({
                    collections: mergedCollections
                });
            }
        };
        
        window.addEventListener( 'wprmprc-collections-refreshed', this.collectionsRefreshHandler );

        // Check if we should add a collection for this user.
        if ( window.hasOwnProperty( 'wprmprc_public_collection_save' ) ) {
            // Add to this user's collections.
            this.onAddCollection( 'saved', false, window.wprmprc_public_collection_save );

            // Prevent from saving again.
            window.wprmprc_public_collection_save = false;

            // Remove query strings to prevent reloading from adding it again.
            let query = window.location.search;

            const hashids = new Hashids( 'wp-recipe-maker' );
            const encoded = hashids.encode( window.wprmprc_public_collection_save.id );

            query = query.replace( `save=${encoded}`, '' );
            query = query.replace( '?&', '?' );
            query = '?' === query ? '' : query;

            window.history.replaceState( {}, document.title, window.location.pathname + query );

            // Open this collection.
            let userCollections = [ ...this.state.collections.user ];
            let maxId = Math.max.apply( Math, userCollections.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;

            this.props.history.push( `/collection/user/${ maxId + 1 }` );
        }

        // Check if we should add a recipe for this user.
        if ( window.hasOwnProperty( 'wprmprc_public_collection_save_recipe' ) ) {
            let recipe = window.wprmprc_public_collection_save_recipe.hasOwnProperty( 'data' ) ? JSON.parse( JSON.stringify( window.wprmprc_public_collection_save_recipe.data ) ): false;

            if ( recipe ) {
                // Add recipe to inbox.
                let items = JSON.parse( JSON.stringify( this.state.collections.inbox.items ) );

                recipe.id = items['0-0'].length;
                items['0-0'].push( recipe );

                this.onChangeCollection( 'inbox', 0, { items } );

                // Prevent from saving again.
                window.wprmprc_public_collection_save_recipe = false;

                // Remove query strings to prevent reloading from adding it again.
                let query = window.location.search;

                query = query.replace( `add=${ recipe.recipeId }`, '' );
                query = query.replace( '?&', '?' );
                query = '?' === query ? '' : query;

                window.history.replaceState( {}, document.title, window.location.pathname + query );

                // Open inbox.
                this.props.history.push( `/collection/inbox/` );
            }
        }
    }

    componentWillUnmount() {
        // Clean up event listener.
        if ( this.collectionsRefreshHandler ) {
            window.removeEventListener( 'wprmprc-collections-refreshed', this.collectionsRefreshHandler );
        }
    }

    componentDidUpdate( prevProps, prevState ) {
        if ( JSON.stringify(this.state.collections) !== JSON.stringify(prevState.collections) ) {
            let collectionsToSave = JSON.parse( JSON.stringify( this.state.collections ) );
            collectionsToSave.updated = Math.floor( Date.now() / 1000 );

            Api.saveCollections( collectionsToSave );
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
        if ( 'inbox' === type ) {
            const inbox = this.cleanUpCollection({ ...this.state.collections.inbox, ...newCollection });

            this.setState({
                collections: {
                    ...this.state.collections,
                    inbox,
                }
            });
        } else if ( 'user' === type ) {
            const index = this.state.collections[type].findIndex((collection) => id === collection.id);

            if ( -1 !== index ) {
                let userCollections = [ ...this.state.collections.user ];
                userCollections[index] = this.cleanUpCollection({
                    ...userCollections[index],
                    ...newCollection,
                });

                this.setState({
                    collections: {
                        ...this.state.collections,
                        user: userCollections,
                    }
                });
            }
        }
    }

    onChangeSharedCollection( encoded, newCollection ) {
        let sharedCollections = { ...this.state.sharedCollections };

        if ( false === newCollection ) {
            sharedCollections[ encoded ] = false;
        } else {
            sharedCollections[ encoded ] = this.cleanUpCollection( {
                ...this.state.sharedCollections[ encoded ],
                ...newCollection,
                sharedEncoded: encoded,
            } );
        }

        this.setState({
            sharedCollections,
        });
    }

    onAddCollection( type, cloneId = false, collection = false, placement =  "bottom" ) {
        if ( 'user' === type || 'saved' === type ) {
            let userCollections = JSON.parse( JSON.stringify( this.state.collections.user ) );
            let maxId = Math.max.apply( Math, userCollections.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;

            // Find collection to clone.
            let cloneIndex = false
            if ( false !== cloneId ) {
                cloneIndex = userCollections.findIndex( (collection) => cloneId === collection.id );
            }

            let newCollection;
            if ( false !== cloneIndex ) {
                newCollection = JSON.parse( JSON.stringify( userCollections[cloneIndex] ) );
                newCollection.id = maxId + 1;
            } else {
                newCollection = {
                    id: maxId + 1,
                    name: '',
                    nbrItems: 0,
                    columns: [ { id: 0, name: __wprm( 'Recipes' ) } ],
                    groups: [ { id: 0, name: '' } ],
                    items: {},
                    created: Math.floor( Date.now() / 1000 ),
                };
            }

            // If adding saved collection, use its fields. Make sure ID stays.
            if ( false !== collection ) {
                newCollection = {
                    ...newCollection,
                    ...collection,
                    id: newCollection.id,
                }
            }

            if ( false !== cloneIndex ) {
                userCollections.splice(cloneIndex + 1, 0, newCollection);
            } else if ( 'top' === placement ) {
                userCollections.unshift(newCollection);
            } else {
                userCollections.push(newCollection);
            }

            this.setState({
                collections: {
                    ...this.state.collections,
                    user: userCollections,
                }
            });
        }
    }

    onDeleteCollection( type, id ) {
        if ( 'user' === type ) {
            const index = this.state.collections[type].findIndex((collection) => id === collection.id);

            if ( -1 !== index ) {
                let userCollections = [ ...this.state.collections.user ];
                userCollections.splice(index, 1);

                const collectionHistoryKey = this.getCollectionHistoryKey( type, id );

                this.setState({
                    collections: {
                        ...this.state.collections,
                        user: userCollections,
                    },
                    collectionHistories: Object.keys( this.state.collectionHistories ).reduce( ( histories, key ) => {
                        if ( key !== collectionHistoryKey ) {
                            histories[ key ] = this.state.collectionHistories[ key ];
                        }

                        return histories;
                    }, {} ),
                });
            }
        }
    }

    onReorderCollection( type, oldIndex, newIndex ) {
        if ( 'user' === type ) {
            let userCollections = [ ...this.state.collections.user ];

            const collection = userCollections.splice(oldIndex, 1)[0];
            userCollections.splice(newIndex, 0, collection);

            this.setState({
                collections: {
                    ...this.state.collections,
                    user: userCollections,
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

    getSharedCollection( encoded ) {
        if ( this.state.sharedCollections.hasOwnProperty( encoded ) ) {
            return this.state.sharedCollections[ encoded ];
        } else {
            const hashids = new Hashids( 'wp-recipe-maker' );
            const decoded = hashids.decode( encoded );

            if ( decoded ) {
                const userId = decoded[0];
                const collectionId = 1 < decoded.length ? decoded[1] : 'inbox';

                Api.getSharedCollection( userId, collectionId ).then( ( response ) => {
                    // Add retrieved collection to shared collections.
                    this.onChangeSharedCollection( encoded, response );
                });
    
                return true; // Return true to indicate that we're loading the collection.
            }
        }

        return false;
    }

    render() {
        const getCollection = ( type, collectionId ) => {
            let collection = false;
            if ( this.state.collections.hasOwnProperty(type) ) {
                if ( 'inbox' === type ) {
                    collection = this.state.collections.inbox;
                } else {
                    collection = this.state.collections[type].find((collection) => collectionId === collection.id);
                }
            }

            return collection;
        }

        const collectionRoute = ( type, collectionId ) => {
            // Special case: need to retrieve shared collection.
            if ( 'shared' === type ) {
                const collection = this.getSharedCollection( collectionId );

                if ( false === collection ) {
                    // Collection not found, redirect.
                    alert( __wprm( 'Shared collection not found.' ) );

                    return (
                        <Redirect to='/' />
                    );
                } else {
                    if ( true === collection ) {
                        return (
                            <Loader />
                        );
                    } else {
                        const collectionHistoryKey = this.getCollectionHistoryKey( 'shared', collection.id, collectionId );

                        return (
                            <Collection
                                layout={this.state.layout}
                                modal={this.state.modal}
                                collections={false}
                                type="shared"
                                collection={collection}
                                onChangeCollection={( type, id, newCollection ) => {
                                    this.onChangeSharedCollection( collectionId, newCollection );
                                } }
                                recipes={this.state.recipes}
                                onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                                historyState={ this.getCollectionHistory( 'shared', collection.id, collectionId ) }
                                onHistoryStateChange={ ( historyState ) => {
                                    this.onChangeCollectionHistory( collectionHistoryKey, historyState );
                                } }
                            />
                        );
                    }
                }
            }

            // Just need to load specific collection.
            const collection = getCollection( type, collectionId );

            if ( collection ) {
                const collectionHistoryKey = this.getCollectionHistoryKey( type, collection.id );

                return (
                    <Collection
                        layout={this.state.layout}
                        modal={this.state.modal}
                        collections={this.state.collections}
                        type={type}
                        collection={collection}
                        onChangeCollection={this.onChangeCollection.bind(this)}
                        recipes={this.state.recipes}
                        onUpdateRecipes={this.onUpdateRecipes.bind(this)}
                        historyState={ this.getCollectionHistory( type, collection.id ) }
                        onHistoryStateChange={ ( historyState ) => {
                            this.onChangeCollectionHistory( collectionHistoryKey, historyState );
                        } }
                    />
                )
            } else {
                return (
                    <Redirect to='/' />
                )
            }
        }

        const recipeRoute = ( type, collectionId, recipeId, servings ) => {
            let collection = false;

            if ( 'shared' === type ) {
                collection = this.getSharedCollection( collectionId );

                if ( true === collection ) {
                    return (
                        <Loader />
                    );
                }
            } else if ( 'shopping' !== type ) {
                collection = getCollection( type, collectionId );
            }

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
                    } else if ( 'shared' === type ) {
                        return (
                            <Redirect to={`/share/${collectionId}`} />
                        );
                    } else {
                        return (
                            <Redirect to={`/collection/${type}/${collectionId}`} />
                        );
                    }
                } else {
                    return (
                        <Recipe
                            layout={this.state.layout}
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

        const shoppingListRoute = ( shoppingListId, type, collectionId ) => {
            if ( false !== shoppingListId ) {
                return (
                    <ShoppingList
                        layout={this.state.layout}
                        uid={ shoppingListId }
                        collections={this.state.collections}
                        type="shopping"
                        collection={false}
                        onChangeCollection={this.onChangeCollection.bind(this)}
                    />
                );
            } else if ( 'shared' === type ) {
                const collection = this.getSharedCollection( collectionId );

                if ( false === collection ) {
                    // Collection not found, redirect.
                    alert( __wprm( 'Shared collection not found.' ) );

                    return (
                        <Redirect to='/' />
                    );
                } else {
                    if ( true === collection ) {
                        return (
                            <Loader />
                        );
                    } else {
                        return (
                            <ShoppingList
                                layout={this.state.layout}
                                uid={ false }
                                collections={false}
                                type={type}
                                collection={collection}
                                onChangeCollection={( type, id, newCollection ) => {
                                    this.onChangeSharedCollection( collectionId, newCollection );
                                } }
                            />
                        );
                    }
                }
            } else {
                const collection = getCollection( type, collectionId );

                if ( collection && wprmprc_public.settings.recipe_collections_shopping_list ) {
                    return (
                        <ShoppingList
                            layout={this.state.layout}
                            uid={ false }
                            collections={this.state.collections}
                            type={type}
                            collection={collection}
                            onChangeCollection={this.onChangeCollection.bind(this)}
                        />
                    )
                }
            }

            return (
                <Redirect to='/' />
            )
        }

        return (
            <Switch>
                <Route path="/collection/inbox/:recipeId/:servings?" render={(props) => {
                    const { recipeId, servings } = props.match.params;
                    return recipeRoute( 'inbox', null, parseInt( recipeId ), servings );
                }} />
                <Route path="/collection/inbox" render={() => {
                    return collectionRoute( 'inbox', null );
                }} />
                <Route path="/share/:encoded/:recipeId/:servings?" render={(props) => {
                    const { encoded, recipeId, servings } = props.match.params;
                    return recipeRoute( 'shared', encoded , parseInt( recipeId ), servings );
                }} />
                <Route path="/collection/:type/:collectionId/:recipeId/:servings?" render={(props) => {
                    const { type, collectionId, recipeId, servings } = props.match.params;
                    return recipeRoute( type, parseInt( collectionId ), parseInt( recipeId ), servings );
                }} />
                <Route path="/collection/:type/:collectionId" render={(props) => {
                    const { type, collectionId } = props.match.params;
                    return collectionRoute( type, parseInt( collectionId ) );
                }} />
                <Route path="/shopping-list/inbox" render={() => {
                    return shoppingListRoute( false, 'inbox', null );
                }} />
                <Route path="/shopping-list/share/:encoded" render={(props) => {
                    const { encoded } = props.match.params;
                    return shoppingListRoute( false, 'shared', encoded );
                }} />
                <Route path="/shopping-list/:shoppingListId/recipe/:recipeId/:servings?" render={(props) => {
                    const { shoppingListId, recipeId, servings } = props.match.params;
                    return recipeRoute( 'shopping', shoppingListId, parseInt( recipeId ), servings );
                }} />
                <Route path="/shopping-list/:type/:collectionId" render={(props) => {
                    const { type, collectionId } = props.match.params;
                    return shoppingListRoute( false, type, parseInt( collectionId ) );
                }} />
                <Route path="/shopping-list/:shoppingListId" render={(props) => {
                    const { shoppingListId } = props.match.params;
                    return shoppingListRoute( shoppingListId, false, false );
                }} />
                <Route path="/share/:encoded" render={(props) => {
                    const { encoded } = props.match.params;
                    return collectionRoute( 'shared', encoded );
                }} />
                <Route render={() =>
                    <Overview
                        layout={this.state.layout}
                        collections={this.state.collections}
                        onChangeCollection={this.onChangeCollection.bind(this)}
                        onDeleteCollection={this.onDeleteCollection.bind(this)}
                        onAddCollection={this.onAddCollection.bind(this)}
                        onReorderCollection={this.onReorderCollection.bind(this)}
                    />
                } />
            </Switch>
        );
    }
}
export default withRouter(App);
