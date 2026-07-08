import React, { Component, Fragment } from 'react';
import Select from 'react-select';

import { __wprm } from 'Shared/Translations';

export default class AddSavedCollection extends Component {
    constructor(props) {
        super(props);

        this.state = {
            editing: false,
        }
    }


    render() {
        return (
            <div className="wprmprc-add-saved-collection-actions">
                {
                    this.state.editing
                    ?
                    <Select
                        className="wprmprc-add-saved-collection-select"
                        placeholder={ __wprm( 'Select a collection to add for this user' ) }
                        value={ false }
                        onChange={(option) => {
                            this.props.onAddSavedCollection(option.data);
                            this.setState({ editing: false });
                        }}
                        options={ wprmprc_public.saved_collections }
                        clearable={false}
                        styles={{
                            control: styles => ({ ...styles, borderRadius: 5 }),
                        }}
                        menuPlacement="top"
                    />
                    :
                    <span
                        className="wprmprc-action wprmprc-add-saved-collection-action"
                        onClick={() => this.setState({ editing: true }) }
                    >{ __wprm( 'Add Saved Collection' ) }</span>
                }
            </div>
        );
    }
}
