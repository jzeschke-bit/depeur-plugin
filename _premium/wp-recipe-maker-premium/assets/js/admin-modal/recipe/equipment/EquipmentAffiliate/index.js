import React, { Component } from 'react';

import '../../../../../css/admin/modal/recipe/equipment-affiliate.scss';

import FieldContainer from 'Modal/fields/FieldContainer';
import FieldRadio from 'Modal/fields/FieldRadio';
import { __wprm } from 'Shared/Translations';

import Api from 'Shared/Api';
import EquipmentItem from './EquipmentItem';

export default class EquipmentAffiliate extends Component {
    constructor(props) {
        super(props);

        this.state = {
            isUpdating: false,
        }
    }

    componentDidMount() {
        if ( wprm_admin.addons.premium ) {
            this.getAffiliateData();
        }
    }

    getAffiliateData() {
        let getDataFor = {};

        for ( let i = 0; i < this.props.equipment.length; i++ ) {
            const equipment = this.props.equipment[ i ];

            if ( equipment.name && ( ! equipment.hasOwnProperty( 'affiliate' ) || false === equipment.affiliate ) ) {
                getDataFor[ i ] = equipment.name;
            }
        }

        if ( 0 < Object.keys( getDataFor ).length ) {
            const updatingIndexes = Object.keys( getDataFor ).map( (index) => parseInt( index ) );

            this.setState({
                isUpdating: updatingIndexes,
            }, () => {
                Api.equipmentAffiliate.get( getDataFor ).then((data) => {
                    if ( data && data.affiliate ) {
                        let newEquipment = JSON.parse( JSON.stringify( this.props.equipment ) );
    
                        for ( let index in data.affiliate ) {
                            newEquipment[ parseInt( index ) ].affiliate = data.affiliate[ index ];
                        }
    
                        this.props.onRecipeChange( {
                            equipment: newEquipment
                        } );
                    }

                    this.setState({
                        isUpdating: false,
                    });
                });
            });
        }
    }

    render() {
        const equipment = this.props.equipment.filter((field) => field.name );
        if ( ! equipment.length ) {
            return (
                <p>{ __wprm( 'No equipment set for this recipe.' ) }</p>
            );
        }

        return (
            <div className="wprm-admin-modal-field-equipment-affiliate-container">
                <table
                    className="wprm-admin-modal-field-equipment-affiliate-items"
                >
                    <thead>
                    <tr>
                        <th>{ __wprm( 'Name' ) }</th>
                        <th>{ __wprm( 'Regular Link' ) }</th>
                        <th>{ __wprm( 'Image' ) }</th>
                        <th>{ __wprm( 'HTML Code' ) }</th>
                    </tr>
                    </thead>
                    <tbody>
                    {
                        this.props.equipment.map((item, index) => {
                            if ( ! item.name ) {
                                return null;
                            }

                            return (
                                <EquipmentItem
                                    equipment={ item }
                                    isUpdating={ this.state.isUpdating && this.state.isUpdating.includes( index ) }
                                    key={ index }
                                />
                            )
                        })
                    }
                    </tbody>
                </table>
                <button
                    type="button"
                    className="button button-primary button-compact"
                    onClick={() => {
                        this.props.openSecondaryModal('equipment-affiliate', {
                            equipment: this.props.equipment,
                            onEquipmentChange: (equipment) => {
                                this.props.onRecipeChange({
                                    equipment,
                                });
                            },
                        });
                    }}
                    disabled={ false !== this.state.isUpdating }
                >{ __wprm( 'Edit Affiliate Fields' ) }</button>
            </div>
        );
    }
}