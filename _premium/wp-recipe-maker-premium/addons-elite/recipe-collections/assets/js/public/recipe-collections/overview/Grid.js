import React, { Component, Fragment } from 'react';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { withRouter } from 'react-router-dom';

import { __wprm } from 'Shared/Translations';

import GridCollection from './GridCollection';
import ContextMenu from '../general/ContextMenu';

class Grid extends Component {
    constructor(props) {
        super(props);

        this.state = {
            editing: false,
        }
    }

    componentDidUpdate( prevProps ) {
        if ( this.props.collections.user.length > prevProps.collections.user.length ) {
            let maxId = Math.max.apply( Math, this.props.collections.user.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;

            this.setState({
                editing: maxId,
            });
        }
    }

    onDragEnd(result) {
        if ( "COLLECTION" === result.type && result.destination ) {
            const oldIndex = result.source.index;
            const newIndex = result.destination.index;

            this.props.onReorder( 'user', oldIndex, newIndex );
        }
    }

    render() {
        // Starter Templates.
        let starterTemplates = [];
        if ( this.props.hasStarterTemplates ) {
            for ( let template of wprmprc_public.starter_templates ) {
                starterTemplates.push(
                    {
                        label: template.name,
                        action: () => {
                            this.props.onAdd( 'saved', false, template );
                        }
                    },
                );
            }
        }

        // Quick Add Collections.
        let quickAddCollections = [];
        if ( this.props.hasQuickAddCollections ) {
            for ( let template of wprmprc_public.quick_add_collections ) {
                quickAddCollections.push(
                    {
                        label: template.name,
                        action: () => {
                            this.props.onAdd( 'saved', false, template );
                        }
                    },
                );
            }
        }

        return (
            <DragDropContext
                onDragEnd={ this.onDragEnd.bind(this) }
            >
                <div
                    className="wprmprc-overview-grid"
                >
                    <GridCollection
                        collection={ this.props.collections.inbox }
                        onOpenShoppingList={ () => {
                            this.props.history.push(`/shopping-list/inbox/`);
                        }}
                        onClick={ () => {
                            this.props.history.push(`/collection/inbox/`);
                        }}
                        onChange={ ( changes ) => {
                            this.props.onChange( 'inbox', 0, changes );
                        }}
                        onDuplicate={ false }
                        onDelete={ false }
                        editing={ 'inbox' === this.state.editing }
                        onEditing={ ( editing ) => {
                            if ( editing ) {
                                this.setState({ editing: 'inbox' } );
                            } else {
                                if ( 'inbox' === this.state.editing ) {
                                    this.setState({ editing: false } );
                                }
                            }
                        } }
                    />
                    <Droppable
                        droppableId="overview-grid"
                        type="COLLECTION"
                    >
                        {(provided, snapshot) => (
                            <div
                                className={`wprmprc-overview-grid-collections${ snapshot.isDraggingOver ? ' wprmprc-overview-grid-collections-draggingover' : ''}`}
                                ref={provided.innerRef}
                                {...provided.droppableprops}
                            >
                                {
                                    this.props.collections.user.map((collection, index) =>
                                        <Draggable
                                            draggableId={`${collection.id}`}
                                            index={index}
                                            key={collection.id}
                                            type="COLLECTION"
                                        >
                                            {(provided, snapshot) => (
                                                <GridCollection
                                                    collection={ collection }
                                                    type="user"
                                                    innerRef={ provided.innerRef }
                                                    draggable={ provided }
                                                    onOpenShoppingList={ () => {
                                                        this.props.history.push(`/shopping-list/user/${collection.id}`);
                                                    }}
                                                    onClick={ () => {
                                                        this.props.history.push(`/collection/user/${ collection.id }`);
                                                    }}
                                                    onChange={ ( changes ) => {
                                                        // Don't allow editing fixed collections (except items).
                                                        if ( collection.fixed && ! changes.hasOwnProperty( 'items' ) ) {
                                                            return;
                                                        }
                                                        this.props.onChange( 'user', collection.id, changes );
                                                    }}
                                                    onDuplicate={ collection.fixed ? false : () => {
                                                        this.props.onAdd( 'user', collection.id );
                                                    } }
                                                    onDelete={ collection.fixed ? false : () => {
                                                        this.props.onDelete( 'user', collection.id )
                                                    } }
                                                    editing={ collection.id === this.state.editing && ! collection.fixed }
                                                    onEditing={ ( editing ) => {
                                                        // Don't allow editing mode for fixed collections.
                                                        if ( collection.fixed ) {
                                                            return;
                                                        }
                                                        if ( editing ) {
                                                            this.setState({ editing: collection.id } );
                                                        } else {
                                                            if ( collection.id === this.state.editing ) {
                                                                this.setState({ editing: false } );
                                                            }
                                                        }
                                                    } }
                                                />
                                            )}
                                        </Draggable>
                                    )
                                }
                                {provided.placeholder}
                            </div>
                        )}
                    </Droppable>
                    <div
                        className="wprmprc-action wprmprc-overview-grid-collection-add"
                        onClick={() => {
                            if ( ! this.props.hasStarterTemplates ) {
                                this.props.onAdd( 'user' );
                            }
                        }}
                    >
                        {
                            this.props.hasStarterTemplates
                            ?
                            <ContextMenu
                                icon={ false }
                                text={ __wprm( 'Add Collection' ) }
                                menu={ [
                                    ...starterTemplates,
                                    {
                                        divider: true,
                                    },
                                    {
                                        label: __wprm( 'Empty Collection' ),
                                        action: () => {
                                            this.props.onAdd( 'user' );
                                        }
                                    }
                                ] }
                            />
                            :
                            __wprm( 'Add Collection' )
                        }
                    </div>
                    {
                        this.props.hasQuickAddCollections
                        &&
                        <div
                            className="wprmprc-action wprmprc-overview-grid-collection-add"
                        >
                            <ContextMenu
                                icon={ false }
                                text={ __wprm( 'Add Pre-made Collection' ) }
                                menu={ quickAddCollections }
                            />
                        </div>
                    }
                </div>
            </DragDropContext>
        );
    }
}
export default withRouter( Grid );