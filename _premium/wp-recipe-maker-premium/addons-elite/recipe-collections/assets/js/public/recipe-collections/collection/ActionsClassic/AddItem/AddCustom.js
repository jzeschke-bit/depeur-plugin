import React, { Component, Fragment } from 'react';
import { Droppable } from 'react-beautiful-dnd';

import { __wprm } from 'Shared/Translations';

import Item from '../../Item';
import EditCustom from '../EditItem/EditCustom';

export default class AddCustom extends Component {
    componentDidMount() {
        this.props.onChangeAddItems([
            {
                type: 'ingredient',
                name: '',
                color: 'none',
                ingredients: [
                    {
                        id: 0,
                        amount: '',
                        unit: '',
                        name: '',
                    }
                ],
                nutrition: {}
            }
        ]);
    }

    render() {
        const addInterface = this.props.hasOwnProperty( 'interface' ) ? this.props.interface : 'drag';
        const item = {
            ...this.props.addItems[0],
            id: 'select-0',
        }

        if ( 'ingredient' !== item.type ) {
            return null;
        }

        return (
            <div className="wprmprc-collection-action-add-ingredient">
                <Droppable
                    droppableId={`select-items`}
                    type='RECIPE'
                    isDropDisabled={true}
                >
                    {(provided, snapshot) => (
                        <div
                            className="wprmprc-collection-action-select-items"
                            ref={provided.innerRef}
                            {...provided.droppableProps}
                        >
                            <div className="wprmprc-collection-action-select-items-add">
                                {
                                    'click' === addInterface
                                    ?
                                    __wprm( 'Click to add:' )
                                    :
                                    __wprm( 'Drag and drop to add:' )
                                }
                            </div>
                            <Item
                                layout={this.props.layout}
                                type={this.props.type}
                                collection={this.props.collection}
                                item={ item }
                                interface={ this.props.interface }
                                onAddItem={ this.props.onAddItem }
                                key="select-custom"
                                index={ 0 }
                            />
                        </div>
                    )}
                </Droppable>
                <EditCustom
                    item={item}
                    onEdit={(item) => this.props.onChangeAddItems( [item] ) }
                />
            </div>
        );
    }
}
