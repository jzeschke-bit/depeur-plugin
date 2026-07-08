import React, { Component, Fragment } from 'react';

import '../../../../css/admin/modal/recipe/equipment-affiliate-edit.scss';

import Header from 'Modal/general/Header';
import Footer from 'Modal/general/Footer';
import { __wprm } from 'Shared/Translations';

import EquipmentItemLink from './EquipmentItemLink';
import EquipmentItemImage from './EquipmentItemImage';
import EquipmentItemHtml from './EquipmentItemHtml';

import Api from 'Shared/Api';

export default class EditEquipmentAffiliate extends Component {
    constructor(props) {
        super(props);

        this.state = {
            isSaving: false,
            equipment: JSON.parse( JSON.stringify( this.props.equipment ) ),
        };

        this.saveAffiliate = this.saveAffiliate.bind(this);
        this.hasChanged = this.hasChanged.bind(this);
    }

    saveAffiliate() {
        const equipmentToSave = this.state.equipment.filter( (item, index) => this.hasChanged( this.props.equipment[ index ], item ) );
        const detailsToSave = equipmentToSave.map((equipment) => ({
            name: equipment.name,
            affiliate: equipment.affiliate,
        }));
        
        this.setState({
            isSaving: true,
        }, () => {
            Api.equipmentAffiliate.save( detailsToSave ).then((data) => {
                if ( data ) {
                    this.props.onEquipmentChange(this.state.equipment);
                    this.props.maybeCloseModal();
                } else {
                    this.setState({
                        isSaving: false,
                    });
                }
            });
        });
    }

    hasChanged( original, current, type = 'all' ) {
        // Skip empty lines.
        if ( ! original || ! original.name ) {
            return false;
        }

        // No affiliate set for either.
        if ( false === original.affiliate && false === current.affiliate ) {
            return false;
        }
        
        // Affiliate set for one, but not the other.
        if ( ( false === original.affiliate && false !== current.affiliate ) || ( false === current.affiliate && false !== original.affiliate ) ) {
            return true;
        }

        // Affiliate information set for both, compare specific fields.
        if ( 'link' === type || 'all' === type ) {
            const originalEaflId = false === original.affiliate.eafl ? false : original.affiliate.eafl.id;
            const currentEaflId = false === current.affiliate.eafl ? false : current.affiliate.eafl.id;

            if ( originalEaflId !== currentEaflId ) {
                return true;
            }
            
            const originalNofollow = ! original.affiliate.nofollow ? 'default' : original.affiliate.nofollow;
            const currentNofollow = ! current.affiliate.nofollow ? 'default' : current.affiliate.nofollow;
            if ( originalNofollow !== currentNofollow ) {
                return true;
            }

            if ( original.affiliate.link !== current.affiliate.link ) {
                return true;
            }
        }

        if ( 'image' === type || 'all' === type ) {
            if ( original.affiliate.image_id !== current.affiliate.image_id ) {
                return true;
            }
        }

        if ( 'html' === type || 'all' === type ) {
            if ( original.affiliate.html !== current.affiliate.html ) {
                return true;
            }
        }

        // False if no specific changes were found.
        return false;
    }

    render() {
        let changesMade = this.state.equipment.some( (item, index) => this.hasChanged( this.props.equipment[ index ], item ) );

        return (
            <Fragment>
                <Header
                    onCloseModal={ this.props.maybeCloseModal }
                >
                    { __wprm( 'Editing Equipment Affiliate Fields' ) }
                </Header>
                <div className="wprm-admin-modal-field-equipment-affiliate-container wprm-admin-modal-field-equipment-affiliate-edit-container">
                    <div className="wprm-admin-modal-field-equipment-affiliate">
                    <p>{ __wprm( 'The fields you set here will affect all recipes using this equipment.' ) }</p>
                    <h3>{ __wprm( 'Regular Links' ) }</h3>
                    <div className="wprm-admin-modal-field-equipment-affiliate-links">
                    {
                        this.state.equipment.map((item, index) => {
                            if ( ! item.name ) {
                                return null;
                            }
        
                            return (
                                <EquipmentItemLink
                                    equipment={ item }
                                    onChange={(affiliate) => {
                                        let newEquipment = JSON.parse( JSON.stringify( this.state.equipment ) );
                                        newEquipment[ index ].affiliate = {
                                            ...newEquipment[ index ].affiliate,
                                            ...affiliate,
                                        };

                                        this.setState({
                                            equipment: newEquipment,
                                        });
                                    }}
                                    hasChanged={ this.hasChanged( this.props.equipment[ index ], item, 'link' ) }
                                    key={ index }
                                />
                            )
                        })
                    }
                    </div>
                    <h3>{ __wprm( 'Images' ) }</h3>
                    <div className="wprm-admin-modal-field-equipment-affiliate-links">
                    {
                        this.state.equipment.map((item, index) => {
                            if ( ! item.name ) {
                                return null;
                            }
        
                            return (
                                <EquipmentItemImage
                                    equipment={ item }
                                    onChange={(affiliate) => {
                                        let newEquipment = JSON.parse( JSON.stringify( this.state.equipment ) );
                                        newEquipment[ index ].affiliate = {
                                            ...newEquipment[ index ].affiliate,
                                            ...affiliate,
                                        };

                                        this.setState({
                                            equipment: newEquipment,
                                        });
                                    }}
                                    hasChanged={ this.hasChanged( this.props.equipment[ index ], item, 'image' ) }
                                    key={ index }
                                />
                            )
                        })
                    }
                    </div>
                    <h3>{ __wprm( 'HTML Code' ) }</h3>
                    <div className="wprm-admin-modal-field-equipment-affiliate-links">
                    {
                        this.state.equipment.map((item, index) => {
                            if ( ! item.name ) {
                                return null;
                            }
        
                            return (
                                <EquipmentItemHtml
                                    equipment={ item }
                                    onChange={(affiliate) => {
                                        let newEquipment = JSON.parse( JSON.stringify( this.state.equipment ) );
                                        newEquipment[ index ].affiliate = {
                                            ...newEquipment[ index ].affiliate,
                                            ...affiliate,
                                        };

                                        this.setState({
                                            equipment: newEquipment,
                                        });
                                    }}
                                    hasChanged={ this.hasChanged( this.props.equipment[ index ], item, 'html' ) }
                                    key={ index }
                                />
                            )
                        })
                    }
                    </div>
                    <p>{ __wprm( 'Images and HTML code only show up when the Equipment block is set to the "Images" display style in the Template Editor.' ) }</p>
                    </div>
                </div>
                <Footer
                    savingChanges={ this.state.isSaving }
                >
                    <button
                        className="button button-secondary button-compact"
                        onClick={ this.props.maybeCloseModal }
                    >
                        { __wprm( 'Cancel' ) }
                    </button>
                    <button
                        className="button button-primary button-compact"
                        onClick={ this.saveAffiliate }
                        disabled={ ! changesMade }
                    >
                        { __wprm( 'Save Changes' ) }
                    </button>
                </Footer>
            </Fragment>
        );
    }
}