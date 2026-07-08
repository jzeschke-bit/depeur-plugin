import React from 'react';
import Select from 'react-select';

import InputBlock from './InputBlock';
import RecipeImageBlock from './RecipeImageBlock';
import TextareaBlock from './TextareaBlock';

const CustomFieldBlock = (props) => {
    // Message if no custom fields set.
    if ( ! wprm_admin_modal.custom_fields || ! wprm_admin_modal.custom_fields.fields || ! Object.keys( wprm_admin_modal.custom_fields.fields ).length ) {
        return (
            <div className={`wprmprs-layout-block-input wprmprs-layout-block-${props.block.type}`}>
                <label className="wprmprs-form-label">Custom Field</label>
                <p>You need to create a custom field through the WP Recipe Maker > Manage page first.</p>
            </div>
        )
    }

    // Dropdown if no custom field selected yet.
    if ( ! props.block.field || ! wprm_admin_modal.custom_fields.fields.hasOwnProperty( props.block.field ) ) {
        return (
            <div className={`wprmprs-layout-block-input wprmprs-layout-block-${props.block.type}`}>
                <label className="wprmprs-form-label">Custom Field</label>
                <Select
                    className="wprmprs-layout-block-select"
                    placeholder="Select the custom field to display..."
                    value={false}
                    onChange={(option) => {
                        props.onEdit('field', option.value);
                        props.onEdit('label', option.label);
                    }}
                    options={
                        Object.values( wprm_admin_modal.custom_fields.fields ).map((field) => {
                            return {
                                value: field.key,
                                label: field.name,
                            }
                        })
                    }
                    clearable={false}
                />
            </div>
        );
    }

    const fieldOptions = wprm_admin_modal.custom_fields.fields[ props.block.field ];

    switch( fieldOptions.type ) {
        case 'text':
        case 'link':
        case 'email':
            return ( <InputBlock {...props } /> );
        case 'textarea':
            return ( <TextareaBlock {...props } /> );
        case 'image':
            return ( <RecipeImageBlock {...props } /> );
    }

    return null;
}

export default CustomFieldBlock;