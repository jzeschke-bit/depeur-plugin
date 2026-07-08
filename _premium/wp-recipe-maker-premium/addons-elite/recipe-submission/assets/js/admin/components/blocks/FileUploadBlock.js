import React, { Component } from 'react';
import shared from './_shared';

const FileUploadBlock = (props) => {
    return (
        <div className={`wprmprs-layout-block-upload wprmprs-layout-block-${props.block.type}`}>
            { props.block.label && (
                <label className="wprmprs-form-label">{ props.block.label }{ props.block.required && shared.requiredSpan }</label>
            ) }
            { props.block.help && (
                <div className="wprmprs-form-help">{props.block.help}</div>
            ) } 
            <input type="file" className="wprmprs-form-input" disabled/>
            {shared.editRecipeField(props)}
        </div>
    );
}

export default FileUploadBlock;