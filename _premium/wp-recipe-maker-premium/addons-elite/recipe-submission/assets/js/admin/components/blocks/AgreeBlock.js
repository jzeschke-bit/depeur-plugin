import React, { Component } from 'react';
import shared from './_shared';

const AgreeBlock = (props) => {
    return (
        <div className="wprmprs-layout-block-agree">
            <label>
                <input type="checkbox" className="wprmprs-form-agree" disabled/> <span dangerouslySetInnerHTML={ { __html: props.block.text } } />
            </label>
            { props.isEditing && (
                <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                    <label htmlFor={`edit-block-${props.block.key}-text`}>Text</label>
                    <input type="text" id={`edit-block-${props.block.key}-text`} value={props.block.text} onChange={(e) => props.onEdit('text', e.target.value)} />
                </div>
            ) }
        </div>
    );
}

export default AgreeBlock;