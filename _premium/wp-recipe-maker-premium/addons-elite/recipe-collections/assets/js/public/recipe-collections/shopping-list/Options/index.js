import React from 'react';

import { __wprm } from 'Shared/Translations';

import Checkbox from '../../general/Checkbox';

const Options = (props) => {
    const { options, onOptionsChange } = props;

    return (
        <div className="wprmprc-shopping-list-collection">
            <div className="wprmprc-shopping-list-collection-header">
                <div className="wprmprc-shopping-list-collection-name">
                    { __wprm( 'Shopping List Options' ) }
                </div>
            </div>
            <div className="wprmprc-shopping-list-options-container">
                <div>
                    <strong>{ __wprm( 'Ingredients' ) }</strong>
                    <div className="wprmprc-shopping-list-options-option">
                        <Checkbox
                            checked={ options.notes }
                            onChange={ ( checked ) => {
                                onOptionsChange({
                                    notes: checked,
                                });
                            } }
                            id="wprmprc-shopping-list-option-notes"
                        />
                        <label
                            htmlFor="wprmprc-shopping-list-option-notes"
                        >{ __wprm( 'Include ingredient notes' ) }</label>
                    </div>
                </div>
                {
                    wprmprc_public.settings.unit_conversion_enabled
                    &&
                    <div className="wprmprc-shopping-list-options-system-container">
                        <strong>{ __wprm( 'Preferred Unit System' ) }</strong>
                        <div className="wprmprc-shopping-list-options-option">
                            <input
                                type="radio"
                                value={1}
                                name="wprmprc-shopping-list-options-system"
                                id="wprmprc-shopping-list-options-system-1"
                                checked={ 1 === options.system }
                                onChange={(e) => {
                                    onOptionsChange({
                                        system: 1,
                                    });
                                }}
                            /><label htmlFor="wprmprc-shopping-list-options-system-1">{ wprmprc_public.settings.unit_conversion_system_1 }</label>
                        </div>
                        <div className="wprmprc-shopping-list-options-option">
                            <input
                                type="radio"
                                value={2}
                                name="wprmprc-shopping-list-options-system"
                                id="wprmprc-shopping-list-options-system-2"
                                checked={ 2 === options.system }
                                onChange={(e) => {
                                    onOptionsChange({
                                        system: 2,
                                    });
                                }}
                            /><label htmlFor="wprmprc-shopping-list-options-system-2">{ wprmprc_public.settings.unit_conversion_system_2 }</label>
                        </div>
                    </div>
                }
            </div>
        </div>
    );
}
export default Options;