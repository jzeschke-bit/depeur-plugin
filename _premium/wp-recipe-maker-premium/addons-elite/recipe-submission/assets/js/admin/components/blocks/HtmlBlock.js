import React, { Component } from 'react';
import shared from './_shared';

const HtmlBlock = (props) => {
    return (
        <div className="wprmprs-layout-block-html">
            <div dangerouslySetInnerHTML={ { __html: props.block.text } } />
            { props.isEditing && (
                <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                    <label htmlFor={`edit-block-${props.block.key}-text`}>HTML</label>
                    <textarea id={`edit-block-${props.block.key}-text`} value={props.block.text} onChange={(e) => props.onEdit('text', e.target.value)} rows="3"></textarea>
                </div>
            ) }
        </div>
    );
}

export default HtmlBlock;