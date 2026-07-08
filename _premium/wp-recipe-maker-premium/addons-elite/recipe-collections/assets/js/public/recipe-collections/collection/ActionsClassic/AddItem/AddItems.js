import React, { Component } from 'react';
import { Droppable } from 'react-beautiful-dnd';

import { __wprm } from 'Shared/Translations';

import Item from '../../Item';

export default class AddItems extends Component {
    constructor(props) {
        super(props);

        this.state = {
            itemsPerPage: 10,
            page: 1,
        }
    }

    componentDidUpdate( prevProps ) {
        if ( JSON.stringify( this.props.addItems ) !== JSON.stringify( prevProps.addItems ) ) {
            this.setState({
                page: 1,
            });
        }
    }

    render() {
        const addInterface = this.props.hasOwnProperty( 'interface' ) ? this.props.interface : 'drag';
        const itemsToShow = this.state.page * this.state.itemsPerPage;

        return (
            <Droppable
                droppableId={`select-items`}
                type='RECIPE'
                isDropDisabled={true}
            >
                {(provided, snapshot) => (
                    <div
                        className='wprmprc-collection-action-select-items'
                        ref={provided.innerRef}
                        {...provided.droppableProps}
                    >
                        {
                            0 < this.props.addItems.length
                            &&
                            <div className="wprmprc-collection-action-select-items-add">
                                {
                                    'click' === addInterface
                                    ?
                                    __wprm( 'Click to add:' )
                                    :
                                    __wprm( 'Drag and drop to add:' )
                                }
                            </div>
                        }
                        {
                            this.props.addItems.map( (item, index) => {
                                // Limit items shown at first.
                                if ( itemsToShow <= index ) {
                                    return null;
                                }

                                return (
                                    <Item
                                        layout={this.props.layout}
                                        type={this.props.type}
                                        collection={this.props.collection}
                                        item={{
                                            ...item,
                                            id: `select-${item.id}`,
                                        }}
                                        interface={ this.props.interface }
                                        onAddItem={ this.props.onAddItem }
                                        index={index}
                                        key={ `select-${item.id}` }
                                    />
                                )
                            })
                        }
                        {
                            itemsToShow < this.props.addItems.length
                            &&
                            <div className="wprmprc-collection-action-select-items-actions">
                                <span
                                    role="button"
                                    className="wprmprc-collection-action-select-items-action"
                                    onClick={() => {
                                        this.setState({
                                            page: this.state.page + 1,
                                        });
                                    }}
                                >{ __wprm( 'Load more...' ) }</span>
                            </div>
                        }
                    </div>
                )}
            </Droppable>
        );
    }
}
