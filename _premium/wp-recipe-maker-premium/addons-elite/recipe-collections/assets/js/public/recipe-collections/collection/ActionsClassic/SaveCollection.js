import React, { Component, Fragment } from 'react';
import Hashids from 'hashids'

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Api from '../../general/Api';
import Loader from '../../general/Loader';
import Icon from '../../general/Icon';

export default class SaveCollection extends Component {
    constructor(props) {
        super(props);

        this.state = {
            saving: false,
            saved: false,
        }
    }

    componentDidUpdate() {
        if ( this.state.saved ) {
            // Redirect to collections if link is set.
            const collections_url = wprmprc_public.settings.recipe_collections_link;
            if ( collections_url ) {
                window.location = collections_url;
            }
        }
    }

    saveCollection() {
        if ( 0 === parseInt( wprmp_public.user ) ) {
            const localCollections = localStorage.getItem( 'wprm-recipe-collection' );

            let collections;
            if ( localCollections ) {
                collections = JSON.parse(localCollections);
            } else {
                collections = wprmp_public.collections.default;
            }

            let collection = JSON.parse(JSON.stringify(this.props.collection));

            // Remove share related data.
            collection.shared = false;
            delete collection.sharedEncoded;

            // Get unique ID for collection.
            let maxId = Math.max.apply( Math, collections.user.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;
            collection.id = maxId + 1;

            collections.user.push(collection);
            localStorage.setItem( 'wprm-recipe-collection', JSON.stringify( collections ) );

            this.setState({
                saved: true,
            });
        } else {
            if ( 'shared' === this.props.type ) {
                const hashids = new Hashids( 'wp-recipe-maker' );
                const decoded = hashids.decode( this.props.collection.sharedEncoded );

                if ( decoded ) {
                    const userId = decoded[0];
                    const collectionId = 1 < decoded.length ? decoded[1] : 'inbox';

                    this.setState({
                        saving: true,
                    });
                
                    Api.saveSharedCollectionToCollections( userId, collectionId ).then(() => {
                        this.setState({
                            saving: false,
                            saved: true,
                        });
                    });
                } else {
                    alert( __wprm( 'Something went wrong. Please try again.' ) );
                }
            } else {
                this.setState({
                    saving: true,
                });

                Api.saveCollectionToCollections(this.props.collection.id).then(() => {
                    this.setState({
                        saving: false,
                        saved: true,
                    });
                });
            }
        }
    }

    render() {
        if ( this.state.saved || ! wprmprc_public.settings.recipe_collections_save_button ) {
            return null;
        }

        const layout = this.props.hasOwnProperty( 'layout' ) ? this.props.layout : 'classic';
        
        return (
            <Fragment>
            {
                'grid' === layout
                ?
                <Button
                    className="wprmprc-container-header-action wprmprc-container-header-action-save"
                    onClick={this.saveCollection.bind(this)}
                    tabIndex="-1"
                    aria-label={ __wprm( 'Save to my Collections' ) }
                >{ this.state.saving ? <Loader /> : <Icon type="download" title={ __wprm( 'Save to my Collections' ) } /> }</Button>
                :
                <div className="wprmprc-collection-action" onClick={this.saveCollection.bind(this)}>
                    { this.state.saving ? <Loader /> : __wprm( 'Save to my Collections' ) }
                </div>
            }
            </Fragment>
        );
    }
}
