import React, { Component, Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import EditNote from '../ActionsClassic/EditItem/EditNote';
import Item from '../Item';

export default class ModalAddNote extends Component {
    constructor(props) {
        super(props);

        this.clearItems = this.clearItems.bind(this);
    }

    componentDidMount() {
        this.clearItems();
    }

    clearItems() {
        this.props.onChangeAddItems([
            {
                type: 'note',
                name: '',
                color: 'none',
            }
        ]);
    }

    render() {
        const item = {
            ...this.props.addItems[0],
            id: 'select-0',
        }

        if ( 'note' !== item.type ) {
            return null;
        }

        return (
            <div className="wprmprc-collection-modal-add-container wprmprc-collection-modal-add-note-container">
                <div className="wprmprc-collection-modal-add-preview">
                    <Item
                        layout={this.props.layout}
                        type={this.props.type}
                        collection={this.props.collection}
                        item={ item }
                        interface={ 'none' }
                        draggable={ false }
                        onAddItem={ this.props.onAddItem }
                        key="select-custom"
                        index={ 0 }
                    />
                </div>
                <EditNote
                    item={item}
                    onEdit={(item) => this.props.onChangeAddItems( [item] ) }
                />
                <div className="wprmprc-collection-modal-actions">
                    <button
                        className="wprm-popup-modal__btn"
                        onClick={() => {
                            this.props.onAddItem( item );
                            this.clearItems();
                        }}
                    >{ __wprm( 'Add Note' ) }</button>
                </div>
            </div>
        );
    }
}
