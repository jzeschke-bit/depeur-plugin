import React, { Fragment } from 'react';

import Property from './Property';

const Edit = (props) => {
    let editingElement = false;
    if ( ! isNaN( props.options.selected ) ) {
        editingElement = props.layout.blocks.find( ( block ) => block.id === props.options.selected );
    }

    return (
        <Fragment>
            <div className="wprmp-nutrition-label-editor-side-section">
                <strong>Edit Mode</strong>
                <div>
                    <label>
                        <input
                            type="radio"
                            checked={ 'general' === props.mode }
                            onChange={() => props.onChangeMode( 'general' ) }
                        /> Edit General Properties
                    </label>
                </div>
                <div>
                    <label>
                        <input
                            type="radio"
                            checked={ 'elements' === props.mode }
                            onChange={() => props.onChangeMode( 'elements' ) }
                        /> Edit Individual Elements
                    </label>
                </div>
                <br/>
                <div>
                    <label>
                        <input type="checkbox" 
                            checked={ props.options.separate } 
                            onChange={ () => {
                                props.onChangeOptions({
                                    separate: ! props.options.separate,
                                });
                        }} /> Separate different elements
                    </label>
                </div>
            </div>
            {
                'general' === props.mode
                &&
                <div className="wprmp-nutrition-label-editor-side-section">
                    <strong>General Properties</strong>
                    {
                        Object.keys( props.layout.properties ).map( ( property, index ) => {
                            return (
                                <Property
                                    type="layout"
                                    property={ property }
                                    value={ props.layout.properties[ property ] }
                                    onChange={ ( value ) => {
                                        props.onChangeProperties( {
                                            [ property ]: value,
                                        } );
                                    } }
                                    key={ index }
                                />
                            )
                        })
                    }
                </div>
            }
            {
                'elements' === props.mode
                &&
                <Fragment>
                    <div className="wprmp-nutrition-label-editor-side-section">
                        <strong>Add Element</strong>
                        {
                            Object.values( wprmp_nutrition_label_layout.blocks ).map( ( block, index ) => (
                                <a
                                    href="#"
                                    role="button"
                                    onClick={() => {
                                        props.onAddBlock( block.type );
                                    }}
                                    key={ index }
                                >{ block.label }</a>    
                            ) )
                        }
                    </div>
                    <div className="wprmp-nutrition-label-editor-side-section">
                        <strong>Edit Element</strong>
                        {
                            ! editingElement
                            ?
                            <div>Click on an element to start editing</div>
                            :
                            <Fragment>
                                {
                                    Object.keys( editingElement ).map( ( property, index ) => {
                                        if ( 'type' === property || 'name' === property || 'id' === property ) {
                                            return null;
                                        }

                                        // Only show calorie property if calories is selected as the nutrient.
                                        if ( 'calories' === property && 'calories' !== editingElement.nutrient ) {
                                            return null;
                                        }

                                        return (
                                            <Property
                                                type={ editingElement.type }
                                                property={ property }
                                                value={ editingElement[ property ] }
                                                onChange={ ( value ) => {
                                                    let newProperties = JSON.parse( JSON.stringify( editingElement ) );
                                                    newProperties[ property ] = value;

                                                    props.onChangeBlock( newProperties );
                                                } }
                                                key={ index }
                                            />
                                        )
                                    })
                                }
                                <div className="wprmp-nutrition-label-editor-remove">
                                    <a
                                        href="#"
                                        role="button"
                                        onClick={ () => {
                                            if ( confirm( 'Are you sure you want to remove this element?' ) ) {
                                                props.onRemoveBlock();
                                            }
                                        }}
                                    >
                                        Remove this Element
                                    </a>
                                </div>
                            </Fragment>
                        }
                    </div>
                </Fragment>
            }
        </Fragment>
    );
}

export default Edit;