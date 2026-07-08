import React, { Fragment } from 'react';

import striptags from 'striptags';

import { __wprm } from 'Shared/Translations';
import FieldTextarea from 'Modal/fields/FieldTextarea';

const EquipmentItemHtml = (props) => {
    const { equipment } = props;

    return (
        <div className="wprm-admin-modal-field-equipment-affiliate-html-container">
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
            <div className="wprm-admin-modal-field-equipment-affiliate-html">
                <FieldTextarea
                    name="equipment-html"
                    value={ equipment.affiliate ? equipment.affiliate.html : '' }
                    onChange={ (html) => {
                        props.onChange( {
                            html,
                        } );
                    }}
                />
                <div className="wprm-admin-modal-field-equipment-affiliate-html-preview">
                    {
                        equipment.affiliate
                        && equipment.affiliate.html
                        &&
                        <div dangerouslySetInnerHTML={ { __html: equipment.affiliate.html } } />
                    }
                </div>
            </div>
        </div>
    );
}
export default EquipmentItemHtml;