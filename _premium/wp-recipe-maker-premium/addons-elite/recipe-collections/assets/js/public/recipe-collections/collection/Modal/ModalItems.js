import React, { Component, Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import Button from '../../../../shared/Button';
import Item from '../Item';

export default class ModalItems extends Component {
    constructor(props) {
        super(props);

        this.state = {
            itemsPerPage: 10,
            page: 1,
        }
    }

    componentDidUpdate( prevProps ) {
        if ( JSON.stringify( this.props.addItems ) !== JSON.stringify( prevProps.addItems ) ) {
            this.setState({
                page: 1,
            });
        }
    }

    render() {
        const itemsToShow = this.state.page * this.state.itemsPerPage;

        return (
            <div className="wprmprc-collection-modal-items-container">
                <div className="wprmprc-collection-modal-items">
                    {
                        this.props.addItems.map( (item, index) => {
                            // Limit items shown at first.
                            if ( itemsToShow <= index ) {
                                return null;
                            }

                            return (
                                <Button
                                    className="wprmprc-collection-modal-item"
                                    onClick={() => {
                                        this.props.onAddItem( item );
                                    }}
                                    key={ index }
                                >
                                    <Item
                                        layout={this.props.layout}
                                        type={this.props.type}
                                        collection={this.props.collection}
                                        item={{
                                            ...item,
                                            id: `select-${item.id}`,
                                        }}
                                        interface={ 'none' }
                                        draggable={ false }
                                        index={index}
                                    />
                                </Button>
                            )
                        })
                    }
                    {
                        0 < this.props.addItems.length
                        &&
                        <Fragment>
                            <div className="wprmprc-collection-modal-item-placeholder"></div>
                            <div className="wprmprc-collection-modal-item-placeholder"></div>
                            <div className="wprmprc-collection-modal-item-placeholder"></div>
                            <div className="wprmprc-collection-modal-item-placeholder"></div>
                        </Fragment>
                    }
                </div>
                {
                    itemsToShow < this.props.addItems.length
                    &&
                    <div className="wprmprc-collection-modal-actions">
                        <button
                            className="wprm-popup-modal__btn"
                            onClick={() => {
                                this.setState({
                                    page: this.state.page + 1,
                                });
                            }}
                        >{ __wprm( 'Load more...' ) }</button>
                    </div>
                }
            </div>
        );
    }
}
