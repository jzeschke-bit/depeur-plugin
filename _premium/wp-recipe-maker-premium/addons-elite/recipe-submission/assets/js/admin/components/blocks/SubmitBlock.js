import React, { Component } from 'react';
import shared from './_shared';

const SubmitBlock = (props) => {
    return (
        <div className="wprmprs-layout-block-submit">
            <input type="submit" className="button button-secondary button-compact" value={props.block.text} disabled />
            { props.isEditing && (
                <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                    <label htmlFor={`edit-block-${props.block.key}-text`}>Text</label>
                    <input type="text" id={`edit-block-${props.block.key}-text`} value={props.block.text} onChange={(e) => props.onEdit('text', e.target.value)} />
                </div>
            ) }
        </div>
    );
}

export default SubmitBlock;