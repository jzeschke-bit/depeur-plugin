import React, { Component } from 'react';
import ReactDOM from 'react-dom';

import { __wprm } from 'Shared/Translations';

export default class ModalContainer extends Component {
    constructor(props) {
        super(props);

        const uid = this.props.hasOwnProperty( 'modal' ) ? this.props.modal : false;

        this.state = {
            uid,
        }

        this.openTimeout = null;
        this.closeTimeout = null;
        this.isModalOpen = false;

        this.onMaybeClose = this.onMaybeClose.bind( this );
        this.onParentClose = this.onParentClose.bind( this );
        this.onExternalModalOpen = this.onExternalModalOpen.bind( this );
        this.onExternalModalClose = this.onExternalModalClose.bind( this );
        this.openModal = this.openModal.bind( this );
        this.closeModal = this.closeModal.bind( this );
    }

    componentDidMount() {
        document.addEventListener( 'wprm-modal-open', this.onExternalModalOpen );
        document.addEventListener( 'wprm-modal-close', this.onExternalModalClose );

        if ( this.props.open ) {
            this.openModal();
        }
    }

    componentDidUpdate( prevProps ) {
        if ( prevProps.open !== this.props.open ) {
            if ( this.props.open ) {
                this.openModal();
            } else if ( this.isModalOpen ) {
                this.closeModal();
            }
        }
    }

    componentWillUnmount() {
        document.removeEventListener( 'wprm-modal-open', this.onExternalModalOpen );
        document.removeEventListener( 'wprm-modal-close', this.onExternalModalClose );

        if ( this.openTimeout ) {
            clearTimeout( this.openTimeout );
            this.openTimeout = null;
        }

        if ( this.closeTimeout ) {
            clearTimeout( this.closeTimeout );
            this.closeTimeout = null;
        }

        if (
            false !== this.state.uid
            && window.WPRecipeMaker
            && window.WPRecipeMaker.modal
            && 'function' === typeof window.WPRecipeMaker.modal.close
            && ( this.isModalOpen || `${ window.WPRecipeMaker.modal.currentOpenUid }` === `${ this.state.uid }` )
        ) {
            window.WPRecipeMaker.modal.close( this.state.uid );
        }
    }

    onExternalModalOpen( event ) {
        if (
            ! event
            || ! event.detail
            || `${ event.detail.uid }` !== `${ this.state.uid }`
        ) {
            return;
        }

        this.isModalOpen = true;
    }

    onExternalModalClose( event ) {
        if (
            ! event
            || ! event.detail
            || `${ event.detail.uid }` !== `${ this.state.uid }`
        ) {
            return;
        }

        const wasModalOpen = this.isModalOpen;
        this.isModalOpen = false;

        if ( this.props.open && wasModalOpen ) {
            this.onParentClose();
        }
    }

    onParentClose() {
        if ( this.props.hasOwnProperty( 'onClose' ) ) {
            this.props.onClose();
        }
    }

    onMaybeClose() {
        if (
            false !== this.state.uid
            && window.WPRecipeMaker
            && window.WPRecipeMaker.modal
            && 'function' === typeof window.WPRecipeMaker.modal.close
        ) {
            window.WPRecipeMaker.modal.close( this.state.uid );
            return;
        }

        this.onParentClose();
    }
    
    openModal() {
        if ( false !== this.state.uid ) {
            if ( this.closeTimeout ) {
                clearTimeout( this.closeTimeout );
                this.closeTimeout = null;
            }

            // Need setTimeout to decouple from the render cycle.
            this.openTimeout = setTimeout( () => {
                this.openTimeout = null;
                window.WPRecipeMaker.modal.open( this.state.uid );
            } );
        }
    }

    closeModal() {
        if ( false !== this.state.uid ) {
            if ( this.openTimeout ) {
                clearTimeout( this.openTimeout );
                this.openTimeout = null;
            }

            this.closeTimeout = setTimeout( () => {
                this.closeTimeout = null;
                window.WPRecipeMaker.modal.close( this.state.uid );
            } );
        }
    }

    render() {        
        const container = false !== this.state.uid ? document.querySelector( `#wprm-popup-modal-${ this.state.uid } .wprm-recipe-collections-modal` ) : false;
        if ( container ) {
            return ReactDOM.createPortal(
                <div
                    className="wprm-popup-modal__overlay"
                    tabIndex="-1"
                    onClick={ (e) => {
                        if ( e.target.classList.contains( 'wprm-popup-modal__overlay' ) ) {
                            this.onMaybeClose();
                        }
                    } }
                >
                    <div className={ `wprm-popup-modal__container${ this.props.hasOwnProperty('class') ? ` wprm-popup-modal__container-${ this.props.class }`: '' }` } role="dialog" aria-modal="true" aria-labelledby={ `wprm-popup-modal-${ this.state.uid }-title` }>
                        <header className="wprm-popup-modal__header">
                            <h2 className="wprm-popup-modal__title" id={ `wprm-popup-modal-${ this.state.uid }-title` }>{ this.props.title }</h2>

                            <button
                                className="wprm-popup-modal__close"
                                aria-label={ __wprm( 'Close' ) }
                                onClick={ this.onMaybeClose }
                            ></button>
                        </header>

                        <div className="wprm-popup-modal__content" id={ `wprm-popup-modal-${ this.state.uid }-content` }>
                            { this.props.children }
                        </div>

                        {
                            this.props.hasOwnProperty( 'button' )
                            &&
                            <footer className="wprm-popup-modal__footer">
                                <button
                                    className="wprm-popup-modal__btn"
                                    onClick={ () => {
                                        const close = this.props.hasOwnProperty( 'buttonAction' ) ? this.props.buttonAction() : true;

                                        if ( close ) {
                                            this.onMaybeClose();
                                        }
                                    } }
                                >{ this.props.button }</button>
                                {
                                    this.props.hasOwnProperty( 'buttonSecondary' )
                                    &&
                                    <button
                                        className="wprm-popup-modal__btn wprm-popup-modal__btn--secondary"
                                        onClick={ () => {
                                            const close = this.props.hasOwnProperty( 'buttonSecondaryAction' ) ? this.props.buttonSecondaryAction() : true;

                                            if ( close ) {
                                                this.onMaybeClose();
                                            }
                                        } }
                                    >{ this.props.buttonSecondary }</button>
                                }
                            </footer>
                        }
                    </div>
                </div>,
                container,
            );
        }
        
        return null;
    }
}
