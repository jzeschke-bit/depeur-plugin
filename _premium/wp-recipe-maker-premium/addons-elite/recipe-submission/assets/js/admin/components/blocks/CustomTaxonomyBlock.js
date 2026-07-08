import React from 'react';
import Select from 'react-select';
import shared from './_shared';

const CustomTaxonomyBlock = (props) => {
    // Message if no custom taxonomies set.
    if ( ! wprm_admin_modal.categories || ! Object.keys( wprm_admin_modal.categories ).length ) {
        return (
            <div className={`wprmprs-layout-block-input wprmprs-layout-block-${props.block.type}`}>
                <label className="wprmprs-form-label">Custom Taxonomy</label>
                <p>You need to create a custom taxonomy through the WP Recipe Maker > Manage page first.</p>
            </div>
        )
    }

    // Dropdown if no custom field selected yet.
    if ( ! props.block.field || ! wprm_admin_modal.categories.hasOwnProperty( props.block.field ) ) {
        return (
            <div className={`wprmprs-layout-block-input wprmprs-layout-block-${props.block.type}`}>
                <label className="wprmprs-form-label">Custom Taxonomy</label>
                <Select
                    className="wprmprs-layout-block-select"
                    placeholder="Select the custom taxonomy to display..."
                    value={false}
                    onChange={(option) => {
                        props.onEdit('field', option.value);
                        props.onEdit('label', option.label);
                    }}
                    options={
                        Object.keys( wprm_admin_modal.categories ).map((taxonomy) => {
                            return {
                                value: taxonomy,
                                label: wprm_admin_modal.categories[ taxonomy ].label,
                            }
                        })
                    }
                    clearable={false}
                />
            </div>
        );
    }

    // Input type.
    const inputType = props.block.hasOwnProperty( 'input_type' ) && props.block.input_type ? props.block.input_type : 'text';

    const inputTypeOptions = [
        {
            value: 'text',
            label: 'Text',
        },
        {
            value: 'single-select',
            label: 'Single-select Dropdown',
        },
        {
            value: 'single-isotope',
            label: 'Single-select Isotope',
        },
        {
            value: 'multiple-isotope',
            label: 'Multiselect Isotope',
        },
    ];

    const inputTypeSelection = inputTypeOptions.find((option) => option.value === inputType);

    return (
        <div className={`wprmprs-layout-block-input wprmprs-layout-block-${props.block.type}`}>
            { props.block.label && (
                <label className="wprmprs-form-label">{ props.block.label }{ props.block.required && shared.requiredSpan }</label>
            ) }
            { props.block.help && (
                <div className="wprmprs-form-help">{props.block.help}</div>
            ) }
            {
                'text' === inputType
                &&
                <input type="text" className="wprmprs-form-input" placeholder={props.block.placeholder} disabled/>
            }
            {
                'single-select' === inputType
                &&
                <select className="wprmprs-form-input" style={ { minWidth: 100 } } disabled>
                    <option value="">{ props.block.placeholder }</option>
                </select>
            }
            {
                ( 'single-isotope' === inputType || 'multiple-isotope' === inputType )
                &&
                <div className="wprmprs-layout-block-isotope-container">
                    <div className="wprmprs-layout-block-isotope-option">Option A</div>
                    <div className="wprmprs-layout-block-isotope-option">Another Possibility</div>
                    <div className="wprmprs-layout-block-isotope-option">Third Option</div>
                </div>
            }
            { props.isEditing && (
            <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                <label className="wprmprs-form-label">Input Type</label>
                <Select
                    className="wprmprs-layout-block-select"
                    value={ inputTypeSelection }
                    onChange={(option) => {
                        props.onEdit('input_type', option.value);
                    }}
                    options={ inputTypeOptions }
                    clearable={false}
                />
                <input type="checkbox" id={`edit-block-${props.block.key}-required`} checked={props.block.required} onChange={(e) => props.onEdit('required', e.target.checked)} />
                <label htmlFor={`edit-block-${props.block.key}-required`}>required</label>
                <label htmlFor={`edit-block-${props.block.key}-label`}>Label</label>
                <input type="text" id={`edit-block-${props.block.key}-label`} value={props.block.label} onChange={(e) => props.onEdit('label', e.target.value)} />
                <label htmlFor={`edit-block-${props.block.key}-help`}>Help Text</label>
                <input type="text" id={`edit-block-${props.block.key}-help`} value={props.block.help} onChange={(e) => props.onEdit('help', e.target.value)} />
                <label htmlFor={`edit-block-${props.block.key}-placeholder`}>Placeholder</label>
                <input type="text" id={`edit-block-${props.block.key}-placeholder`} value={props.block.placeholder} onChange={(e) => props.onEdit('placeholder', e.target.value)} />
            </div>
            ) }
        </div>
    );
}

export default CustomTaxonomyBlock;