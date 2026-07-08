import React, { Component } from 'react';
import ReactTable from 'react-table';
import 'react-table/react-table.css';

import Icon from 'Shared/Icon';
import { __wprm } from 'Shared/Translations';
import { downloadToCsv } from 'Shared/CSV';

let reportData = false;

if ( typeof window.wprm_reports_data !== 'undefined' ) {
    reportData = window.wprm_reports_data;
}

export default class App extends Component {
    constructor(props) {
        super(props);

        this.state = {
            valueType: 'total',
        };
    }

    render() {
        if ( false === reportData ) {
            return (
                <div>{ __wprm( 'No data found.' ) }</div>
            );
        }

        const userData = reportData.user_report;
        const itemData = Object.values( reportData.items_report );
        const recipeData = reportData.recipe_report;
        const itemHeaders = [
            __wprm( 'Metric' ),
            __wprm( 'Average' ),
            __wprm( 'Median' ),
            __wprm( 'Maximum (1 user)' ),
            __wprm( 'Total (all users)' ),
        ];
        const recipeHeaders = [
            __wprm( 'Recipe Name' ),
            __wprm( '# Users' ),
            __wprm( '# Added' ),
            __wprm( 'Last 31 Days' ),
            __wprm( 'Last 7 Days' ),
        ];

        const itemColumns = [{
            Header: __wprm( 'Metric' ),
            accessor: 'name',
            width: 440,
        },{
            Header: __wprm( 'Average' ),
            accessor: 'nbr_average',
            width: 120,
            Cell: row => (
                <div>
                    { Math.ceil( row.value * 100 ) / 100 }
                </div>
            ),
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( 'Median' ),
            accessor: 'nbr_median',
            width: 120,
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( 'Maximum (1 user)' ),
            accessor: 'nbr_max',
            width: 120,
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( 'Total (all users)' ),
            accessor: 'nbr_total',
            width: 120,
            className: 'wprm-report-table-center',
        }];

        const recipeColumns = [{
            Header: __wprm( 'Sort:' ),
            id: 'actions',
            accessor: 'id',
            headerClassName: 'wprm-admin-table-help-text',
            sortable: false,
            width: 40,
            Filter: () => (
                <div>
                    { __wprm( 'Filter:' ) }
                </div>
            ),
            Cell: row => (
                <div className="wprm-report-table-actions">
                    <Icon
                        type="pencil"
                        title={ __wprm( 'Edit Recipe' ) }
                        onClick={() => {
                            WPRM_Modal.open( 'recipe', {
                                recipeId: row.value,
                            } );
                        }}
                    />
                </div>
            ),
        },{
            Header: __wprm( 'Recipe Name' ),
            accessor: 'name',
            width: 400,
        },{
            Header: __wprm( '# Users' ),
            accessor: 'users',
            width: 120,
            filterable: false,
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( '# Added' ),
            accessor: 'lifetime',
            width: 120,
            filterable: false,
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( 'Last 31 Days' ),
            accessor: '31_days',
            width: 120,
            filterable: false,
            className: 'wprm-report-table-center',
        },{
            Header: __wprm( 'Last 7 Days' ),
            accessor: '7_days',
            width: 120,
            filterable: false,
            className: 'wprm-report-table-center',
        }];
        
        return (
            <div>
                <p>The report is based on <strong>{ userData.users_using_feature } users</strong> who are actively using the feature out of { userData.users_total } total users. This means that <strong>{ Math.ceil( userData.users_using_feature_percentage * 100 ) / 100 }%</strong> of users have used the collections feature at least once.</p>
                <div className="wprm-report-table-header">
                    <h2>{ __wprm( 'Collections Usage' ) }</h2>
                    <button
                        type="button"
                        className="button button-secondary button-compact"
                        onClick={() => {
                            const rows = itemData.map((item) => [
                                item.name,
                                Math.ceil( ( Number( item.nbr_average ) || 0 ) * 100 ) / 100,
                                item.nbr_median,
                                item.nbr_max,
                                item.nbr_total,
                            ]);

                            downloadToCsv( 'recipe-collections-usage', itemHeaders, rows );
                        }}
                    >
                        { __wprm( 'Download to CSV' ) }
                    </button>
                </div>
                <ReactTable
                    data={itemData}
                    columns={itemColumns}
                    showPagination={false}
                    defaultPageSize={itemData.length}
                    sortable={false}
                    filterable={false}
                    defaultFilterMethod={(filter, row, column) => {
                        const id = filter.pivotId || filter.id;
                        return row[id] !== undefined
                            ? String(row[id]).toLocaleLowerCase().includes(filter.value.toLowerCase())
                            : true;
                    }}
                    resizable={false}
                    className="wprm-admin-table wprm-report-table -highlight"
                />
                <div className="wprm-report-table-header">
                    <h2>{ __wprm( 'Recipes used in Collections' ) }</h2>
                    <button
                        type="button"
                        className="button button-secondary button-compact"
                        onClick={() => {
                            const rows = recipeData.map((recipe) => [
                                recipe.name,
                                recipe.users,
                                recipe.lifetime,
                                recipe['31_days'],
                                recipe['7_days'],
                            ]);

                            downloadToCsv( 'recipe-collections-recipes', recipeHeaders, rows );
                        }}
                    >
                        { __wprm( 'Download to CSV' ) }
                    </button>
                </div>
                <ReactTable
                    data={recipeData}
                    columns={recipeColumns}
                    showPagination={false}
                    defaultPageSize={recipeData.length}
                    defaultSorted={[
                        {
                            id: 'count',
                            desc: true,
                        },
                    ]}
                    filterable={true}
                    defaultFilterMethod={(filter, row, column) => {
                        const id = filter.pivotId || filter.id;
                        return row[id] !== undefined
                            ? String(row[id]).toLocaleLowerCase().includes(filter.value.toLowerCase())
                            : true;
                    }}
                    resizable={false}
                    className="wprm-admin-table wprm-report-table -highlight"
                />
                <div className="wprm-report-table-legend">
                    <div className="wprm-report-table-legend-item">
                        <div className="wprm-report-table-legend-label">{ __wprm( '# Users' ) }</div>
                        <div className="wprm-report-table-legend-description">{ __wprm( 'Number of users that have this recipe in one of their collections at least once' ) }</div>
                    </div>
                    <div className="wprm-report-table-legend-item">
                        <div className="wprm-report-table-legend-label">{ __wprm( '# Added' ) }</div>
                        <div className="wprm-report-table-legend-description">{ __wprm( 'Total times that this recipe can be found in a collection (could be multiple times per user)' ) }</div>
                    </div>
                    <div className="wprm-report-table-legend-item">
                        <div className="wprm-report-table-legend-label">{ __wprm( 'Last X Days' ) }</div>
                        <div className="wprm-report-table-legend-description">{ __wprm( 'Total times that this recipe can be found in a collection, having been added to that collection during this timeframe' ) }</div>
                    </div>
                </div>
            </div>
        );
    }
}
