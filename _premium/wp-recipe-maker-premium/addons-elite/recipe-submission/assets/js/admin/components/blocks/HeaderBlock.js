import React, { Component } from 'react';
import shared from './_shared';

const HeaderBlock = (props) => {
    const Tag = props.block.tag;

    return (
        <div className="wprmprs-layout-block-header">
            <Tag>{props.block.text}</Tag>
            { props.isEditing && (
                <div className={shared.editClassName} onClick={(e) => shared.onEditClick(e)}>
                    <label htmlFor={`edit-block-${props.block.key}-text`}>Text</label>
                    <input type="text" id={`edit-block-${props.block.key}-text`} value={props.block.text} onChange={(e) => props.onEdit('text', e.target.value)} />
                    <label htmlFor={`edit-block-${props.block.key}-tag`}>Tag</label>
                    <select id={`edit-block-${props.block.key}-tag`} value={props.block.tag} onChange={(e) => props.onEdit('tag', e.target.value)}>
                        <option value="h1">H1</option>
                        <option value="h2">H2</option>
                        <option value="h3">H3</option>
                        <option value="h4">H4</option>
                        <option value="h5">H5</option>
                        <option value="h6">H6</option>
                    </select>
                </div>
            ) }
        </div>
    );
}

export default HeaderBlock;