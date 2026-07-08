import React, { Component } from 'react';
import shared from './_shared';

const RecipeImageBlock = (props) => {
    return (
        <div className="wprmprs-layout-block-recipe_image">
            { props.block.label && (
                <label className="wprmprs-form-label">{ props.block.label }{ props.block.required && shared.requiredSpan }</label>
            ) }
            { props.block.help && (
                <div className="wprmprs-form-help">{props.block.help}</div>
            ) }
            <div className="ezdz-dropzone">
                <div>{props.block.placeholder}</div>
            </div>
            {shared.editRecipeField(props)}
        </div>
    );
}

export default RecipeImageBlock;