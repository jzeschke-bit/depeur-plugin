import React, { Component } from 'react';

import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';

import Item from './Item';
import Api from 'Shared/Api';

import '../../../../css/admin/modal/recipe/products.scss';

export default class Products extends Component {
    constructor(props) {
        super(props);

        this.state = {
            isUpdating: false,
        }
    }

    componentDidMount() {
        if ( wprm_admin.addons.elite ) {
            this.updateProducts();
        }
    }

    updateProducts() {
        const useDefaultProductAmount = (
            'wprm_ingredient' === this.props.taxonomy
            && wprm_admin_modal
            && wprm_admin_modal.settings
            && wprm_admin_modal.settings.products_default_linked_ingredient_amount
        ) || (
            'wprm_equipment' === this.props.taxonomy
            && wprm_admin_modal
            && wprm_admin_modal.settings
            && wprm_admin_modal.settings.products_default_linked_equipment_amount
        );
        let getProductsFor = {};

        for ( let i = 0; i < this.props.items.length; i++ ) {
            const item = this.props.items[ i ];

            if ( ! item.hasOwnProperty( 'product' ) || false === item.product ) {
                getProductsFor[ i ] = {
                    name: item.name,
                }
            }
        }

        if ( 0 < Object.keys( getProductsFor ).length ) {
            const updatingIndexes = Object.keys( getProductsFor ).map( (index) => parseInt( index ) );

            this.setState({
                isUpdating: updatingIndexes,
            }, () => {
                Api.product.getAll( this.props.taxonomy, getProductsFor ).then((data) => {
                    if ( data && data.hasOwnProperty( 'products' ) ) {
                        let newItems = JSON.parse( JSON.stringify( this.props.items ) );
    
                        for ( let index in data.products ) {
                            const itemIndex = parseInt( index );
                            newItems[ itemIndex ].product = data.products[ index ];

                            if ( useDefaultProductAmount
                                && data.products[ index ]
                                && (
                                    ! newItems[ itemIndex ].hasOwnProperty( 'product_amount' )
                                    || '' === newItems[ itemIndex ].product_amount
                                    || newItems[ itemIndex ].product_amount_default
                                )
                            ) {
                                newItems[ itemIndex ].product_amount = '1';
                                newItems[ itemIndex ].product_amount_default = true;
                            }
                        }
    
                        // Update items and state.
                        this.props.onItemsChange(newItems);
                    }

                    this.setState({
                        isUpdating: false,
                    });
                });
            });
        }
    }

    render() {
        const { items } = this.props;
        const useDefaultProductAmount = (
            'wprm_ingredient' === this.props.taxonomy
            && wprm_admin_modal
            && wprm_admin_modal.settings
            && wprm_admin_modal.settings.products_default_linked_ingredient_amount
        ) || (
            'wprm_equipment' === this.props.taxonomy
            && wprm_admin_modal
            && wprm_admin_modal.settings
            && wprm_admin_modal.settings.products_default_linked_equipment_amount
        );
        const amountTooltip = useDefaultProductAmount
            ? __wprm( 'The exact amount of the product needed in the recipe. Positive values override the default, 0 hides the product for this recipe, and resetting it uses the global default of 1.' )
            : __wprm( 'The exact amount of the product needed in the recipe. Can be decimal numbers like 0.05 if you only need a small portion of the actual product. When empty or 0, the product will not be shown.' );

        if ( ! items.length ) {
            return (
                <p>{ __wprm( 'Nothing to add products to yet.' ) }</p>
            );
        }

        return (
            <div className="wprm-admin-modal-field-products-container">
                <table
                    className="wprm-admin-modal-field-products"
                    style={{ tableLayout: 'fixed', width: '100%' }}
                >
                    <thead>
                    <tr>
                        <th style={{ width: '25%' }}>{ __wprm( 'In Recipe' ) }</th>
                        <th style={{ width: '15%' }}>{ __wprm( 'Amount Needed' ) }
                            <Icon
                                type="question"
                                title={ amountTooltip }
                                className="wprm-admin-icon-help"
                            />
                        </th>
                        <th style={{ width: '60%' }}>{ __wprm( 'Product' ) }
                            <Icon
                                type="question"
                                title={ __wprm( 'Warning: changing the product can affect other recipes using this ingredient or equipment.' ) }
                                className="wprm-admin-icon-help"
                            />
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    {
                        items.map((item, index) => {
                            return (
                                <Item
                                    item={ item }
                                    taxonomy={ this.props.taxonomy }
                                    onItemChange={ ( changes ) => {
                                        let newItems = JSON.parse( JSON.stringify( this.props.items ) );

                                        newItems[ index ] = {
                                            ...item,
                                            ...changes,
                                        };

                                        this.props.onItemsChange( newItems );
                                    } }
                                    isUpdating={ this.state.isUpdating && this.state.isUpdating.includes( index ) }
                                    openSecondaryModal={ this.props.openSecondaryModal }
                                    key={ index }
                                />
                            )
                        })
                    }
                    </tbody>
                </table>
            </div>
        );
    }
}
