import React, { Component } from 'react';
import shared from './_shared';

const ParagraphBlock = (props) => {
    return (
        <div className="wprmprs-layout-block-paragraph">
            <p>{props.block.text}</p>
            { props.isEditing && (
                <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                    <label htmlFor={`edit-block-${props.block.key}-text`}>Text</label>
                    <textarea id={`edit-block-${props.block.key}-text`} value={props.block.text} onChange={(e) => props.onEdit('text', e.target.value)} rows="3"></textarea>
                </div>
            ) }
        </div>
    );
}

export default ParagraphBlock;