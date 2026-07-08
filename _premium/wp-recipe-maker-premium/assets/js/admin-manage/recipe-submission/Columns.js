import React from 'react';
import he from 'he';
 
import bulkEditCheckbox from 'Manage/general/bulkEditCheckbox';
import TextFilter from 'Manage/general/TextFilter';
import Api from 'Shared/Api';
import Icon from 'Shared/Icon';
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
                        title={ __wprm( 'Edit Recipe Submission' ) }
                        onClick={() => {
                            WPRM_Modal.open( 'recipe', {
                                recipe: row.original,
                                saveCallback: () => datatable.refreshData(),
                            } );
                        }}
                    />
                    <Icon
                        type="checkmark"
                        title={ __wprm( 'Approve Submission' ) }
                        onClick={() => {
                            Api.submission.approve( row.original.id, false ).then(() => datatable.refreshData());
                        }}
                    />
                    <Icon
                        type="checkbox-alternate"
                        title={ __wprm( 'Approve Submission & Add to new Post' ) }
                        onClick={() => {
                            Api.submission.approve( row.original.id, true ).then((data) => {
                                if ( data && data.edit_link ) {
                                    window.location = data.edit_link;
                                } else {
                                    datatable.refreshData();
                                }
                            })
                        }}
                    />
                    <Icon
                        type="trash"
                        title={ __wprm( 'Delete Recipe Submission' ) }
                        onClick={() => {
                            if( confirm( `${ __wprm( 'Are you sure you want to delete' ) } "${row.original.name}"?` ) ) {
                                Api.recipe.delete(row.original.id).then(() => datatable.refreshData());
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
            Header: __wprm( 'User' ),
            id: 'submission_author',
            accessor: 'submission_author',
            width: 300,
            sortable: false,
            filterable: false,
            Cell: row => {
                const user = row.value;
                if ( ! user ) {
                    return null;
                }

                const name = user.name ? user.name : ( row.original.submission_author_user_name ? row.original.submission_author_user_name : '' );

                return (
                    <div className="wprm-admin-manage-recipe-submission-user">
                        <div className="wprm-admin-manage-recipe-submission-user-name">
                            {
                                user.id
                                ?
                                <a href={ row.original.submission_author_user_link } target="_blank">#{ user.id }</a>
                                :
                                null
                            }
                            {
                                name
                                ?
                                <span> - { name }</span>
                                :
                                null
                            }
                        </div>
                        {
                            user.email
                            ?
                            <div className="wprm-admin-manage-recipe-submission-user-email">{ user.email }</div>
                            :
                            null
                        }
                    </div>
                )
            },
        },{
            Header: __wprm( 'Name' ),
            id: 'name',
            accessor: 'name',
            Filter: (props) => (<TextFilter {...props}/>),
            Cell: row => row.value ? he.decode(row.value) : null,
        }];

        return columns;
    }
};