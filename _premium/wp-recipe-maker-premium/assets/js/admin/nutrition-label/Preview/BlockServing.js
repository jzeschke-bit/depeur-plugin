import React from 'react';

const BlockServing = (props) => {
    if ( ! props.block ) {
        return null;
    }

    let value = 126;
    if ( '100g' === props.layout.properties.display_values && '100' === props.block.serving_value ) {
        value = 100;
    }

    return (
        <div className={ `wprmp-nutrition-label-block-serving wprmp-nutrition-label-block-serving-${ props.block.style }`}>
            <div className="wprmp-nutrition-label-block-serving-text">{ props.block.text }</div>
            <div className="wprmp-nutrition-label-block-serving-spacer">&nbsp;</div>
            <div className="wprmp-nutrition-label-block-serving-value">{ value } { wprm_admin.settings.nutrition_default_serving_unit }</div>
        </div>
    );
}

export default BlockServing;