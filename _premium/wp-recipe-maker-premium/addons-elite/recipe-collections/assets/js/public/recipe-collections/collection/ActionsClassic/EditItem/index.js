import React, { Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import EditCustom from './EditCustom';
import EditNote from './EditNote';
import ItemContent from '../../Item/ItemContent';

const EditItem = (props) => {
    const { item } = props;

    return (
        <Fragment>
            <ItemContent
                layout={ props.layout }
                type={ 'saved' }
                item={ item }
                draggable={ false }
                index={ 0 }
            />
            {
                'ingredient' === item.type
                &&
                <EditCustom
                    item={ item }
                    onEdit={ props.onEdit }
                />
            }
            {
                'note' === item.type
                &&
                <EditNote
                    item={ item }
                    onEdit={ props.onEdit }
                />
            }
        </Fragment>
    );
}

export default EditItem;