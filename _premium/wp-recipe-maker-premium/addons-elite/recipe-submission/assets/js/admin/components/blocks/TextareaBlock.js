import React, { Component } from 'react';
import shared from './_shared';

const TextareaBlock = (props) => {
    return (
        <div className={`wprmprs-layout-block-textarea wprmprs-layout-block-${props.block.type}`}>
            { props.block.label && (
                <label className="wprmprs-form-label">{ props.block.label }{ props.block.required && shared.requiredSpan }</label>
            ) }
            { props.block.help && (
                <div className="wprmprs-form-help">{props.block.help}</div>
            ) } 
            <textarea className="wprmprs-form-input" placeholder={props.block.placeholder} disabled></textarea>
            {shared.editRecipeField(props)}
        </div>
    );
}

export default TextareaBlock;