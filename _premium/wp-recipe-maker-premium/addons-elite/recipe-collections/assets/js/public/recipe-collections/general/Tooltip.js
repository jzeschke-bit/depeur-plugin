import React, { useEffect, useRef } from 'react';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

const OurTooltip = (props) => {
    const content = props.hasOwnProperty( 'content' ) ? props.content : false;

    if ( ! content ) {
        return props.children;
    }

    // Props for tippy.js.
    let tippyProps = {
        theme: 'wprm',
        content: `<div className="wprmprc-tooltip">${ content }</div>`,
        allowHTML: true,
    };

    if ( props.hasOwnProperty( 'style' ) ) {
        tippyProps.style = props.style;
    }
    
    // Container element reference.
    const containerRef = useRef();

    useEffect(() => {
        if ( containerRef.current ) {
            const instance = tippy( containerRef.current, tippyProps );
    
            return () => {
                instance.destroy();
            };
        }
    }, [tippyProps]);

    return (
        <div
            className="wprmprc-tooltip-container"
            tabIndex={ props.hasOwnProperty('tabIndex') && false !== props.tabIndex ? props.tabIndex : 0 }
            ref={ containerRef }
        >
            { props.children }
        </div>
    );
};

export default OurTooltip;