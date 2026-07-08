import React from 'react';

const PropertyDropdown = (props) => {
    let options = false;

    // Show actual options.
    if ( 'serving_value' === props.property ) {
        options = {
            'actual': 'Actual serving value',
            '100': '100 if set to display per 100g',
        };
    }

    // Display values options.
    if ( 'display_values' === props.property ) {
        options = {
            'serving': 'Per serving',
            '100g': 'Per 100g',
            // 'both': 'Both per serving and per 100g',
        };
    }

    // Text style options.
    if ( 'border_style' === props.property ) {
        options = {
            'solid': 'Solid',
            'dashed': 'Dashed',
            'dotted': 'Dotted',
            'double': 'Double',
            'groove': 'Groove',
            'ridge': 'Ridge',
            'inset': 'Inset',
            'outset': 'Outset',
        };
    }

    // Text style options.
    if ( 'text' === props.type && 'style' === props.property ) {
        options = {
            'regular': 'Regular',
            'bold': 'Bold',
            'title': 'Title',
            'subtitle': 'Subtitle',
            'daily': 'Daily Value',
            'disclaimer': 'Disclaimer',
        };
    }

    // Serving style options.
    if ( 'serving' === props.type && 'style' === props.property ) {
        options = {
            'regular': 'Regular',
            'big': 'Big',
        };
    }

    // Calories display options.
    if ( 'calories' === props.type && 'calories' === props.property ) {
        options = {
            'normal': 'Normal',
            'daily': 'Daily Value',
            'fat': 'Calories from Fat',
        };
    }

    // Nutrient style options.
    if ( ( 'nutrient' === props.type || 'other_nutrients' === props.type ) && 'style' === props.property ) {
        options = {
            'main': 'Main',
            'child': 'Child',
            'child-line': 'Child with full line',
            'subchild': 'Subchild',
            'subchild-line': 'Subchild with full line',
            'calories': 'Calories',
            'other': 'Other',
        };
    }

    // Nutrient options.
    if ( 'nutrient' === props.type && 'nutrient' === props.property ) {
        options = {};
        for ( let nutrient of Object.keys( wprm_admin_modal.nutrition ) ) {
            const nutrientOptions = wprm_admin_modal.nutrition[ nutrient ];

            if ( 'serving_size' !== nutrient ) {
                options[ nutrient ] = nutrientOptions.label;
            }
        }
    }

    if ( ! options ) {
        return <p>Something went wrong.</p>;
    }

    return (
        <select
            value={ props.value }
            onChange={ (e) => {
                props.onChange( e.target.value );
            } }
        >
            {
                Object.keys( options ).map( ( option, index ) => {
                    const label = options[ option ];

                    return (
                        <option
                            value={ option }
                            key={ index }
                        >{ label }</option>
                    )
                })
            }
          </select>
    );
}

export default PropertyDropdown;