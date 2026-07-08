import React from 'react';
import he from 'he';

import Icon from 'Shared/Icon';
import TextFilter from 'Manage/general/TextFilter';
import { __wprm } from 'Shared/Translations';

export default {
    getColumns( datatable ) {
        let columns = [{
            Header: __wprm( 'Sort:' ),
            id: 'actions',
            headerClassName: 'wprm-admin-table-help-text',
            sortable: false,
            width: 65,
            Filter: () => (
                <div>
                    { __wprm( 'Filter:' ) }
                </div>
            ),
            Cell: row => (
                <div className="wprm-admin-manage-actions">
                    <Icon
                        type="pencil"
                        title={ __wprm( 'View and edit collections for this user' ) }
                        onClick={() => {
                            if ( ! wprmp_admin.manage.recipe_collections_link ) {
                                alert( 'You need to set the "Link to Collections Feature" setting on the WP Recipe Maker > Settings > Recipe Collections page.' );
                            } else {
                                const url = `${ wprmp_admin.manage.recipe_collections_link.replace( '/#/', '' ) }?wprmprc_user=${row.original.ID}`;
                                window.location = url;
                            }
                        }}
                    />
                </div>
            ),
        },{
            Header: __wprm( 'User ID' ),
            id: 'id',
            accessor: 'ID',
            width: 65,
            Filter: (props) => (<TextFilter {...props}/>),
        },{
            Header: __wprm( 'Display Name' ),
            id: 'display_name',
            accessor: 'data.display_name',
            width: 300,
            Filter: (props) => (<TextFilter {...props}/>),
            Cell: row => row.value ? he.decode(row.value) : null,
        },{
            Header: __wprm( 'Email' ),
            id: 'user_email',
            accessor: 'data.user_email',
            width: 300,
            Filter: (props) => (<TextFilter {...props}/>),
            Cell: row => row.value ? he.decode(row.value) : null,
        },{
            Header: __wprm( '# Collections' ),
            id: 'collections',
            accessor: 'data.collections',
            width: 150,
            sortable: false,
            Filter: ({ filter, onChange }) => (
                <select
                    onChange={event => onChange(event.target.value)}
                    style={{ width: '100%', fontSize: '1em' }}
                    value={filter ? filter.value : 'all'}
                >
                    <option value="all">{ __wprm( 'Show All' ) }</option>
                    <option value="yes">{ __wprm( 'Has Saved Collections' ) }</option>
                    <option value="no">{ __wprm( 'Does not have Saved Collections' ) }</option>
                </select>
            ),
            Cell: (row) => {
                return row.value.length; 
            },
        },{
            Header: __wprm( '# Items in Inbox' ),
            id: 'inbox',
            accessor: 'data.inbox',
            width: 150,
            sortable: false,
            filterable: false,
        },{
            Header: __wprm( '# Items in Collections' ),
            id: 'items',
            accessor: 'data.items',
            width: 150,
            sortable: false,
            filterable: false,
        }];

        return columns;
    }
};