import React, { Fragment } from 'react';

import striptags from 'striptags';

import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';
import Media from 'Modal/general/Media';

const EquipmentItemImage = (props) => {
    const { equipment } = props;

    const selectImage = (e) => {
        e.preventDefault();
                
        Media.selectImage((attachment) => {
            props.onChange( {
                image_id: attachment.id,
                image_url: attachment.url,
            } );
        });
    };

    return (
        <div className="wprm-admin-modal-field-equipment-affiliate-image-container">
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
            <div className="wprm-admin-modal-field-equipment-affiliate-image">
                {
                    equipment.affiliate && equipment.affiliate.image_id
                    ?
                    <Fragment>
                        <Icon
                            type="photo"
                            title={ __wprm( 'Edit Image' ) }
                            onClick={ selectImage }
                        />
                        <Icon
                            type="trash"
                            title={ __wprm( 'Remove Image' ) }
                            onClick={ () => {
                                props.onChange( {
                                    image_id: false,
                                    image_url: '',
                                } );
                            } }
                        />
                    </Fragment>
                    :
                    <Icon
                        type="photo"
                        title={ __wprm( 'Add Image' ) }
                        onClick={ selectImage }
                    />
                }
                {
                    equipment.affiliate && equipment.affiliate.image_id && equipment.affiliate.image_url
                    &&
                    <div className="wprm-admin-modal-field-equipment-affiliate-image-preview">
                        <img
                            src={ equipment.affiliate.image_url }
                        />
                    </div>
                }
            </div>
        </div>
    );
}
export default EquipmentItemImage;