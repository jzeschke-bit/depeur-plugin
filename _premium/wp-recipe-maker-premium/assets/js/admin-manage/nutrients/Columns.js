import React, { Fragment } from 'react';
import he from 'he';
 
import Api from 'Shared/Api';
import Icon from 'Shared/Icon';
import { __wprm } from 'Shared/Translations';

import '../../../css/admin/manage/nutrients.scss';

export default {
    getColumns( datatable ) {
        let columns = [{
            Header: '',
            id: 'actions',
            sortable: false,
            width: 70,
            filterable: false,
            Cell: row => (
                <div className="wprm-admin-manage-actions">
                    <Icon
                        type="pencil"
                        title={ __wprm( 'Edit Nutrient' ) }
                        onClick={() => {
                            let nutrient = JSON.parse(JSON.stringify(row.original));

                            WPRM_Modal.open( 'nutrient', {
                                nutrient,
                                saveCallback: () => datatable.refreshData(),
                            } );
                        }}
                    />
                    {
                        'internal' !== row.original.type
                        &&
                        <Icon
                            type="trash"
                            title={ __wprm( 'Delete Custom Nutrient' ) }
                            onClick={() => {
                                if( confirm( `${ __wprm( 'Are you sure you want to delete' ) } "${row.original.label}"?` ) ) {
                                    Api.nutrient.delete(row.original.key).then(() => datatable.refreshData());
                                }
                            }}
                        />
                    }
                </div>
            ),
        },{
            Header: __wprm( 'Active' ),
            id: 'active',
            accessor: 'active',
            width: 50,
            sortable: false,
            filterable: false,
            Cell: row => {
                if ( 'serving_size' === row.original.key ) {
                    return null;
                }
                
                return (
                    <div className="wprm-manage-nutrients-active">
                        <input
                            type="checkbox"
                            checked={ true === row.value }
                            onChange={ () => {
                                Api.nutrient.update(true, {
                                    ...row.original,
                                    active: ! row.value,
                                }).then(() => datatable.refreshData());
                            } }
                        />
                    </div>
                )
            },
        },{
            Header: __wprm( 'Key' ),
            id: 'key',
            accessor: 'key',
            width: 200,
            sortable: false,
            filterable: false,
        },{
            Header: __wprm( 'Label' ),
            id: 'label',
            accessor: 'label',
            sortable: false,
            filterable: false,
            Cell: row => {
                if ( ! row.value ) {
                    return null;
                }

                return (
                    <div>
                        {
                            'internal' === row.original.type
                            ?
                            he.decode(row.value)
                            :
                            <Fragment>
                                { 'custom' === row.original.type ? `${ he.decode(row.value) } (${ __wprm( 'custom' ) })` : `${ he.decode(row.value) } (${ __wprm( 'calculated' ) }: ${row.original.calculation})` }
                            </Fragment>
                        }
                    </div>
                )
            },
        },{
            Header: __wprm( 'Daily Need' ),
            id: 'daily',
            accessor: 'daily',
            width: 100,
            sortable: false,
            filterable: false,
            Cell: row => (
                <div className="wprm-manage-nutrients-daily">{ row.value ? row.value : null }</div>
            ),
        },{
            Header: __wprm( 'Unit' ),
            id: 'unit',
            accessor: 'unit',
            width: 100,
            sortable: false,
            filterable: false,
            Cell: row => 'serving_size' !== row.original.key && row.value ? he.decode(row.value) : null,
        },{
            Header: __wprm( 'Order' ),
            id: 'order',
            accessor: 'order',
            width: 100,
            sortable: false,
            filterable: false,
        }];

        return columns;
    }
};