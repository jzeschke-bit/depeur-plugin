import React from 'react';

const BlockLine = (props) => {
    if ( ! props.block ) {
        return null;
    }

    return (
        <div
            className="wprmp-nutrition-label-block-line"
            style={{
                height: props.block.height,
            }}
        />
    );
}

export default BlockLine;