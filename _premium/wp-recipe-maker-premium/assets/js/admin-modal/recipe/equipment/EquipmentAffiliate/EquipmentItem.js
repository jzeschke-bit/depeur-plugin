import React, { Fragment } from 'react';

import striptags from 'striptags';

import Loader from 'Shared/Loader';
import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';

const EquipmentItem = (props) => {
    const { equipment } = props;

    const usingEafl = window.hasOwnProperty( 'EAFL_Modal' );
    const hasEafl = usingEafl && equipment.affiliate && equipment.affiliate.eafl;

    return (
        <tr>
            <td>{ striptags( equipment.name ) }</td>
            {
                props.isUpdating
                ?
                <td colSpan="3"><Loader /></td>
                :
                <Fragment>
                    <td>
                        {
                            hasEafl
                            ?
                            <div className="wprm-admin-modal-field-equipment-affiliate-item-eafl">
                                EAFL #{ equipment.affiliate.eafl.id } - <a href={ equipment.affiliate.eafl.url } target="_blank">{ equipment.affiliate.eafl.name }</a>
                            </div>
                            :
                            <Fragment>
                                {
                                    equipment.affiliate && equipment.affiliate.link
                                    ?
                                    <div className="wprm-admin-modal-field-equipment-affiliate-item-link">
                                        <a href={ equipment.affiliate.link } target="_blank">{ equipment.affiliate.link }</a>
                                    </div>
                                    :
                                    <div className="wprm-admin-modal-field-equipment-affiliate-item-none">{ __wprm( 'No link set' ) }</div>
                                }
                            </Fragment>
                        }
                    </td>
                    <td>
                        {
                            equipment.affiliate && equipment.affiliate.image_id && equipment.affiliate.image_url
                            ?
                            <div className="wprm-admin-modal-field-equipment-affiliate-item-image">
                                <img
                                    src={ equipment.affiliate.image_url }
                                />
                            </div>
                            :
                            <div className="wprm-admin-modal-field-equipment-affiliate-item-none">n/a</div>
                        }
                    </td>
                    <td>
                        {
                            equipment.affiliate && equipment.affiliate.html
                            ?
                            <div className="wprm-admin-modal-field-equipment-affiliate-item-html">
                                <textarea rows="1" disabled="disabled">{ equipment.affiliate.html }</textarea>
                            </div>
                            :
                            <div className="wprm-admin-modal-field-equipment-affiliate-item-none">{ __wprm( 'No HTML set' ) }</div>
                        }
                    </td>
                </Fragment>
            }
        </tr>
    );
}
export default EquipmentItem;