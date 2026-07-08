import React, { Component } from 'react';
import PropTypes from 'prop-types';

import '../../../css/public/blocks.scss';

import blockComponents from './BlockComponents';
import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';

const DragHandle = SortableHandle( ({name}) => <span className="wprmprs-layout-block-handle"><span className="dashicons dashicons-sort"></span><br/>{name}</span> );

const SortableItem = SortableElement( ( {block, isEditing, onSelectBlock, onEditSelectedBlock, onDeleteSelectedBlock} ) => {
    const BlockComponent = blockComponents[block.type];

    return (
        <li className="wprmprs-layout-block">
            <DragHandle
                name={block.name}
            />
            <div onClick={() => {onSelectBlock(block.key);}} className={isEditing ? 'wprmprs-layout-block-container wprmprs-layout-block-container-selected' : 'wprmprs-layout-block-container' }>
                <BlockComponent
                    block={block}
                    isEditing={isEditing}
                    onEdit={onEditSelectedBlock}
                />
                { isEditing && (
                    <a href="#" className="wprmprs-layout-block-delete" onClick={(e) => { e.preventDefault(); e.stopPropagation(); onDeleteSelectedBlock(); }}>Delete Block</a>
                ) }
            </div>
        </li>
    );
});

const SortableList = SortableContainer( ( {items, editingBlock, onSelectBlock, onEditSelectedBlock, onDeleteSelectedBlock} ) => {
    return (
        <ul id="wprmprs-layout-blocks">
            {items.map( (block, index ) => (
                <SortableItem
                    key={block.key}
                    index={index}
                    block={block}
                    isEditing={editingBlock === block.key}
                    onSelectBlock={onSelectBlock}
                    onEditSelectedBlock={onEditSelectedBlock}
                    onDeleteSelectedBlock={onDeleteSelectedBlock}
                />
            ))}
        </ul>
    );
});

const Blocks = (props) => {
    return (
        <SortableList
            items={props.blocks}
            onSortStart={(_, event) => event.preventDefault()}
            onSortEnd={props.onSortEnd}
            useDragHandle={true}
            editingBlock={props.editingBlock}
            onSelectBlock={props.onSelectBlock}
            onEditSelectedBlock={props.onEditSelectedBlock}
            onDeleteSelectedBlock={props.onDeleteSelectedBlock}
        />
    );
}

Blocks.propTypes = {
    blocks: PropTypes.array.isRequired,
    onSortEnd: PropTypes.func.isRequired,
    editingBlock: PropTypes.oneOfType([PropTypes.bool, PropTypes.number]).isRequired,
    onSelectBlock: PropTypes.func.isRequired,
    onEditSelectedBlock: PropTypes.func.isRequired,
    onDeleteSelectedBlock: PropTypes.func.isRequired,
}

export default Blocks;