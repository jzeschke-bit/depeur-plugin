import React, { Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import EditList from '../../general/EditList';

const EditStructureModal = (props) => {
    const editListItem = (type, editing, item, index) => (
        <Fragment>
            {
                editing
                ?
                <input
                    type="text"
                    value={item.name}
                    onChange={(event) => {
                        let items = 'columns' === type ? [ ...props.collection.columns ] : [ ...props.collection.groups ];

                        items[index] = {
                            ...items[index],
                            name: event.target.value,
                        }

                        if ( 'columns' === type ) {
                            props.onChangeColumns(items);
                        } else {
                            props.onChangeGroups(items);
                        }
                    }}
                />
                :
                item.name
            }
        </Fragment>
    );

    return (
        <div className="wprm-recipe-collections-edit-structure-modal-content">
            <div className="wprm-recipe-collections-edit-description">
                <label>
                    { __wprm( 'Description' ) }
                    <input
                        type="text"
                        value={ props.collection.description }
                        onChange={(event) => {
                            props.onChangeDescription( event.target.value );
                        }}
                    />
                </label>
            </div>
            <div className="wprm-recipe-collections-edit-structure-container">
                <div className="wprm-recipe-collections-edit-structure">
                    <EditList
                        header={ () => ( __wprm( 'Columns' ) ) }
                        type='column'
                        alwaysEditing={ true }
                        inModal={ true }
                        onAdd={() => {
                            let items = [ ...props.collection.columns ];
                            let maxId = Math.max.apply( Math, items.map( function(item) { return item.id; } ) );
                            maxId = maxId < 0 ? -1 : maxId;

                            items.push({ id: maxId + 1, name: '' });
                            props.onChangeColumns(items);
                        }}
                        onDelete={(id, index) => {
                            let items = [ ...props.collection.columns ];
                            items.splice(index, 1);

                            props.onChangeColumns(items);
                        }}
                        onReorder={(newItems) => {
                            props.onChangeColumns(newItems);
                        }}
                        onDuplicate={ (id, index) => {
                            props.onDuplicateColumn( index );
                        } }
                        items={props.collection.columns}
                        item={(editing, item, index) => editListItem('columns', editing, item, index) }
                        labels={{
                            add: __wprm( 'Add Column' ),
                            edit: __wprm( 'Edit Columns' ),
                        }}
                    />
                </div>
                <div className="wprm-recipe-collections-edit-structure">
                    <EditList
                        header={ () => ( __wprm( 'Groups' ) ) }
                        type='group'
                        alwaysEditing={ true }
                        inModal={ true }
                        onAdd={() => {
                            let items = [ ...props.collection.groups ];
                            let maxId = Math.max.apply( Math, items.map( function(item) { return item.id; } ) );
                            maxId = maxId < 0 ? -1 : maxId;

                            items.push({ id: maxId + 1, name: '' });
                            props.onChangeGroups(items);
                        }}
                        onDelete={(id, index) => {
                            let items = [ ...props.collection.groups ];
                            items.splice(index, 1);

                            props.onChangeGroups(items);
                        }}
                        onReorder={(newItems) => {
                            props.onChangeGroups(newItems);
                        }}
                        items={props.collection.groups}
                        item={(editing, item, index) => editListItem('groups', editing, item, index) }
                        labels={{
                            add: __wprm( 'Add Group' ),
                            edit: __wprm( 'Edit Groups' ),
                        }}
                    />
                </div>
            </div>
            <div className="wprm-recipe-collections-edit-clear">
                <label>
                    { __wprm( 'Collection Items' ) }
                    <input
                        type="button"
                        value={ __wprm( 'Clear All Items' ) }
                        onClick={() => {
                            if ( confirm( __wprm( 'Are you sure you want to remove all items from this collection?' ) ) ) {
                                props.onClearItems();
                            }
                        }}
                    />
                </label>
            </div>
        </div>  
    );
}
export default EditStructureModal;