import React, { Component, Fragment } from 'react';

import Buttons from './Buttons';
import Edit from './Edit';
import Picker from './Picker';
import Preview from './Preview';

import '../../../css/admin/nutrition-label/app.scss';

export default class App extends Component {
    constructor(props) {
        super(props);

        const layout = window.wprmp_nutrition_label_layout.layout ? JSON.parse( JSON.stringify( window.wprmp_nutrition_label_layout.layout ) ) : false;

        this.state = {
            mode: false === layout ? 'new' : 'general',
            options: {},
            layout,
            originalLayout: JSON.parse( JSON.stringify( layout ) ),
            editingBlock: false,
            saving: false,
        }
    }

    onSaveLayout() {
        const data = {
            action: 'wprmp_save_nutrition_layout',
            security: wprm_admin.nonce,
            layout: JSON.stringify( this.state.layout ),
        }

        this.setState({
            saving: true,
        });

        jQuery.post( wprm_admin.ajax_url, data, function(out) {
            this.setState({
                originalLayout: JSON.parse(JSON.stringify( this.state.layout ) ),
                saving: false,
            });
        }.bind(this), 'json' );
    }

    render() {
        const changesMade = JSON.stringify( this.state.layout ) !== JSON.stringify( this.state.originalLayout );

        return (
            <Fragment>
                <h1>Nutrition Label Layout</h1>
                <div className={ `wprmp-nutrition-label-editor wprmp-nutrition-label-editor-${ this.state.mode }` }>
                    {
                        'new' === this.state.mode
                        ?
                        <Picker
                            onPickLayout={( layout ) => {
                                let newState = {
                                    layout: JSON.parse( JSON.stringify( layout ) ),
                                    mode: 'general',
                                };

                                if ( false === this.state.originalLayout ) {
                                    newState.originalLayout = JSON.parse( JSON.stringify( layout ) );
                                }

                                this.setState( newState );
                            }}
                        />
                        :
                        <Fragment>
                            <div className="wprmp-nutrition-label-editor-side">
                                <Buttons
                                    saving={ this.state.saving }
                                    changesMade={ changesMade }
                                    onSave={ this.onSaveLayout.bind(this) }
                                    onCancel={() => {
                                        this.setState({
                                            layout: JSON.parse( JSON.stringify( this.state.originalLayout ) ),
                                        });
                                    }}
                                    onReset={() => {
                                        this.setState({
                                            layout: false,
                                            mode: 'new',
                                        });
                                    }}
                                />
                                <Edit
                                    mode={ this.state.mode }
                                    onChangeMode={ ( mode ) => {
                                        this.setState({
                                            mode,
                                        });
                                    }}
                                    layout={ this.state.layout }
                                    onChangeProperties={ ( properties ) => {
                                        let newLayout = JSON.parse( JSON.stringify( this.state.layout ) );
                                        newLayout.properties = {
                                            ...newLayout.properties,
                                            ...properties
                                        }

                                        this.setState({
                                            layout: newLayout,
                                        });
                                    }}
                                    options={ this.state.options }
                                    onChangeOptions={ ( options ) => {
                                        this.setState({
                                            options: {
                                                ...this.state.options,
                                                ...options,
                                            },
                                        });
                                    }}
                                    onAddBlock={ ( type ) => {
                                        const block = wprmp_nutrition_label_layout.blocks[ type ];

                                        let maxId = Math.max.apply( Math, this.state.layout.blocks.map( function( block ) { return block.id; } ) );
                                        maxId = maxId < 0 ? -1 : maxId;

                                        let newBlock = {
                                            ...block.properties,
                                            type,
                                            id: maxId + 1,
                                        }

                                        let newLayout = JSON.parse( JSON.stringify( this.state.layout ) );
                                        newLayout.blocks.push( newBlock );

                                        this.setState({
                                            layout: newLayout,
                                        });
                                    }}
                                    onRemoveBlock={ () => {
                                        const removeIndex = this.state.layout.blocks.findIndex( ( block ) => block.id === this.state.options.selected );

                                        if ( 0 <= removeIndex ) {
                                            let newLayout = JSON.parse( JSON.stringify( this.state.layout ) );
                                            newLayout.blocks.splice( removeIndex, 1 );

                                            this.setState({
                                                layout: newLayout,
                                            });
                                        }
                                    }}
                                    onChangeBlock={ ( properties ) => {
                                        const editingIndex = this.state.layout.blocks.findIndex( ( block ) => block.id === this.state.options.selected );

                                        if ( 0 <= editingIndex ) {
                                            let newLayout = JSON.parse( JSON.stringify( this.state.layout ) );
                                            newLayout.blocks[ editingIndex ] = {
                                                ...newLayout.blocks[ editingIndex ],
                                                ...properties
                                            }

                                            this.setState({
                                                layout: newLayout,
                                            });
                                        }
                                    }}
                                />
                            </div>
                            <div className="wprmp-nutrition-label-editor-main">
                                <Preview
                                    layout={ this.state.layout }
                                    mode={ this.state.mode }
                                    options={ this.state.options }
                                    onChangeSelected={ ( selected ) => {
                                        this.setState({
                                            options: {
                                                ...this.state.options,
                                                selected: selected,
                                            },
                                            mode: 'elements',
                                        });
                                    }}
                                    onChangeBlocks={ ( blocks ) => {
                                        let newLayout = JSON.parse( JSON.stringify( this.state.layout ) );
                                        newLayout.blocks = blocks;

                                        this.setState({
                                            layout: newLayout,
                                        });
                                    }}
                                />
                            </div>
                        </Fragment>
                    }
                </div>
            </Fragment>
        );
    }
}