import React, { Component, Fragment } from 'react';
import { withRouter, Link } from 'react-router-dom';
import Hashids from 'hashids'

import Button from '../../../shared/Button';
import Icon from '../general/Icon';
import Api from '../general/Api';
import Loader from '../general/Loader';
import { __wprm } from 'Shared/Translations';

import GridHeaderActions from './GridHeaderActions';
import Collection from './collection';
import List from './list';
import Options from './Options';

let editingThrottle = false;

class ShoppingList extends Component {

    constructor(props) {
        super(props);

        let uid = props.uid;
        if ( ! uid ) {
            uid = props.collection && props.collection.hasOwnProperty( 'shoppingList' ) ? props.collection.shoppingList : false;
        }

        const notes = wprmprc_public.settings.recipe_collections_shopping_list_options_notes ? true : false;

        this.state = {
            uid,
            shoppingList: false,
            loading: false === uid ? false : true,
            saving: false,
            shopping: false,
            regenerating: false,
            editing: false,
            options: {
                notes,
                system: 1,
            },
            outdated: false,
        }

        this.onGroupsChange = this.onGroupsChange.bind(this);
        this.onChangeEditing = this.onChangeEditing.bind(this);
        this.onPrintRecipes = this.onPrintRecipes.bind(this);
        this.onShopList = this.onShopList.bind(this);
        this.onGenerateShoppingList = this.onGenerateShoppingList.bind(this);
        this.onRegenerateShoppingList = this.onRegenerateShoppingList.bind(this);
        this.onClearAll = this.onClearAll.bind(this);
    }

    componentDidMount() {
        if ( false !== this.state.uid ) {
            this.loadShoppingList( this.state.uid );
        }
    }

    componentWillUnmount() {
        window.onbeforeunload = null;
    }

    componentDidUpdate( prevProps, prevState ) {
        let uid = this.props.uid;
        if ( ! uid ) {
            uid = this.props.collection && this.props.collection.hasOwnProperty( 'shoppingList' ) ? this.props.collection.shoppingList : false;
        }

        if ( prevState.uid !== uid ) {
            if ( false === uid ) {
                this.setState({
                    uid: false,
                    shoppingList: false,
                    loading: false,
                });
            } else {
                this.loadShoppingList( uid );
            }
        } else if ( this.state.uid ) {
            // UID is set and the same, but shopping list might have changed.
            if ( JSON.stringify( this.state.shoppingList ) !== JSON.stringify( prevState.shoppingList ) ) {
                const shoppingListToSave = JSON.parse( JSON.stringify( this.state.shoppingList ) );
                Api.saveShoppingList( this.state.uid, shoppingListToSave );
            }
        }
    }
    
    onGroupsChange( newGroups ) {
        let newShoppingList = JSON.parse( JSON.stringify( this.state.shoppingList ) );
        newShoppingList.groups = newGroups;

        this.setState({
            shoppingList: newShoppingList,
        });
    }

    onChangeEditing( editing ) {
        // Use throttle to prevent tapping issues in Safari on iOS.
        if ( ! editingThrottle ) {
            editingThrottle = true;

            // If we're enabling editing and the shopping list is empty, create an empty group first.
            if ( editing && this.state.shoppingList && this.state.shoppingList.groups && 0 === this.state.shoppingList.groups.length ) {
                const newGroups = [{
                    id: 0,
                    checked: false,
                    name: __wprm( 'Group' ),
                    ingredients: [],
                }];
                this.onGroupsChange( newGroups );
            }

            this.setState({
                editing,
            }, () => {
                setTimeout(() => {
                    editingThrottle = false;
                }, 500);
            });
        }
    }

    loadShoppingList( uid ) {
        this.setState({
            uid,
            shoppingList: false,
            loading: true,
        }, () => {
            Api.getShoppingList( uid ).then((data) => {
                if ( data ) {
                    const shoppingList = JSON.parse( JSON.stringify( data ) );

                    // Check if shopping list is outdated.
                    let outdated = false;

                    if ( false !== this.props.collection ) {
                        const currentItems = this.props.collection.items;
                        const shoppingListItems = shoppingList.collection.items;

                        if ( JSON.stringify( currentItems ) !== JSON.stringify( shoppingListItems ) ) {
                            outdated = true;
                        }
                    }

                    // Set state.
                    this.setState({
                        shoppingList,
                        loading: false,
                        outdated,
                    })
                } else {
                    // Could not load shopping list. Generate new one.
                    let newCollection = JSON.parse( JSON.stringify( this.props.collection ) );
                    newCollection.shoppingList = false;

                    this.props.onChangeCollection( this.props.type, this.props.collection.id, newCollection );
                }
            });
        });
    }

    onPrintRecipes() {
        // Get recipes to print.
        const collection = this.state.shoppingList.collection;
        let recipesToPrint = [];

        if ( collection.items ) {
            // Follow order of columns and groups.
            for ( let column of collection.columns ) {
                let inShoppingList;

                if ( column.hasOwnProperty( 'inShoppingList' ) ) {
                    inShoppingList = column.inShoppingList;
                } else {
                    // Default to false, unless there's only 1 column.
                    inShoppingList = 1 === collection.columns.length;
                }
                
                if ( inShoppingList ) {
                    for ( let group of collection.groups ) {
                        const items = collection.items[`${column.id}-${group.id}`] ? collection.items[`${column.id}-${group.id}`] : [];
    
                        for ( let item of items ) {
                            if ( 'recipe' === item.type && item.recipeId && item.servings > 0 ) {
                                recipesToPrint.push(item.recipeId);
            
                                // Problem: hashids doesn't work with decimals. Pass along as large number.
                                recipesToPrint.push( Math.floor( item.servings * Math.pow( 10, 6 ) ) );
                            }
                        }
                    }
                }
            }
        }

        // Get Print URL.
        const urlParts = wprm_public.home_url.split(/\?(.+)/);
        let printUrl = urlParts[0];

        // Encode recipes to print.
        const hashids = new Hashids( 'wp-recipe-maker' );
        const recipesToPrintEncoded = hashids.encode( recipesToPrint );

        if ( wprm_public.permalinks ) {
            printUrl += wprm_public.print_slug + '/recipes';
            printUrl += '/' + recipesToPrintEncoded;

            if ( urlParts[1] ) {
                printUrl += '?' + urlParts[1];
            }
        } else {
            printUrl += '?' + wprm_public.print_slug + '=recipes';
            printUrl += '&' + recipesToPrintEncoded;

            if ( urlParts[1] ) {
                printUrl += '&' + urlParts[1];
            }
        }

        window.open( printUrl, '_blank' );
    }

    onShopList() {
        if ( ! this.state.shopping ) {
            this.setState({
                shopping: true,
            }, () => {
                Api.shopShoppingList( this.state.uid, 'instacart' ).then((link) => {
                    this.setState({
                        shopping: false,
                    }, () => {
                        if ( link ) {
                            window.open( link, '_blank' );
                        } else {
                            alert( __wprm( 'Something went wrong. Please try again later.' ) );
                        }
                    });
                });
            });
        }
    }

    onGenerateShoppingList( skipConfirmation = false ) {
        if ( ! this.state.saving && ! this.state.regenerating ) {
            // Check if there are recipes selected for the shopping list.
            let hasRecipes = false;

            this.props.collection.columns.forEach( (column) => {
                let inShoppingList;

                if ( column.hasOwnProperty( 'inShoppingList' ) ) {
                    inShoppingList = column.inShoppingList;
                } else {
                    // Default to false, unless there's only 1 column.
                    inShoppingList = 1 === this.props.collection.columns.length;
                }
                
                if ( inShoppingList ) {
                    this.props.collection.groups.forEach( (group) => {
                        const columnGroup = `${column.id}-${group.id}`;
                        const items = this.props.collection.items[columnGroup] || [];

                        // Check if there are any items with servings > 0 that are not leftovers.
                        const validItems = items.filter( (item) => {
                            const isLeftovers = wprmprc_public.settings.recipe_collections_items_leftovers && item.hasOwnProperty( 'leftovers' ) && item.leftovers;
                            return ! isLeftovers && 0 < parseFloat( item.servings || 0 );
                        } );

                        if ( validItems.length > 0 ) {
                            hasRecipes = true;
                        }
                    } );
                }
            } );

            // If no recipes selected, ask for confirmation (unless explicitly skipped).
            if ( ! hasRecipes && ! skipConfirmation ) {
                if ( ! confirm( __wprm( 'No recipes have been selected for the shopping list. Do you want to generate an empty shopping list that you can fill manually?' ) ) ) {
                    return;
                }
            }

            // Generate shopping list.
            this.setState({
                saving: true,
            }, () => {
                window.WPRecipeMaker.analytics.registerAction( '', '', 'generate-shopping-list', {
			        collection: 'temp' === this.props.type ? 'Quick Access Shopping List' : this.props.collection.name,
		        });

                Api.generateShoppingList( this.props.type, this.props.collection, this.state.options ).then((uid) => {
                    this.setState({
                        saving: false,
                    }, () => {
                        if ( uid ) {
                            let newCollection = JSON.parse( JSON.stringify( this.props.collection ) );
                            newCollection.shoppingList = uid;

                            this.props.onChangeCollection( this.props.type, this.props.collection.id, newCollection );
                        }
                    });
                });
            });
        }
    }

    onRegenerateShoppingList() {
        if ( ! this.state.saving && confirm( __wprm( 'Are you sure you want to generate a new shopping list for this collection? You will only be able to access this shopping list again with the share link.' ) ) ) {
            // Set to regenerating to prevent immediately generating again on iOS.
            this.setState({
                regenerating: true,
            }, () => {
                let newCollection = JSON.parse( JSON.stringify( this.props.collection ) );
                
                if ( false === newCollection ) {
                    newCollection = JSON.parse( JSON.stringify( this.state.shoppingList.collection ) );
                }

                newCollection.shoppingList = false;
                this.props.onChangeCollection( this.props.type, newCollection.id, newCollection );

                // Force scroll to top (if enabled).
                const location = this.props.history.location.pathname;
                if ( '/' === location.slice( -1 ) ) {
                    this.props.history.replace( location.substr( 0, location.length - 1 ) );
                } else {
                    this.props.history.replace( `${ location }/` );
                }

                // Required to fix issue on iOS.
                setTimeout(() => {
                    this.setState({
                        regenerating: false,
                    });
                }, 500 );
            });
        }
    }

    onClearAll() {
        if ( confirm( __wprm( 'Are you sure you want to remove all recipes from this shopping list?' ) ) ) {
            this.props.onChangeCollection( this.props.type, this.props.collection.id, { items: {} } );
        }
    }
    
    render() {
        const { type, collection } = this.props;
        const { uid } = this.state;

        // Construct share link.
        let shareLink = '';
        if ( false !== uid ) {
            const parts = window.location.href.split( '#' );

            if ( 'shortcode' === type || 'temp' === type ) {
                shareLink = `${ parts[0] }#shopping-list-${ collection.id }/edit/${ uid }`;
            } else {
                shareLink = `${ parts[0] }#shopping-list/${ uid }`;
            }
        }

        // Check if there are any items in the collection when using the shortcode.
        let hasItems = true;
        if ( ( 'shortcode' === type || 'temp' === type ) && false === uid && false === this.state.loading ) {
            const allItems = Object.values( collection.items ).reduce( (allItems, groupItems) => allItems.concat(groupItems), [] );            
            hasItems = 0 < allItems.length;
        }

        return (
            <Fragment>
                <div className="wprmprc-container-header-container">
                    <div className="wprmprc-container-header">
                        {
                            'shortcode' !== type
                            && 'temp' !== type
                            &&
                            <Fragment>
                                <Button
                                    tag="span"
                                    className="wprmprc-header-link"
                                    onClick={() => {
                                        if ( 'shopping' === type || 'saved' === type ) {
                                            this.props.history.push(`/`);
                                        } else if ( 'inbox' === type ) {
                                            this.props.history.push(`/collection/inbox/`);
                                        } else if ( 'shared' === type ) {
                                            this.props.history.push(`/share/${collection.sharedEncoded}`);
                                        } else {
                                            this.props.history.push(`/collection/${type}/${collection.id}`);
                                        }
                                    }}
                                >{ 'shared' === this.props.type && `${ __wprm( 'Shared Collection:' ) } ` }{ collection && collection.name ? collection.name : __wprm( 'Back' ) }</Button>
                                <span className="wprmprc-header-link-separator">&gt;</span>
                            </Fragment>
                        }
                        <span className="wprmprc-container-header-name">{ __wprm( 'Shopping List' ) }</span>
                    </div>
                    {
                        'grid' === this.props.layout
                        && ! this.state.loading
                        && hasItems
                        &&
                        <GridHeaderActions
                            type={ this.props.type }
                            hasShoppingList={ false !== uid }
                            editing={ this.state.editing }
                            onChangeEditing={ this.onChangeEditing }
                            onClearAll={ false === uid && 'temp' === this.props.type && hasItems ? this.onClearAll : false }
                            shopping={ this.state.shopping }
                            onShopList={ this.onShopList }
                            onGenerateShoppingList={ this.onGenerateShoppingList }
                            onRegenerateShoppingList={ this.onRegenerateShoppingList }
                            onPrintShoppingList={ () => {
                                const printUrl = window.WPRecipeMaker.print.getUrl( 'shopping-list/' + this.state.uid );
                                window.open( printUrl,'_blank' );
                            }}
                            onPrintRecipes={ this.onPrintRecipes }
                            shareLink={ shareLink }
                        />
                    }
                </div>
                {
                    this.state.loading
                    ?
                    <Loader/>
                    :
                    <div className={ `wprmprc-shopping-list${ this.state.editing ? ' wprmprc-shopping-list-editing' : ''}`}>
                        {
                            ! hasItems
                            ?
                            <div className="wprmprc-shopping-list-none">
                                <div>{ __wprm( 'No recipes have been added to the shopping list yet.' ) }</div>
                                {
                                    'temp' === this.props.type
                                    &&
                                    <Button
                                        tag="span"
                                        className="wprmprc-shopping-list-none-link"
                                        onClick={ () => this.onGenerateShoppingList( true ) }
                                    >{ __wprm( 'Generate empty shopping list' ) }</Button>
                                }
                            </div>
                            :
                            <Fragment>
                                {
                                    false === uid
                                    ?
                                    <Fragment>
                                        {
                                            'grid' === this.props.layout
                                            &&
                                            <Fragment>
                                                {
                                                    'temp' === this.props.type
                                                    ?
                                                    <div className="wprmprc-shopping-list-help">{ __wprm( 'Click the cart icon in the top right to generate the shopping list.' ) }</div>
                                                    :
                                                    <div className="wprmprc-shopping-list-help">{ __wprm( 'Select recipes and click the cart icon in the top right to generate the shopping list.' ) }</div>
                                                }
                                            </Fragment>
                                        }
                                        <Collection
                                            { ...this.props }
                                            shoppingList={uid}
                                        />
                                        {
                                            wprmprc_public.settings.recipe_collections_shopping_list_options
                                            &&
                                            <Options
                                                options={ this.state.options }
                                                onOptionsChange={ (newOptions) => {
                                                    this.setState({
                                                        options: {
                                                            ...this.state.options,
                                                            ...newOptions,
                                                        },
                                                    });
                                                }}
                                            />
                                        }
                                    </Fragment>
                                    :
                                    <Fragment>
                                        {
                                            'grid' === this.props.layout
                                            && 'temp' === this.props.type
                                            && 
                                            <div className="wprmprc-shopping-list-help">{ __wprm( 'Click the cart icon in the top right to generate a new shopping list.' ) }</div>
                                        }
                                        <Collection
                                            collection={ this.state.shoppingList.collection }
                                            type={ 'shortcode' === type || 'temp' === type ? type : false }
                                            onChangeCollection={ false }
                                            shoppingList={uid}
                                        />
                                        <List
                                            groups={ this.state.shoppingList.groups }
                                            onGroupsChange={ this.onGroupsChange }
                                            editing={ this.state.editing }
                                            onChangeEditing={ this.onChangeEditing }
                                        />
                                        {
                                            this.state.outdated
                                            && 'grid' === this.props.layout
                                            &&
                                            <div className="wprmprc-shopping-list-warning">{ __wprm( 'Changes to the collection have been made since this shopping list was generated.' ) } <Button
                                                    tag="a"
                                                    href="#"
                                                    onClick={ (e) => {
                                                        e.preventDefault();
                                                        this.onRegenerateShoppingList();
                                                    } }
                                                >{ __wprm( 'Regenerate the shopping list to include these changes.' ) }</Button>
                                                <Button
                                                    className="wprmprc-shopping-list-warning-ignore"
                                                    onClick={ () => {
                                                        this.setState({
                                                            outdated: false,
                                                        });
                                                    } }
                                                    tabIndex="-1"
                                                    aria-label={ __wprm( 'Ignore this warning' ) }
                                                ><Icon type="close" title={ __wprm( 'Ignore' ) } /></Button>
                                            </div>
                                        }
                                        {
                                            'classic' === this.props.layout
                                            &&
                                            <div className="wprmprc-shopping-list-meta">
                                                {
                                                    wprmprc_public.settings.recipe_collections_shopping_list_share
                                                    && <Link to={ `/shopping-list/${ uid }` }>{ __wprm( 'Right click and copy this link to allow others to edit this shopping list.' ) }</Link>
                                                }
                                            </div>
                                        }
                                    </Fragment>
                                }
                                {
                                    'classic' === this.props.layout
                                    &&
                                    <div className="wprmprc-shopping-list-actions">
                                        {
                                            false === uid
                                            ?
                                            <Button
                                                className={ `wprmprc-shopping-list-action wprmprc-shopping-list-action-generate${ this.state.saving ? ' wprmprc-shopping-list-action-disabled' : '' }` }
                                                onClick={ this.onGenerateShoppingList }
                                                disabled={ this.state.saving }
                                            >
                                                {
                                                    this.state.saving
                                                    ?
                                                    <Loader />
                                                    :
                                                    __wprm( 'Generate Shopping List' )
                                                }
                                            </Button>
                                            :
                                            <Fragment>
                                                {
                                                    wprmprc_public.settings.recipe_collections_shopping_list_print
                                                    &&
                                                    <Button
                                                        className="wprmprc-shopping-list-action wprmprc-shopping-list-action-print"
                                                        onClick={ () => {
                                                            const printUrl = window.WPRecipeMaker.print.getUrl( 'shopping-list/' + this.state.uid );
                                                            window.open( printUrl,'_blank' );
                                                        } }
                                                    >{ __wprm( 'Print Shopping List' ) }</Button>
                                                }
                                                {
                                                    wprmprc_public.settings.recipe_collections_shopping_list_print_recipes
                                                    &&
                                                    <Button
                                                        className="wprmprc-shopping-list-action wprmprc-shopping-list-action-print-recipes"
                                                        onClick={ this.onPrintRecipes }
                                                    >{ __wprm( 'Print Recipes' ) }</Button>
                                                }
                                                <Button
                                                    className={ `wprmprc-shopping-list-action wprmprc-shopping-list-action-regenerate${ this.state.saving ? ' wprmprc-shopping-list-action-disabled' : '' }`}
                                                    onClick={ this.onRegenerateShoppingList }
                                                >
                                                    { __wprm( 'Regenerate Shopping List' ) }
                                                </Button>
                                            </Fragment>
                                        }
                                    </div>
                                }
                            </Fragment>
                        }
                    </div>
                }
            </Fragment>
        );
    }
}

export default withRouter(ShoppingList);