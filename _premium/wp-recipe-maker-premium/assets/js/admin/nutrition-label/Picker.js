import React, { Fragment } from 'react';

import Preview from './Preview';

const Picker = (props) => {
    const layouts = window.wprmp_nutrition_label_layout.defaults ? JSON.parse( JSON.stringify( window.wprmp_nutrition_label_layout.defaults ) ) : [];

    if ( 0 === layouts.length ) {
        return (
            <p>Something went wrong in loading the layouts. Please contact support@bootstrapped.ventures for help.</p>
        )
    }

    return (
        <Fragment>
            <p>Choose the nutrition label to use as a starting point:</p>
            <div className="wprmp-nutrition-label-editor-new-layouts">
                {
                    Object.keys( layouts ).map( ( id, index ) => {
                        const layout = layouts[id];

                        return (
                            <div
                                className="wprmp-nutrition-label-editor-new-layout"
                                onClick={() => {
                                    props.onPickLayout( layout );
                                }}
                                key={ index }
                            >
                                <div className="wprmp-nutrition-label-editor-new-layout-name">{ layout.name }</div>
                                <Preview
                                    mode="new"
                                    layout={ layout }
                                    options={ {} }
                                    onChangeSelected={ () => {} }
                                    onChangeBlocks={ () => {} }
                                />
                            </div>
                        )
                    })
                }
            </div>
        </Fragment>
    );
}

export default Picker;