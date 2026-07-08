import React, { Fragment } from 'react';
import he from 'he';
 
import TextFilter from 'Manage/general/TextFilter';
import bulkEditCheckbox from 'Manage/general/bulkEditCheckbox';
import Api from 'Shared/Api';
import Icon from 'Shared/Icon';
import Tooltip from 'Shared/Tooltip';
import { __wprm } from 'Shared/Translations';

export default {
    getColumns( datatable ) {
        let columns = [
            bulkEditCheckbox( datatable ),
            {
                Header: __wprm( 'Sort:' ),
                id: 'actions',
                headerClassName: 'wprm-admin-table-help-text',
                sortable: false,
                width: 130,
                Filter: () => (
                    <div>
                        { __wprm( 'Filter:' ) }
                    </div>
                ),
                Cell: row => (
                    <div className="wprm-admin-manage-actions">
                        <Icon
                            type="pencil"
                            title={ __wprm( 'Edit Saved Collection' ) }
                            onClick={() => {
                                const url = `${wprmp_admin.manage.collections_url}&id=${row.original.id}`;
                                window.location = url;
                            }}
                        />
                        <Icon
                            type="reload"
                            title={ __wprm( 'Reload Recipes' ) }
                            onClick={() => {
                                Api.collection.reload(row.original.id).then(() => datatable.refreshData());
                            }}
                        />
                        <Icon
                            type="duplicate"
                            title={ __wprm( 'Duplicate Saved Collection' ) }
                            onClick={() => {
                                const url = `${wprmp_admin.manage.collections_url}&action=duplicate&id=${row.original.id}`;
                                window.location = url;
                            }}
                        />
                        <Icon
                            type="trash"
                            title={ __wprm( 'Delete Saved Collection' ) }
                            onClick={() => {
                                if( confirm( `${ __wprm( 'Are you sure you want to delete' ) } "${row.original.name}"?` ) ) {
                                    Api.collection.delete(row.original.id).then(() => datatable.refreshData());
                                }
                            }}
                        />
                    </div>
                ),
            },{
                Header: __wprm( 'ID' ),
                id: 'id',
                accessor: 'id',
                width: 65,
                Filter: (props) => (<TextFilter {...props}/>),
            },{
                Header: __wprm( 'Date' ),
                id: 'date',
                accessor: 'date',
                width: 150,
                Filter: (props) => (<TextFilter {...props}/>),
            },{
                Header: __wprm( 'Name' ),
                id: 'name',
                accessor: 'name',
                Filter: (props) => (<TextFilter {...props}/>),
                Cell: row => row.value ? he.decode(row.value) : null,
            },{
                Header: __wprm( 'Description' ),
                id: 'description',
                accessor: 'description',
                Filter: (props) => (<TextFilter {...props}/>),
                Cell: row => {                    
                    if ( ! row.value ) {
                        return ( <div></div> );
                    }
                    return ( <div dangerouslySetInnerHTML={ { __html: row.value } } /> );
                },
                width: 300,
            },{
                Header: __wprm( 'Category' ),
                id: 'category',
                accessor: 'category',
                width: 150,
                Filter: (props) => (<TextFilter {...props}/>),
                Cell: row => {
                    return (
                        <div className="wprm-manage-saved-collections-container">
                            <Icon
                                type="pencil"
                                title={ __wprm( 'Change Category' ) }
                                onClick={() => {
                                    const newCategory = prompt( `${ __wprm( 'What do you want to be the category group for' ) } "${row.original.name}"?`, row.value );
                                    if( false !== newCategory ) {
                                        Api.collection.setCategory( row.original.id, newCategory ).then(() => datatable.refreshData() );
                                    }
                                }}
                            />
                            {
                                row.value
                                ?
                                <span>{ row.value }</span>
                                :
                                null
                            }
                        </div>
                    )
                },
            },{
                Header: __wprm( 'Default' ),
                id: 'default',
                accessor: 'default',
                filterable: false,
                sortable: false,
                width: 80,
                Cell: (row) => (
                    <Tooltip
                        content={ __wprm( 'Enable to make this a default collection for new users. Does not affect those who have used the collections feature before.' ) }
                    >
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ (e) => {
                                Api.collection.update(row.original.id, {
                                    ...row.original,
                                    default: e.target.checked,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </Tooltip>
                ),
            },{
                Header: __wprm( 'Push to All' ),
                id: 'push',
                accessor: 'push',
                filterable: false,
                sortable: false,
                width: 80,
                Cell: (row) => (
                    <Tooltip
                        content={ __wprm( 'Enable to push this collection to everyone using the collections feature. Will affect both new and existing users and add this collection to their list.' ) }
                    >
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ (e) => {
                                Api.collection.update(row.original.id, {
                                    ...row.original,
                                    push: e.target.checked,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </Tooltip>
                ),
            },{
                Header: __wprm( 'Fixed' ),
                id: 'fixed',
                accessor: 'fixed',
                filterable: false,
                sortable: false,
                width: 80,
                Cell: (row) => (
                    <Tooltip
                        content={ __wprm( 'Enable to mark this collection as fixed. Fixed saved collections will always show up in the collections list of a user and cannot be edited by them.' ) }
                    >
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ (e) => {
                                Api.collection.update(row.original.id, {
                                    ...row.original,
                                    fixed: e.target.checked,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </Tooltip>
                ),
            },{
                Header: __wprm( 'Template' ),
                id: 'template',
                accessor: 'template',
                filterable: false,
                sortable: false,
                width: 80,
                Cell: (row) => (
                    <Tooltip
                        content={ __wprm( 'Enable to make this saved collection show up as an option after clicking "Add Collection". This would usually be an empty collection with a specific structure like "Empty Week Plan".' ) }
                    >
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ (e) => {
                                Api.collection.update(row.original.id, {
                                    ...row.original,
                                    template: e.target.checked,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </Tooltip>
                ),
            },{
                Header: __wprm( 'Quick Add' ),
                id: 'quick_add',
                accessor: 'quick_add',
                filterable: false,
                sortable: false,
                width: 80,
                Cell: (row) => (
                    <Tooltip
                        content={ __wprm( 'Enable to make this saved collection show up after clicking on "Add Pre-made Collection". Can be used to give users easy access to the meal plans you create.' ) }
                    >
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ (e) => {
                                Api.collection.update(row.original.id, {
                                    ...row.original,
                                    quick_add: e.target.checked,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </Tooltip>
                ),
            },{
                Header: __wprm( 'Order' ),
                id: 'order',
                accessor: 'order',
                filterable: false,
                width: 80,
                Cell: (row) => {
                    const order = row.value ? row.value : 0;

                    return (
                        <a
                            href="#"
                            onClick={ (e) => {
                                e.preventDefault();
                                const newOrder = prompt( __wprm( 'What do you want the new order to be?' ), order );

                                if ( null !== newOrder ) {
                                    let newOrderNumber = parseInt( newOrder );

                                    if ( isNaN( newOrderNumber ) ) {
                                        newOrderNumber = 0;
                                    }

                                    Api.collection.update(row.original.id, {
                                        ...row.original,
                                        order: newOrderNumber,
                                    }).then(() => datatable.refreshData());
                                }
                            } }
                        >{ order }</a>
                    )
                },
            },{
                Header: __wprm( 'Save Collection Link' ),
                id: 'saveLink',
                accessor: 'saveLink',
                filterable: false,
                sortable: false,
                Cell: row => row.value ? <input type="type" value={ row.value } style={ { width: '100%', fontSize: '0.9em', padding: 3, border: 'none' } } readOnly /> : null,
            },{
                Header: __wprm( '# Items' ),
                id: 'nbrItems',
                accessor: 'nbrItems',
                width: 65,
                Filter: (props) => (<TextFilter {...props}/>),
            }
        ];

        return columns;
    }
};