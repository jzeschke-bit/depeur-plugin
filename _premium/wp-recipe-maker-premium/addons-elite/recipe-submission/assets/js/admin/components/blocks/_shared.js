import React, { Component } from 'react';

const shared = {
    requiredSpan: (<span className="wprmprs-layout-block-required">*</span>),
    editClassName: 'wprmprs-layout-block-edit',
    onEditClick: (e) => e.stopPropagation(),
    editRecipeField: (props) => (
        <div>
            { props.isEditing && (
            <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
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
    ),
};

export default shared;