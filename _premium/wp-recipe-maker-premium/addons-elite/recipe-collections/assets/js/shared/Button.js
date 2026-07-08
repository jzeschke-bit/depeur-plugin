import React from 'react';
import '../../css/public/button.scss';

const Button = (props) => {
    if ( props.hasOwnProperty( 'isButton' ) && false === props.isButton ) {
        return (
            <div
                className={ props.className }
            >
                { props.children }
            </div>
        )
    }

    const tag = props.hasOwnProperty('tag') ? props.tag : 'div';
    const tabIndex = props.hasOwnProperty('tabIndex') ? props.tabIndex : 0;
    const disabled = props.hasOwnProperty('disabled') ? props.disabled : false;

    let className = props.hasOwnProperty('className') ? props.className : '';
    className += ' wprmprc-button';
    if ( disabled ) { className += ' wprmprc-button--disabled'; }
    className = className.trim();

    return React.createElement(
        tag,
        {
            onClick: props.onClick,
            role: 'button',
            tabIndex: disabled ? '-1' : tabIndex,
            onKeyDown: (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    props.onClick(e);
                }
            },
            className: className,
            'aria-disabled': disabled,
            'aria-label': props.hasOwnProperty('aria-label') ? props['aria-label'] : null,
            href: props.hasOwnProperty('href') ? props.href : null,
            ref: props.hasOwnProperty('buttonRef') ? props.buttonRef : null,
        },
        props.children
    );
}

export default Button;