import React, { useRef } from 'react';

import { __wprm } from 'Shared/Translations';

import ModalContainer from './ModalContainer';

import AddItemModal from './AddItemModal';
import EditItemModal from './EditItemModal';
import EditStructureModal from './EditStructureModal';
import HistoryTimelineModal from './HistoryTimelineModal';

const Modal = (props) => {
    const lastOpenModeRef = useRef( props.mode );

    if ( props.open ) {
        lastOpenModeRef.current = props.mode;
    }

    const mode = props.open ? props.mode : lastOpenModeRef.current;

    let Content = null;
    let extraProps = {};

    switch ( mode ) {
        case 'history':
            Content = HistoryTimelineModal;
            extraProps = {
                title: __wprm( 'Undo/Redo History' ),
                class: 'collections-history',
                button: __wprm( 'Done' ),
            };
            break;
        case 'structure':
            Content = EditStructureModal;
            extraProps = {
                title: __wprm( 'Change Collection Structure' ),
                button: __wprm( 'Done' ),
            };

            if ( ! props.useModalForStructure ) {
                extraProps.open = false;
            }
            break;
        case 'edit':
            Content = EditItemModal;
            let title = __wprm( 'Edit Item' );

            if ( 'note' === props.editingItem.type ) {
                title = __wprm( 'Edit Note' );
            } else if ( 'ingredient' === props.editingItem.type ) {
                title = __wprm( 'Edit Custom Recipe' );
            }

            extraProps = {
                title,
                class: 'collections-edit',
                button: __wprm( 'Done' ),
            };

            if ( ! props.useModalForAdd ) {
                extraProps.open = false;
            }
            break;
        default:
            Content = AddItemModal;
            extraProps = {
                title: __wprm( 'Add to Collection' ),
            };

            if ( ! props.useModalForAdd ) {
                extraProps.open = false;
            }
    }

    return (
        <ModalContainer
            { ...props }
            { ...extraProps }
        >
            <Content { ...props } />
        </ModalContainer>
    )
}

export default Modal;
