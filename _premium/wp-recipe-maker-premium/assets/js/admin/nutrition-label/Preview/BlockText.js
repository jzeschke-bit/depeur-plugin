import React from 'react';

const BlockText = (props) => {
    if ( ! props.block ) {
        return null;
    }

    let text = props.block.text;

    // Placeholders.
    text = text.replace( '%recipe_name%', 'My Demo Recipe' );

    if ( '100g' === props.layout.properties.display_values && wprm_admin.text.nutrition_label_servings === text ) {
        text = wprm_admin.text.nutrition_label_100g;
    }

    return (
        <div className={ `wprmp-nutrition-label-block-text wprmp-nutrition-label-block-text-${ props.block.style }` }>
            { text }
        </div>
    );
}

export default BlockText; 