import React, { Fragment } from 'react';

import striptags from 'striptags';

import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';
import FieldDropdown from 'Modal/fields/FieldDropdown';
import FieldText from 'Modal/fields/FieldText';

const EquipmentItemLink = (props) => {
    const { equipment } = props;

    const usingEafl = window.hasOwnProperty( 'EAFL_Modal' );
    const hasEafl = usingEafl && equipment.affiliate && equipment.affiliate.eafl;

    return (
        <div className="wprm-admin-modal-field-equipment-affiliate-link-container">
            <div className="wprm-admin-modal-field-equipment-affiliate-name">
                { striptags( equipment.name ) }
                {
                    props.hasChanged
                    &&
                    <div className="wprm-admin-modal-field-equipment-affiliate-link-count">
                        {
                            equipment.affiliate && false !== equipment.affiliate.count && 0 < equipment.affiliate.count - 1
                            ?
                            `${ equipment.affiliate.count - 1 } ${ __wprm( 'other recipe(s) affected' ) }`
                            :
                            __wprm( 'This can affect other recipes' )
                        }
                    </div>
                }
            </div>
            {
                usingEafl
                &&
                <Fragment>
                    {
                        hasEafl
                        ?
                        <Fragment>
                            <div className="wprm-admin-modal-field-equipment-affiliate-link-eafl">
                                <Icon
                                    type="eafl-link"
                                    title={ __wprm( 'Edit Link' ) }
                                    onClick={() => {
                                        EAFL_Modal.open('edit', {
                                            linkId: equipment.affiliate.eafl.id,
                                            saveCallback: (link) => {
                                                props.onChange( {
                                                    eafl: link,
                                                } );
                                            },
                                        });
                                    }}
                                />
                                &nbsp;
                                <Icon
                                    type="eafl-unlink"
                                    title={ __wprm( 'Remove Link' ) }
                                    onClick={() => {
                                        if( confirm( __wprm( 'Are you sure you want to delete this link?' ) ) ) {
                                            props.onChange( {
                                                eafl: false,
                                            } );
                                        }
                                    }}
                                />
                                &nbsp;EAFL #{ equipment.affiliate.eafl.id } - <a href={ equipment.affiliate.eafl.url } target="_blank">{ equipment.affiliate.eafl.name }</a>
                            </div>
                        </Fragment>
                        :
                        <Icon
                            type="eafl-link"
                            title={ __wprm( 'Set Affiliate Link' ) }
                            onClick={() => {
                                EAFL_Modal.open('insert', {
                                    insertCallback: function(link) {
                                        props.onChange( {
                                            eafl: link,
                                        } );
                                    },
                                    selectedText: equipment.name,
                                });
                            }}
                        />
                    }
                </Fragment>
            }
            {
                ! hasEafl
                &&
                <Fragment>
                    <FieldText
                        name="equipment-link"
                        type="url"
                        value={ equipment.affiliate ? equipment.affiliate.link : '' }
                        onChange={ (link) => {
                            props.onChange( {
                                link,
                            } );
                        }}
                    />
                    <FieldDropdown
                        options={ wprm_admin_modal.options.equipment_link_nofollow }
                        value={ equipment.affiliate && equipment.affiliate.nofollow ? equipment.affiliate.nofollow : 'default' }
                        onChange={ (nofollow) => {
                            props.onChange( {
                                nofollow,
                            } );
                        }}
                        width={ 200 }
                    />
                </Fragment>
            }
        </div>
    );
}
export default EquipmentItemLink;