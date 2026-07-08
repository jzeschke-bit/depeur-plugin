import React, { Component } from 'react';
import AvailableBlocks from './components/AvailableBlocks';
import Blocks from './components/Blocks';

import { arrayMove } from 'react-sortable-hoc';

import '../../css/admin/layout.scss';

export default class Layout extends Component {
    constructor(props) {
        super(props);

        this.state = {
            blocks: window.wprmprs_layout.blocks,
            originalBlocks: JSON.parse(JSON.stringify(window.wprmprs_layout.blocks)),
            blockIndex: window.wprmprs_layout.blocks.length,
            editingBlock: false,
            saving: false,
        }
    }

    onAddBlock(type) {
        let index = this.state.blockIndex + 1;
        let block = {
            ...window.wprmprs_layout.defaults[type],
            key: index,
            type: type,
        }

        this.setState({
            blocks: [
                ...this.state.blocks,
                block
            ],
            blockIndex: index,
        });
    };

    onEditBlock(key, property, value) {
        let blocks = [];
        for(let block of this.state.blocks) {
            if(block.key === key) {
                block[property] = value;
            }

            blocks.push(block);
        }

        this.setState({
            blocks: blocks,
        });
    };

    onEditSelectedBlock(property, value) {
        if(false !== this.state.editingBlock) {
            this.onEditBlock(this.state.editingBlock, property, value);
        }
    }

    onDeleteSelectedBlock() {
        if(false !== this.state.editingBlock) {
            let blocks = [];
            for(let block of this.state.blocks) {
                if(block.key !== this.state.editingBlock) {
                    blocks.push(block);
                }
            }

            this.setState({
                blocks: blocks,
                editingBlock: false,
            });
        }
    }

    onSelectBlock(key) {
        const editingBlock = key === this.state.editingBlock ? false : key;

        this.setState({
            editingBlock: editingBlock,
        });
    };

    onBlocksDrag({oldIndex, newIndex}) {
        this.setState({
            blocks: arrayMove(this.state.blocks, oldIndex, newIndex),
        });
    };

    onSaveLayout() {
        const data = {
            action: 'wprmprs_save_layout',
            security: wprm_admin.nonce,
            blocks: JSON.stringify(this.state.blocks),
        }

        this.setState({
            saving: true,
        });

        jQuery.post(wprm_admin.ajax_url, data, function(out) {
            this.setState({
                originalBlocks: JSON.parse(JSON.stringify(this.state.blocks)),
                saving: false,
            });
        }.bind(this), 'json');
    };

    onResetLayout() {
        if(confirm('Are you sure you want to reset the layout to the default?')) {
            this.setState({
                blocks: window.wprmprs_layout.blocks_default,
            });
        }
    }

    render() {
        const blocksChanged = (
            JSON.stringify(this.state.blocks) !== JSON.stringify(this.state.originalBlocks)
        );

        return (
            <div>
                <h1>Recipe Submission Layout</h1>
                <AvailableBlocks
                    usedBlocks={this.state.blocks}
                    onAddBlock={this.onAddBlock.bind(this)}
                />
                <hr/>
                <Blocks
                    blocks={this.state.blocks}
                    onSortEnd={this.onBlocksDrag.bind(this)}
                    editingBlock={this.state.editingBlock}
                    onEditSelectedBlock={this.onEditSelectedBlock.bind(this)}
                    onDeleteSelectedBlock={this.onDeleteSelectedBlock.bind(this)}
                    onSelectBlock={this.onSelectBlock.bind(this)}
                />
                <hr/>
                <button className="button button-primary wprmprs-layout-save" onClick={this.onSaveLayout.bind(this)} disabled={this.state.saving || !blocksChanged}>{this.state.saving ? '...' : 'Save Form Layout'}</button>
                <button className="button wprmprs-layout-reset" onClick={this.onResetLayout.bind(this)}>Reset to Default</button>
            </div>
        );
    }
}