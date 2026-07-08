import React from 'react';

import BlockNutrient from './BlockNutrient';

const BlockOtherNutrients = (props) => {
    if ( ! props.block ) {
        return null;
    }

    let nutrientsDisplayed = ['serving_size'];

    for ( let block of props.layout.blocks ) {
        if ( 'nutrient' === block.type ) {
            nutrientsDisplayed.push( block.nutrient );
        }
    }

    return (
        <div className="wprmp-nutrition-label-block-other-nutrients">
            {
                Object.keys( wprm_admin_modal.nutrition ).map( ( nutrient, index ) => {
                    
                    if ( nutrientsDisplayed.includes( nutrient ) ) {
                        return null;
                    }

                    return (
                        <BlockNutrient
                            layout={ props.layout }
                            block={ {
                                ...props.block,
                                type: 'nutrient',
                                nutrient,
                            } }
                            key={ index }
                        />
                    );
                })
            }
        </div>
    );
}

export default BlockOtherNutrients;