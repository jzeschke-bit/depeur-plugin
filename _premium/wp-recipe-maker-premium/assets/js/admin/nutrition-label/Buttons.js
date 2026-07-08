import React from 'react';

const Buttons = (props) => {
    return (
        <div className="wprmp-nutrition-label-editor-buttons wprmp-nutrition-label-editor-side-section">
            <button
                className="button button-primary button-compact"
                disabled={ ! props.changesMade }
                onClick={() => {
                    if ( confirm( 'Are you sure you want to save your changes?' ) ) {
                        props.onSave();
                    }
                }}
            >{ props.saving ? '...' : 'Save Changes' }</button>
            <span>&nbsp;</span>
            <button
                className="button button-secondary button-compact"
                onClick={() => {
                    if ( ! props.changesMade || confirm( 'Are you sure you want to cancel your changes?' ) ) {
                        props.onCancel();
                    }
                }}
            >Cancel Changes</button>
            <span>&nbsp;</span>
            <a
                href="#"
                onClick={() => {
                    if ( confirm( 'Are you sure you want to start over?' ) ) {
                        props.onReset();
                    }
                }}
            >Start Over</a>
        </div>
    );
}

export default Buttons;