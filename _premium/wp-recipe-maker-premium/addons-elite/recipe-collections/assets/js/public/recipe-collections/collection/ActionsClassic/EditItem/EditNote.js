import React from 'react';
import Select from 'react-select';

import { __wprm } from 'Shared/Translations';

const EditNote = (props) => {
    const { item } = props;

    const colorOptions = [
        { value: 'none', label: __wprm( 'None' ) },
        { value: 'blue', label: __wprm( 'Blue' ) },
        { value: 'red', label: __wprm( 'Red' ) },
        { value: 'green', label: __wprm( 'Green' ) },
        { value: 'yellow', label: __wprm( 'Yellow' ) },
    ];

    return (
        <div className="wprmprc-collection-action-edit-note-form">
            <label htmlFor="wprmprc-collection-action-edit-note-name">{ __wprm( 'Note' ) }</label>
            <textarea
                id="wprmprc-collection-action-edit-note-name"
                value={item.name}
                onChange={(event) => 
                    props.onEdit({
                        ...item,
                        name: event.target.value,
                    })
                }
            />
            <label>{ __wprm( 'Color' ) }</label>
            <Select
                className="wprmprc-collection-action-edit-item-color"
                value={colorOptions.filter(({value}) => value === item.color)}
                onChange={(option) =>
                    props.onEdit({
                        ...item,
                        color: option.value,
                    })
                }
                options={colorOptions}
                clearable={false}
                styles={{
                    control: styles => ({ ...styles, borderRadius: 5 }),
                }}
                menuPlacement="top"
            />
        </div>
    );
}
export default EditNote;