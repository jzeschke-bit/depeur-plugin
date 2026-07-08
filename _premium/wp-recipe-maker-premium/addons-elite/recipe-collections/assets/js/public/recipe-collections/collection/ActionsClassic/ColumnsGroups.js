import React, { Component, Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import EditList from '../../general/EditList';

export default class ColumnsGroups extends Component {

    render() {
        const editListItem = (type, editing, item, index) => (
            <Fragment>
                {
                    editing
                    ?
                    <input
                        type="text"
                        value={item.name}
                        onChange={(event) => {
                            let items = 'columns' === type ? [ ...this.props.columns ] : [ ...this.props.groups ];

                            items[index] = {
                                ...items[index],
                                name: event.target.value,
                            }

                            if ( 'columns' === type ) {
                                this.props.onChangeColumns(items);
                            } else {
                                this.props.onChangeGroups(items);
                            }
                        }}
                    />
                    :
                    item.name
                }
            </Fragment>
        );

        return (
            <Fragment>
                <EditList
                    type='column'
                    onAdd={() => {
                        let items = [ ...this.props.columns ];
                        let maxId = Math.max.apply( Math, items.map( function(item) { return item.id; } ) );
                        maxId = maxId < 0 ? -1 : maxId;

                        items.push({ id: maxId + 1, name: '' });
                        this.props.onChangeColumns(items);
                    }}
                    onDelete={(id, index) => {
                        let items = [ ...this.props.columns ];
                        items.splice(index, 1);

                        this.props.onChangeColumns(items);
                    }}
                    onReorder={(newItems) => {
                        this.props.onChangeColumns(newItems);
                    }}
                    items={this.props.columns}
                    item={(editing, item, index) => editListItem('columns', editing, item, index) }
                    labels={{
                        add: __wprm( 'Add Column' ),
                        edit: __wprm( 'Edit Columns' ),
                    }}
                />
                <EditList
                    type='group'
                    onAdd={() => {
                        let items = [ ...this.props.groups ];
                        let maxId = Math.max.apply( Math, items.map( function(item) { return item.id; } ) );
                        maxId = maxId < 0 ? -1 : maxId;

                        items.push({ id: maxId + 1, name: '' });
                        this.props.onChangeGroups(items);
                    }}
                    onDelete={(id, index) => {
                        let items = [ ...this.props.groups ];
                        items.splice(index, 1);

                        this.props.onChangeGroups(items);
                    }}
                    onReorder={(newItems) => {
                        this.props.onChangeGroups(newItems);
                    }}
                    items={this.props.groups}
                    item={(editing, item, index) => editListItem('groups', editing, item, index) }
                    labels={{
                        add: __wprm( 'Add Group' ),
                        edit: __wprm( 'Edit Groups' ),
                    }}
                />
            </Fragment>
        );
    }
}
