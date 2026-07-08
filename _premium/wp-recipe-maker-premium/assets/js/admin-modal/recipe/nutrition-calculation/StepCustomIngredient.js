import React, { Component } from 'react';
import AsyncSelect from 'react-select/async';

import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';

export default class StepCustomIngredient extends Component {
    getOptions(input) {
        input = input ? input : this.props.defaultSearch;

        if ( ! input ) {
			return Promise.resolve([]);
        }

        return Api.nutrition.getCustomIngredients(input).then((data) => {
            if ( data ) {
                return data.ingredients;
            } else {
                return [];
            }
        });
    }

    render() {
        return (
            <AsyncSelect
                placeholder={ __wprm( 'Select or start typing to search for a saved ingredient' ) }
                value={this.props.value}
                onChange={this.props.onValueChange}
                getOptionValue={({id}) => id}
                getOptionLabel={({text}) => text}
                loadOptions={this.getOptions.bind(this)}
                defaultOptions={true}
                clearable={false}
                menuPlacement="top"
                styles={{
                    control: (provided) => ({
                        ...provided,
                        backgroundColor: 'white',
                    }),
                    container: (provided) => ({
                        ...provided,
                        width: '100%',
                        maxWidth: '440px',
                        marginBottom: '10px',
                    }),
                }}
            />
        );
    }
}