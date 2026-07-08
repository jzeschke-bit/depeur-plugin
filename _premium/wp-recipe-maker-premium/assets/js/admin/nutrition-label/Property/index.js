import React from 'react';

import PropertyColor from './PropertyColor';
import PropertyDropdown from './PropertyDropdown';
import PropertyNumber from './PropertyNumber';
import PropertyText from './PropertyText';

const Property = (props) => {
    let label = props.property.charAt(0).toUpperCase() + props.property.slice(1);
    label = label.replace( '_', ' ' );

    return (
        <div className="wprmp-nutrition-label-editor-property">
            <div className="wprmp-nutrition-label-editor-property-label">
                { label }
            </div>
            <div className="wprmp-nutrition-label-editor-property-value">
                {
                    ( 'text' === props.property || 'font_family' === props.property )
                    &&
                    <PropertyText
                        value={ props.value }
                        onChange={ props.onChange }
                    />
                }
                {
                    ( 'serving_value' === props.property || 'display_values' === props.property || 'style' === props.property || 'nutrient' === props.property || 'border_style' === props.property )
                    &&
                    <PropertyDropdown
                        type={ props.type }
                        property={ props.property }
                        value={ props.value }
                        onChange={ props.onChange }
                    />
                }
                {
                    ( 'height' === props.property || 'border_width' === props.property || 'max_width' === props.property || 'padding' === props.property
                    || 'font_size' === props.property || 'line_height' === props.property )
                    &&
                    <PropertyNumber
                        value={ props.value }
                        onChange={ props.onChange }
                    />
                }
                {
                    'calories' === props.property
                    &&
                    <PropertyDropdown
                        type="calories"
                        property={ props.property }
                        value={ props.value }
                        onChange={ props.onChange }
                    />
                }
                {
                    ( 'border_color' === props.property || 'text_color' === props.property || 'background_color' === props.property )
                    &&
                    <PropertyColor
                        value={ props.value }
                        onChange={ props.onChange }
                    />
                }
            </div>
        </div>
    );
}

export default Property;