import React from 'react';

const PropertyNumber = (props) => {
    return (
        <input
            type="number"
            min="0"
            value={ props.value }
            onChange={ (e) => {
                props.onChange( parseInt( e.target.value ) );
            } }
        />
    );
}

export default PropertyNumber;