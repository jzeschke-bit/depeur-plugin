import React, { Component, Fragment } from 'react';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

import Button from '../../../shared/Button';
import { __wprm } from 'Shared/Translations';
import Icon from '../general/Icon';

export default class EditList extends Component {
    constructor(props) {
        super(props);

        this.lastItem = React.createRef();

        let editing = props.editing ? props.editing : false;
        let alwaysEditing = false;

        if ( props.hasOwnProperty( 'alwaysEditing') && props.alwaysEditing ) {
            editing = true;
            alwaysEditing = true;
        }

        this.state = {
            editing,
            alwaysEditing,
        }
    }

    componentDidUpdate( prevProps ) {
        if ( this.state.editing ) {
            if ( this.props.items.length > prevProps.items.length ) {
                const inputs = this.lastItem.current.getElementsByTagName('input');

                if ( inputs.length ) {
                    inputs[0].focus();
                }
            }
        }
    }

    onDragEnd(result) {
        if ( this.props.type.toUpperCase() === result.type && result.destination ) {
            const oldIndex = result.source.index;
            const newIndex = result.destination.index;
            let newItems = [ ...this.props.items ];

            const item = newItems.splice( oldIndex, 1 )[0];
            newItems.splice( newIndex, 0, item );

            this.props.onReorder( newItems, oldIndex, newIndex );
        }
    }

    render() {
        const inModal = this.props.hasOwnProperty( 'inModal' ) && this.props.inModal;

        const showDragHandle = ! this.state.editing || this.state.alwaysEditing;
        const showDelete = this.state.editing;
        const showDuplicate = this.state.editing && this.props.hasOwnProperty( 'onDuplicate' );

        const renderItem = ( provided, snapshot, rubric ) => {
            const index = rubric.source.index;
            const item = this.props.items[index];

            return (
                <div
                    className="wprmprc-edit-list-item-container"
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    style={ {
                        ...provided.draggableProps.style,
                        zIndex: 2147483647, // Needed to show above modal.
                    } }
                >
                    {
                        showDragHandle
                        &&
                        <div
                            className="wprmprc-edit-list-item-handle"
                            {...provided.dragHandleProps}
                        ><Icon type="drag" /></div>
                    }
                    {
                        ( showDelete || showDuplicate )
                        &&
                        <div className="wprmprc-edit-list-item-actions">
                            {
                                showDelete
                                &&
                                <Button
                                    className="wprmprc-edit-list-item-delete"
                                    onClick={() => {
                                        if( this.props.skipConfirm || confirm( `${__wprm( 'Are you sure you want to delete all items in' )} "${item.name}"?` ) ) {
                                            this.props.onDelete(item.id, index);
                                        }
                                    }}
                                    aria-label={ __wprm( 'Delete' ) }
                                ><Icon type="delete" /></Button>
                            }
                            {
                                showDuplicate
                                &&
                                <Button
                                    className="wprmprc-edit-list-item-duplicate"
                                    onClick={() => this.props.onDuplicate(item.id, index)}
                                    aria-label={ __wprm( 'Duplicate' ) }
                                ><Icon type="duplicate" /></Button>
                            }
                        </div>
                    }
                    <div
                        className={`wprmprc-edit-list-item${ this.state.editing ? ' wprmprc-edit-list-item-edit': ''}`}
                        ref={index === this.props.items.length - 1 ? this.lastItem : null }
                    >
                        { this.props.item(this.state.editing, item, index) }
                    </div>
                </div>
            )
        };

        return (
            <DragDropContext
                onDragEnd={this.onDragEnd.bind(this)}
            >
                <div
                    className="wprmprc-edit-list"
                    ref={this.listContainer}
                >
                    {
                        undefined !== this.props.header
                        &&
                        <div className='wprmprc-edit-list-item-container wprmprc-edit-list-item-header'>
                            <div className={`wprmprc-edit-list-item${ this.state.editing ? ' wprmprc-edit-list-item-edit': ''}`}>
                                { this.props.header(this.state.editing) }
                            </div>
                        </div>
                    }
                    <Droppable
                        droppableId={`edit-list-${this.props.type}`}
                        type={this.props.type.toUpperCase()}
                        renderClone={ inModal ? renderItem : null }
                    >
                        {(provided, snapshot) => (
                            <div
                                className={`wprmprc-edit-list-items${ snapshot.isDraggingOver ? ' wprmprc-edit-list-items-draggingover' : ''}`}
                                ref={provided.innerRef}
                                {...provided.droppableProps}
                            >
                                {
                                    this.props.items.map((item, index) =>
                                        <Draggable
                                            draggableId={`${item.id}`}
                                            index={index}
                                            key={item.id}
                                            type={this.props.type.toUpperCase()}
                                            isDragDisabled={ this.state.editing && ! this.state.alwaysEditing }
                                        >
                                            { renderItem }
                                        </Draggable>
                                    )
                                }
                                {provided.placeholder}
                            </div>
                        )}
                    </Droppable>
                    <div className="wprmprc-edit-list-actions">
                        {
                            this.state.editing
                            ?
                            <Fragment>
                                {
                                    ! this.state.alwaysEditing
                                    &&
                                    <Fragment><Button
                                        tag="span"
                                        className="wprmprc-edit-list-action wprmprc-edit-list-action-cancel"
                                        onClick={() => this.setState({ editing: false }) }
                                    >{ __wprm( 'Stop Editing' ) }</Button><span className="wprmprc-edit-list-action-seperator"> - </span></Fragment>
                                }<Button
                                    tag="span"
                                    className="wprmprc-edit-list-action wprmprc-edit-list-action-add"
                                    onClick={() => this.props.onAdd()}
                                >{this.props.labels.add}</Button>
                            </Fragment>
                            :
                            <Button
                                tag="span"
                                className="wprmprc-edit-list-action wprmprc-edit-list-action-edit"
                                onClick={() => this.setState({ editing: true }) }
                            >{this.props.labels.edit}</Button>
                        }
                    </div>
                </div>
            </DragDropContext>
        );
    }
}
