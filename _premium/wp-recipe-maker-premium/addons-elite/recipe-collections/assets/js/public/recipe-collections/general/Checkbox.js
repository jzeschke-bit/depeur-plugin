import React from 'react';

import '../../../../css/public/checkbox.scss';

const Checkbox = (props) => {
    return (
        <input
            className="wprmprc-checkbox"
            type="checkbox"
            checked={ props.checked }
            onChange={ (e) => {
                props.onChange( e.target.checked );
            } }
            id={ props.id }
            value="1"
        />
    );
}
export default Checkbox;