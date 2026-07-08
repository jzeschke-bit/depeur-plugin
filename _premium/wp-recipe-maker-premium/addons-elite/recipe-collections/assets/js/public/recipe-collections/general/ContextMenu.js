import React, { Component, Fragment } from 'react';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import CopyToClipboard from 'react-copy-to-clipboard';

import '../../../../css/public/context-menu.scss';

import Button from '../../../shared/Button';
import { __wprm } from 'Shared/Translations';
import Icon from './Icon';
import OurTooltip from './Tooltip';

export default class ContextMenu extends Component {
    constructor(props) {
        super(props);

        this.state = {
            confirming: false,
        }

        this.openerRef = React.createRef();
        this.menuRef = React.createRef();
        this.firstButtonRef = React.createRef();
        this.tippyInstance = null;
        this.onToggle = this.onToggle.bind(this);
        this.onClose = this.onClose.bind(this);
    }

    componentDidMount() {
        if ( this.openerRef.current ) {
            this.tippyInstance = tippy( this.openerRef.current, {
                theme: 'wprm',
                content: this.menuRef.current,
                interactive: true,
                trigger: 'click',
                placement: 'right',
                arrow: true,
                trigger: 'manual',
                onClickOutside: () => {
                    this.onClose();
                },
                onShown: () => {
                    if ( this.firstButtonRef.current ) {
                        this.firstButtonRef.current.focus();
                    }
                }
            });
        }
    }

    componentWillUnmount() {
        if (this.tippyInstance) {
            this.tippyInstance.destroy();
        }
    }

    onToggle() {
        if ( this.tippyInstance ) {
            if ( this.tippyInstance.state.isShown ) {
                this.tippyInstance.hide();
            } else {
                this.tippyInstance.show();
            }
        }
    }

    onClose( action = false ) {
        this.setState({
            confirming: false,
        }, () => {
            // Close tippy.
            if ( this.tippyInstance ) {
                this.tippyInstance.hide();
            }

            // Reset focus to opener.
            if ( this.openerRef.current ) {
                this.openerRef.current.focus();
            }

            // Do optional action.
            if ( false !== action ) {
                action();
            }
        });
    }

    render() {
        const { menu } = this.props;

        if ( ! menu || ! menu.length ) {
            return null;
        }

        const icon = this.props.hasOwnProperty('icon') ? this.props.icon : 'dots';
        const text = this.props.hasOwnProperty('text') ? this.props.text : false;
        const title = this.props.hasOwnProperty('title') ? this.props.title : false;

        
        let foundFirstButton = false;

        return (
            <Fragment>
                <Button
                    className="wprmprc-context-menu-clickable"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        this.onToggle();
                    }}
                    buttonRef={ this.openerRef }
                >
                    <OurTooltip
                        content={ title }
                        tabIndex="-1"
                    >
                        {
                            false !== icon
                            &&
                            <Icon
                                type={ icon }
                                tabIndex="-1"
                            />
                        }
                        {
                            false !== text
                            && text
                        }
                    </OurTooltip>
                </Button>
                <div
                    className="wprmprc-context-menu"
                    onKeyDown={ (e) => {
                        if (e.key === 'Escape') {
                            this.onClose();
                        }
                    } }
                    ref={this.menuRef}
                >
                    {
                        menu.map( ( item, index ) => {
                            // Divider.
                            if ( item.hasOwnProperty( 'divider' ) && item.divider ) {
                                return (
                                    <div className="wprmprc-context-menu-divider" key={ index } />
                                );
                            }

                            // Helpers.
                            const isDisabled = item.hasOwnProperty( 'disabled' ) ? item.disabled : false;
                            const hasAction = ! isDisabled && ( ( item.hasOwnProperty( 'action' ) && false !== item.action ) || item.hasOwnProperty( 'copyToClipboard' ) );
                            const closeOnAction = item.hasOwnProperty( 'closeOnAction' ) ? item.closeOnAction : true;
                            const needsConfirming = item.hasOwnProperty( 'confirm' ) ? true : false;
                            const isConfirming = needsConfirming && false !== this.state.confirming && index === this.state.confirming ? true : false;

                            let thisIsFirstButton = false;
                            if ( ! foundFirstButton && hasAction ) {
                                thisIsFirstButton = true;
                                foundFirstButton = true;
                            }

                            const buttonClass = `wprmprc-context-menu-item${ hasAction && ! isConfirming ? ' wprmprc-context-menu-action' : '' }${ isDisabled ? ' wprmprc-context-menu-item-disabled' : '' }`;

                            // Special case: CopyToClipboard.
                            if ( item.hasOwnProperty( 'copyToClipboard' ) ) {
                                return (
                                    <CopyToClipboard
                                        key={ index }
                                        text={ isDisabled ? '' : item.copyToClipboard.text }
                                        onCopy={ (text, result) => {
                                            if ( ! isDisabled ) {
                                                this.onClose(() => {
                                                    if ( result ) {
                                                        alert( item.copyToClipboard.message );
                                                    } else {
                                                        alert( __wprm( 'Something went wrong. Please contact support.' ) );
                                                    }
                                                });
                                            }
                                        }}
                                    >
                                        <Button
                                            className={ buttonClass }
                                            buttonRef={ thisIsFirstButton ? this.firstButtonRef : null }
                                        >{ item.label }</Button>
                                    </CopyToClipboard>
                                );
                            }

                            return (
                                <Fragment key={ index }>
                                    <Button
                                        className={ buttonClass }
                                        onClick={ () => {
                                            if ( hasAction && ! isConfirming ) {
                                                if ( needsConfirming ) {
                                                    this.setState({
                                                        confirming: index,
                                                    });
                                                } else {
                                                    if ( closeOnAction ) {
                                                        this.onClose( item.action );
                                                    } else {
                                                        item.action();
                                                    }
                                                }
                                            }
                                        }}
                                        isButton={ hasAction }
                                        buttonRef={ thisIsFirstButton ? this.firstButtonRef : null }
                                    >{
                                        isConfirming
                                        ?
                                        item.confirm
                                        :
                                        item.label
                                    }</Button>
                                    {
                                        isConfirming
                                        &&
                                        <Button
                                            className="wprmprc-context-menu-item wprmprc-context-menu-action wprmprc-context-menu-item-confirming"
                                            onClick={ () => {
                                                if ( closeOnAction ) {
                                                    this.onClose( item.action );
                                                } else {
                                                    this.setState({
                                                        confirming: false,
                                                    }, () => {
                                                        item.action();
                                                    });
                                                    
                                                }
                                            }}
                                        >{ __wprm( 'Click to confirm...' ) }</Button>
                                    }
                                </Fragment>
                            );
                        } )
                    }
                </div>
            </Fragment>
        )
    }
}