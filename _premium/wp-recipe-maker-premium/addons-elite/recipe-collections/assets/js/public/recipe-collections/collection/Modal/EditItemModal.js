import React from 'react';

import { __wprm } from 'Shared/Translations';

import EditCustom from '../ActionsClassic/EditItem/EditCustom';
import EditNote from '../ActionsClassic/EditItem/EditNote';
import ItemContent from '../Item/ItemContent';

const EditItemModal = (props) => {
    return (
        <div className="wprm-recipe-collections-layout-grid">
            <ItemContent
                type={ 'saved' }
                item={ props.editingItem }
                draggable={ false }
                index={ 0 }
            />
            {
                'ingredient' === props.editingItem.type
                &&
                <EditCustom
                    item={ props.editingItem }
                    onEdit={ props.onEdit }
                />
            }
            {
                'note' === props.editingItem.type
                &&
                <EditNote
                    item={ props.editingItem }
                    onEdit={ props.onEdit }
                />
            }
        </div>
    );
}
export default EditItemModal;