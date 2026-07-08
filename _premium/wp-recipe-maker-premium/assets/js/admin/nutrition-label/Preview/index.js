import React, { Fragment } from 'react';

import { arrayMove, SortableContainer, SortableElement } from 'react-sortable-hoc';

import BlockLine from './BlockLine';
import BlockNutrient from './BlockNutrient';
import BlockOtherNutrients from './BlockOtherNutrients';
import BlockServing from './BlockServing';
import BlockText from './BlockText';

import '../../../../css/public/nutrition-label.scss';

const blockTypes = {
    line: BlockLine,
    nutrient: BlockNutrient,
    'other_nutrients': BlockOtherNutrients,
    serving: BlockServing,
    text: BlockText,
};

const SortableItem = SortableElement( ( props ) => {
    const BlockElement = blockTypes[ props.block.type ];
        
    return (
        <div
            className={ `wprmp-nutrition-label-editor-sortable-element ${ props.className }` }
            onClick={ props.onClick }
        >
            <BlockElement
                layout={ props.layout }
                block={ props.block }
            />
        </div>
    );
} );

const SortableList = SortableContainer( ( props ) => {
    return (
        <div className="wprmp-nutrition-label-editor-sortable-container">
            { props.children }
        </div>
    );
} );

const Preview = (props) => {
    if ( ! props.layout ) {
        return null;
    }

    const properties = props.layout.properties;
    let style = {};

    // Border.
    if ( 0 < properties.border_width ) {
        style.borderWidth = properties.border_width;
        style.borderStyle = properties.border_style;
        style.borderColor = properties.border_color;
    }

    style.maxWidth = properties.max_width;
    style.padding = properties.padding;
    style.fontFamily = properties.font_family;
    style.fontSize = properties.font_size;
    style.lineHeight = `${ properties.line_height }px`;
    style.color = properties.text_color;
    style.backgroundColor = properties.background_color;

    let globalStyle = '';

    globalStyle += ' .wprm-nutrition-label-layout .wprmp-nutrition-label-block-line {';
    globalStyle += `background-color: ${ properties.border_color };`;
    globalStyle += '}';
    globalStyle += ' .wprm-nutrition-label-layout .wprmp-nutrition-label-block-nutrient {';
    globalStyle += `border-top-color: ${ properties.border_color };`;
    globalStyle += '}';

    // Get blocks to output.
    let validBlocks = [];

    for ( let block of props.layout.blocks ) {
        if ( block.hasOwnProperty( 'type' ) && blockTypes.hasOwnProperty( block.type ) ) {
            validBlocks.push( block );
        }
    }

    return (
        <Fragment>
            <div
                className="wprmp-nutrition-label-editor-preview wprm-nutrition-label-layout"
                style={ style }
            >
                <style type="text/css" dangerouslySetInnerHTML={ { __html: globalStyle } } />
                {
                    'new' === props.mode
                    ?
                    validBlocks.map( ( block, index ) => {
                        const BlockElement = blockTypes[ block.type ];
            
                        return (
                            <BlockElement
                                layout={ props.layout }
                                block={ block }
                                key={ index }
                            />
                        );
                    })
                    :
                    <SortableList
                        distance={ 1 }
                        helperClass="wprm-nutrition-label-layout"
                        helperContainer={() => {
                            return document.querySelector('.wprmp-nutrition-label-editor-sortable-helper');
                        }}
                        onSortEnd={({oldIndex, newIndex}) => {
                            props.onChangeBlocks( arrayMove( validBlocks, oldIndex, newIndex ) );
                        }}
                    >
                        {
                            validBlocks.map( ( block, index ) => {
                                let classes = [];

                                if ( props.options.separate ) {
                                    classes.push( 'wprmp-nutrition-label-editor-preview-seperated' );
                                }
                                if ( block.id === props.options.selected ) {
                                    classes.push( 'wprmp-nutrition-label-editor-preview-selected' );
                                }

                                return (
                                    <SortableItem
                                        className={ classes.join( ' ' ) }
                                        onClick={ () => {
                                            const selected = block.id === props.options.selected ? false : block.id;
                                            
                                            props.onChangeSelected( selected );
                                        }}
                                        key={ `block-${block.id}` }
                                        index={ index }
                                        layout={ props.layout }
                                        block={ block }
                                    />
                                )
                             } )
                        }
                    </SortableList>
                }
            </div>
            <div
                className="wprmp-nutrition-label-editor-sortable-helper"
                style={ style }
            >
            </div>
        </Fragment>
    );
}

export default Preview;