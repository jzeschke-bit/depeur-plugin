import React, { Component } from 'react';
import { Droppable } from 'react-beautiful-dnd';

import { __wprm } from 'Shared/Translations';

import Button from '../../../shared/Button';
import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';

import Item from './Item';
import Header from './Header';

export default class Group extends Component {
    render() {
        const { index, group, items } = this.props;

        return (
            <div className="wprmprc-collection-group">
                {
                    ( 'grid' === this.props.layout || '' !== group.name )
                    &&
                    <Header
                        layout={ this.props.layout }
                        editStructureMode={ this.props.editStructureMode }
                        type="group"
                        showActions={ 'saved' !== this.props.type && 'shared' !== this.props.type }
                        customAction={ 'icons' !== this.props.editStructureMode ? false : {
                            type: 'add',
                            icon: 'plus-alt',
                            title: __wprm( 'Add Item' ),
                            action: this.props.onAddItem,
                        } }
                        menu={ 'icons' !== this.props.editStructureMode ? false : [
                            {
                                label: __wprm( 'Change Name' ),
                                action: () => {
                                    this.props.onEditingHeader( true );
                                }
                            },
                            {
                                disabled: ! this.props.allowMoveUp,
                                label: __wprm( 'Move Up' ),
                                action: () => {
                                    this.props.onMove( true );
                                }
                            },
                            {
                                disabled: ! this.props.allowMoveDown,
                                label: __wprm( 'Move Down' ),
                                action: () => {
                                    this.props.onMove( false );
                                }
                            },
                            {
                                divider: true,
                            },
                            {
                                disabled: 1 === this.props.collection.groups.length,
                                label: __wprm( 'Delete Group' ),
                                confirm: __wprm( 'Are you sure you want to delete?' ),
                                action: () => {
                                    this.props.onDelete();
                                },
                            }
                        ] }
                        name={ group.name }
                        onChangeName={ this.props.onChangeHeaderName }
                        editing={ this.props.editingHeader }
                        onEditing={ this.props.onEditingHeader }
                    />
                }
                <Droppable
                    droppableId={index}
                    type='RECIPE'
                >
                    {(provided, snapshot) => (
                        <div
                            className={`wprmprc-collection-group-items${ snapshot.isDraggingOver ? ' wprmprc-collection-group-items-draggingover' : ''}`}
                            ref={provided.innerRef}
                            {...provided.droppableProps}
                        >
                            {
                                items.map( (item, itemIndex) =>
                                    <Item
                                        layout={this.props.layout}
                                        type={this.props.type}
                                        collection={this.props.collection}
                                        item={item}
                                        recipes={this.props.recipes}
                                        showNutrition={this.props.showNutrition}
                                        onDeleteItem={this.props.onDeleteItem}
                                        onDuplicateItem={() => {
                                            this.props.onDuplicateItem( itemIndex );
                                        }}
                                        onEditItem={() => {
                                            this.props.onEditItem( itemIndex );
                                        }}
                                        onChangeAmount={this.props.onChangeAmount}
                                        onChangeServings={this.props.onChangeServings}
                                        onChangeLeftovers={this.props.onChangeLeftovers}
                                        index={itemIndex}
                                        key={item.id}
                                        allowClick={true}
                                    />
                                )
                            }
                            {
                                'saved' !== this.props.type
                                && 'shared' !== this.props.type
                                && ( ! this.props.collection.fixed || 'admin' === this.props.type )
                                && ( 'classic' === this.props.layout || 'modal' === this.props.editStructureMode || 0 === items.length )
                                &&
                                <Button
                                    className="wprmprc-action wprmprc-collection-group-add-item"
                                    onClick={this.props.onAddItem}
                                    style={ 'overview' === this.props.mode ? {} : { visibility: 'hidden' } }
                                    aria-label={ __wprm( 'Add item to this collection group' ) }
                                >{ __wprm( 'Add Item' ) }</Button>
                            }
                            {provided.placeholder}
                        </div>
                    )}
                </Droppable>
            </div>
        );
    }
}
