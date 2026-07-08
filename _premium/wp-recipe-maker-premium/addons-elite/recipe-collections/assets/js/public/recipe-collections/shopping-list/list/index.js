import React, { Fragment } from 'react';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

import Button from '../../../../shared/Button';
import { __wprm } from 'Shared/Translations';
import Icon from '../../general/Icon';

import Ingredient from './Ingredient';

const List = (props) => {
    const { groups, editing } = props;

    const onDragEnd = (result) => {
        if ( result.destination ) {
            if ( 'GROUP' === result.type ) {
                const oldIndex = result.source.index;
                const newIndex = result.destination.index;
                let newGroups = JSON.parse( JSON.stringify( groups ) );
    
                const group = newGroups.splice( oldIndex, 1 )[0];
                newGroups.splice( newIndex, 0, group );
    
                props.onGroupsChange(newGroups);
            }

            if ( 'INGREDIENT' === result.type ) {
                const oldGroupId = parseInt( result.source.droppableId );
                const newGroupId = parseInt( result.destination.droppableId );

                const oldGroupIndex = groups.findIndex((group) => ( group.id === oldGroupId ));
                const newGroupIndex = groups.findIndex((group) => ( group.id === newGroupId ));

                if ( 0 <= oldGroupIndex && 0 <= newGroupIndex ) {
                    const oldIndex = result.source.index;
                    const newIndex = result.destination.index;
                    
                    let newGroups = JSON.parse( JSON.stringify( groups ) );
        
                    const ingredient = newGroups[ oldGroupIndex ].ingredients.splice( oldIndex, 1 )[0];
                    newGroups[ newGroupIndex ].ingredients.splice( newIndex, 0, ingredient );
        
                    props.onGroupsChange(newGroups);
                }
            }
        }
    };

    return (
        <div className="wprmprc-shopping-list-list">
            <div className="wprmprc-shopping-list-list-header">
                <div className="wprmprc-shopping-list-list-name">
                    { __wprm( 'List' ) }
                </div>
            </div>
            <DragDropContext
                onDragEnd={onDragEnd}
            >
                <Droppable
                    droppableId="shopping-list-groups"
                    type="GROUP"
                >
                    {(provided, snapshot) => (
                        <div
                            className={`wprmprc-shopping-list-list-ingredients${ snapshot.isDraggingOver ? ' wprmprc-shopping-list-list-ingredients-draggingover' : ''}`}
                            ref={provided.innerRef}
                            {...provided.droppableProps}
                        >
                            {
                                0 < groups.length
                                ?
                                <Fragment>
                                    {
                                        groups.map((group, groupIndex) => {
                                            return (
                                                <Draggable
                                                    draggableId={ `group-${group.id}` }
                                                    index={ groupIndex }
                                                    key={ group.id }
                                                    type="GROUP"
                                                    isDragDisabled={ ! editing }
                                                >
                                                    {(provided, snapshot) => (
                                                        <div
                                                            className="wprmprc-shopping-list-list-ingredient-group-container"
                                                            ref={provided.innerRef}
                                                            {...provided.draggableProps}
                                                        >
                                                            {
                                                                editing
                                                                ?
                                                                <div className="wprmprc-shopping-list-list-ingredient-group">
                                                                    <div className="wprmprc-shopping-list-editing-actions">
                                                                        <div
                                                                            className="wprmprc-shopping-list-editing-handle"
                                                                            {...provided.dragHandleProps}
                                                                        ><Icon type="drag" /></div>
                                                                        <Button
                                                                            className="wprmprc-shopping-list-editing-delete"
                                                                            onClick={() => {
                                                                                if ( 0 === group.ingredients.length || confirm( __wprm( 'Are you sure you want to delete this group, and all of the items in it?' ) ) ) {
                                                                                    let newGroups = JSON.parse( JSON.stringify( groups ) );
                                                                                    newGroups.splice( groupIndex, 1 );

                                                                                    props.onGroupsChange(newGroups);
                                                                                }
                                                                            }}
                                                                            aria-label={ __wprm( 'Delete this shopping list group' ) }
                                                                        ><Icon type="delete" /></Button>
                                                                    </div>
                                                                    <input
                                                                        type="text"
                                                                        value={ group.name }
                                                                        onChange={(event) => {
                                                                            let newGroups = JSON.parse( JSON.stringify( groups ) );
                                                                            newGroups[ groupIndex ].name = event.target.value;

                                                                            props.onGroupsChange(newGroups);
                                                                        }}
                                                                    />
                                                                </div>
                                                                :
                                                                <Fragment>
                                                                    {
                                                                        '' !== group.name
                                                                        &&
                                                                        <div className="wprmprc-shopping-list-list-ingredient-group">{ group.name }</div>
                                                                    }
                                                                </Fragment>
                                                            }
                                                            <Droppable
                                                                droppableId={ `${group.id}` }
                                                                type="INGREDIENT"
                                                            >
                                                                {(provided, snapshot) => (
                                                                    <div
                                                                        className={`wprmprc-shopping-list-list-ingredient-group-ingredients${ snapshot.isDraggingOver ? ' wprmprc-shopping-list-list-ingredient-group-ingredients-draggingover' : ''}`}
                                                                        ref={provided.innerRef}
                                                                        {...provided.droppableProps}
                                                                    >
                                                                        {
                                                                            group.ingredients.map( ( ingredient, ingredientIndex ) => (
                                                                                <Ingredient
                                                                                    ingredient={ ingredient }
                                                                                    onIngredientChange={ ( newIngredient ) => {
                                                                                        let newGroups = JSON.parse( JSON.stringify( groups ) );
                                                                                        newGroups[ groupIndex ].ingredients[ ingredientIndex ] = newIngredient;

                                                                                        props.onGroupsChange(newGroups);
                                                                                    } }
                                                                                    onIngredientDelete={ () => {
                                                                                        let newGroups = JSON.parse( JSON.stringify( groups ) );
                                                                                        newGroups[ groupIndex ].ingredients.splice( ingredientIndex, 1 );

                                                                                        props.onGroupsChange(newGroups);
                                                                                    }}
                                                                                    editing={ editing }
                                                                                    index={ ingredientIndex }
                                                                                    key={ ingredientIndex }
                                                                                />
                                                                            ))
                                                                        }
                                                                        {provided.placeholder}
                                                                    </div>
                                                                )}
                                                            </Droppable>
                                                            {
                                                                editing
                                                                &&
                                                                <div className="wprmprc-edit-list-actions">
                                                                    <Button
                                                                        tag="span"
                                                                        className="wprmprc-edit-list-action wprmprc-edit-list-action-add"
                                                                        onClick={() => {
                                                                            let newGroups = JSON.parse( JSON.stringify( groups ) );

                                                                            // Get maxId in use.
                                                                            const allIngredients = groups.reduce( (allIngredients, group) => allIngredients.concat(group.ingredients), [] );
                                                                            let maxId = Math.max.apply( Math, allIngredients.map( function(ingredient) { return ingredient.id; } ) );
                                                                            maxId = maxId < 0 ? -1 : maxId;

                                                                            newGroups[ groupIndex ].ingredients.push({
                                                                                id: maxId + 1,
                                                                                checked: false,
                                                                                name: '',
                                                                                variations: [
                                                                                    { display: '' }
                                                                                ],
                                                                            });

                                                                            props.onGroupsChange(newGroups);
                                                                        }}
                                                                    >{ __wprm( 'Add Item' ) }</Button>
                                                                </div>
                                                            }
                                                        </div>
                                                    )}
                                                </Draggable>
                                            )
                                        })
                                    }
                                </Fragment>            
                                :
                                <div className="wprmprc-shopping-list-list-ingredients-none">
                                    <div>{ __wprm( 'Your shopping list is empty.' ) }</div>
                                    <Button
                                        tag="span"
                                        className="wprmprc-shopping-list-list-ingredients-none-link"
                                        onClick={() => props.onChangeEditing(true)}
                                    >{ __wprm( 'Start editing to manually fill it' ) }</Button>
                                </div>
                            }
                            {provided.placeholder}
                        </div>
                    )}
                </Droppable>
            </DragDropContext>
            <div className="wprmprc-edit-list-actions">
                {
                    editing
                    ?
                    <Fragment>
                        <Button
                            tag="span"
                            className="wprmprc-edit-list-action wprmprc-edit-list-action-cancel"
                            onClick={() => props.onChangeEditing(false) }
                        >{ __wprm( 'Stop Editing' ) }</Button>
                        {
                            0 < groups.length
                            &&
                            <Fragment>
                                <span className="wprmprc-edit-list-action-seperator"> - </span>
                                <Button
                                    tag="span"
                                    className="wprmprc-edit-list-action wprmprc-edit-list-action-add"
                                    onClick={() => {
                                        let newGroups = JSON.parse( JSON.stringify( groups ) );

                                        // Get maxId in use.
                                        let maxId = Math.max.apply( Math, groups.map( function(group) { return group.id; } ) );
                                        maxId = maxId < 0 ? -1 : maxId;

                                        newGroups.push({
                                            id: maxId + 1,
                                            checked: false,
                                            name: __wprm( 'Group' ),
                                            ingredients: [],
                                        });

                                        props.onGroupsChange(newGroups);
                                    }}
                                >{ __wprm( 'Add Group' ) }</Button>
                            </Fragment>
                        }
                        {
                            0 === groups.length
                            &&
                            <Button
                                tag="span"
                                className="wprmprc-edit-list-action wprmprc-edit-list-action-add"
                                onClick={() => {
                                    let newGroups = [];

                                    newGroups.push({
                                        id: 0,
                                        checked: false,
                                        name: __wprm( 'Group' ),
                                        ingredients: [],
                                    });

                                    props.onGroupsChange(newGroups);
                                }}
                            >{ __wprm( 'Add Group' ) }</Button>
                        }
                    </Fragment>
                    :
                    <Button
                        className="wprmprc-edit-list-action wprmprc-edit-list-action-edit"
                        onClick={() => props.onChangeEditing(true)}
                    >{ __wprm( 'Edit Shopping List' ) }</Button>
                }
            </div>
        </div>
    );
}
export default List;