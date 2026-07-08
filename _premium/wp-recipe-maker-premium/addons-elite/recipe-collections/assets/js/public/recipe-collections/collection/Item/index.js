import React, { Component, Fragment } from 'react';
import { Draggable } from 'react-beautiful-dnd';

import ItemContent from './ItemContent';

const Item = (props) => {
    const { item } = props;

    // Draggable by default if not explicitely set.
    let draggable = props.hasOwnProperty( 'draggable' ) ? props.draggable : true;
    if ( 'saved' === props.type || 'shared' === props.type || ( props.collection && props.collection.fixed && 'admin' !== props.type ) ) {
        draggable = false;
    }

    return (
        <Fragment>
            {
                draggable
                ?
                <Draggable
                    draggableId={ `${item.id}` }
                    index={ props.index }
                    type='RECIPE'
                >
                    {(provided, snapshot) => (
                        <ItemContent
                            { ...props }
                            draggable={ provided }
                        />
                    )}
                </Draggable>
                :
                <ItemContent
                    { ...props }
                />
            }
        </Fragment>
    );
}

export default Item;