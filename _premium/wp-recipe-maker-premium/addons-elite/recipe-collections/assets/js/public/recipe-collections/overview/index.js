import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import Button from '../../../shared/Button';
import { __wprm } from 'Shared/Translations';
import EditList from '../general/EditList';

import Grid from './Grid';
import GridHeaderActions from './GridHeaderActions';
import AddSavedCollection from './AddSavedCollection';

class Overview extends Component {
    render() {
        const viewCollection = (type, collection) => (
            <Button
                className={`wprmprc-overview-collection wprmprc-overview-collection-view wprmprc-overview-collection-${type}`}
                onClick={() => {
                    if ( 'inbox' === type ) {
                        this.props.history.push(`/collection/inbox/`);
                    } else {
                        this.props.history.push(`/collection/${type}/${collection.id}`);
                    }
                }}
            >
                <div className="wprmprc-overview-collection-name">{collection.name}</div>
                <div className="wprmprc-overview-collection-items">{collection.nbrItems}</div>
            </Button>
        );

        const editCollection = (type, collection) => {
            // Don't allow editing fixed collections.
            if (collection.fixed) {
                return viewCollection(type, collection);
            }
            return (
                <div className="wprmprc-overview-collection wprmprc-overview-collection-edit">
                    <input
                        type="text"
                        value={collection.name}
                        onChange={(event) => {
                            this.props.onChangeCollection(type, collection.id, { name: event.target.value });
                        }}
                    />
                    <div className="wprmprc-overview-collection-items">{collection.nbrItems}</div>
                </div>
            );
        };

        const isAdminEditingUser = parseInt( wprmprc_public.user ) !== parseInt( wprmprc_public.collections_user );

        // Starter Templates.
        const hasStarterTemplates = wprmprc_public.hasOwnProperty( 'starter_templates' ) && wprmprc_public.starter_templates && 0 < wprmprc_public.starter_templates.length;

        // Quick Add Collections.
        const hasQuickAddCollections = wprmprc_public.hasOwnProperty( 'quick_add_collections' ) && wprmprc_public.quick_add_collections && 0 < wprmprc_public.quick_add_collections.length;

        return (
            <Fragment>
                <div className="wprmprc-container-header-container">
                    <div className="wprmprc-container-header">
                        <span className="wprmprc-container-header-name">{ __wprm( 'Your Collections' ) }{ isAdminEditingUser ? ` (${ __wprm( 'Editing User' ) } #${ wprmprc_public.collections_user }${ wprmprc_public.collections_user_name ? ` - ${ wprmprc_public.collections_user_name }` : '' })` : ''}</span>
                    </div>
                    {
                        'grid' === this.props.layout
                        &&
                        <GridHeaderActions
                            hasStarterTemplates={ hasStarterTemplates }
                            hasQuickAddCollections={ hasQuickAddCollections }
                            onAdd={ this.props.onAddCollection }
                        />
                    }
                </div>
                <div className="wprmprc-overview">
                    {
                        'grid' === this.props.layout
                        ?
                        <Grid
                            collections={ this.props.collections }
                            onReorder={ this.props.onReorderCollection }
                            onAdd={ this.props.onAddCollection }
                            onDelete={ this.props.onDeleteCollection }
                            onChange={ this.props.onChangeCollection }
                            hasStarterTemplates={ hasStarterTemplates }
                            hasQuickAddCollections={ hasQuickAddCollections }
                        />
                        :
                        <EditList
                            type='collection'
                            onAdd={() => this.props.onAddCollection('user')}
                            onDelete={(id) => {
                                const collection = this.props.collections.user.find(c => c.id === id);
                                if (collection && !collection.fixed) {
                                    this.props.onDeleteCollection('user', id);
                                }
                            }}
                            onDuplicate={(id, index) => {
                                const collection = this.props.collections.user.find(c => c.id === id);
                                if (collection && !collection.fixed) {
                                    this.props.onAddCollection( 'user', id );
                                }
                            }}
                            onReorder={(newItems, oldIndex, newIndex) => this.props.onReorderCollection('user', oldIndex, newIndex)}
                            header={(editing) => editing ? editCollection('inbox', this.props.collections.inbox) : viewCollection('inbox', this.props.collections.inbox) }
                            items={this.props.collections.user}
                            item={(editing, item) => {
                                // Don't allow editing fixed collections.
                                if (item.fixed && editing) {
                                    return viewCollection('user', item);
                                }
                                return editing ? editCollection('user', item) : viewCollection('user', item);
                            }}
                            labels={{
                                add: __wprm( 'Add Collection' ),
                                edit: __wprm( 'Edit Collections' ),
                            }}
                        />
                    }
                    {
                        isAdminEditingUser
                        &&
                        <AddSavedCollection
                            onAddSavedCollection={(collection) => {
                                this.props.onAddCollection( 'saved', false, collection );
                            }}
                        />
                    }
                </div>
            </Fragment>
        );
    }
}

export default withRouter(Overview);