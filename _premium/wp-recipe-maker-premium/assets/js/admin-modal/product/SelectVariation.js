import React, { Component } from 'react';
import Select from 'react-select';

import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';

export default class SelectVariation extends Component {
    
    getOptions() {
        return this.props.variations.map(variation => ({
            value: variation.id,
            label: variation.display_name,
            name: variation.name,
        }));
    }
    
    render() {
        if (!this.props.variations || this.props.variations.length === 0) {
            return (
                <Select
                    placeholder={__wprm('No variations available')}
                    isDisabled={true}
                    options={[]}
                />
            );
        }
        
        const options = this.getOptions();
        const selectedOption = options.find(option => option.value === this.props.value);
        
        return (
            <Select
                placeholder={__wprm('Select a variation')}
                value={selectedOption}
                onChange={(option) => {
                    if (this.props.onValueChange) {
                        if (option) {
                            // Find the full variation object
                            const variation = this.props.variations.find(v => v.id === option.value);
                            this.props.onValueChange(variation);
                        } else {
                            this.props.onValueChange(null);
                        }
                    }
                }}
                options={options}
                clearable={true}
                isDisabled={this.props.disabled}
            />
        );
    }
}
