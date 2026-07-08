import React, { Component } from 'react';
import AsyncSelect from 'react-select/async';

import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';

export default class SelectProduct extends Component {
    constructor(props) {
        super(props);
        this.state = {
            defaultOptions: [],
            hasSearched: false,
        };
    }

    componentDidMount() {
        // If we have an initial search term and no product is selected, perform the search
        if (this.props.initialSearch && !this.props.value) {
            this.performInitialSearch();
        }
    }

    performInitialSearch() {
        if (this.state.hasSearched) {
            return;
        }

        this.setState({ hasSearched: true });
        
        Api.product.search(this.props.initialSearch).then((data) => {
            if (data && data.products) {
                this.setState({ defaultOptions: data.products });
            }
        }).catch(() => {
            this.setState({ defaultOptions: [] });
        });
    }

    getOptions(input) {
        if (!input) {
			return Promise.resolve({ options: [] });
        }

		return Api.product.search(input).then((data) => {
            if ( data ) {
                return data.products;
            } else {
                return [];
            }
        });
    }

    formatOptionLabel = (option) => {
        return (
            <div style={{ display: 'flex', alignItems: 'center' }}>
                {option.image_url && (
                    <img 
                        src={option.image_url} 
                        alt={option.name}
                        style={{ 
                            width: '24px', 
                            height: '24px', 
                            marginRight: '8px',
                            objectFit: 'cover',
                            borderRadius: '2px'
                        }} 
                    />
                )}
                <span>{option.text}</span>
            </div>
        );
    }

    render() {
        return (
            <AsyncSelect
                placeholder={ __wprm( 'Search for products' ) }
                value={this.props.value}
                onChange={(option) => {
                    if (this.props.onValueChange) {
                        this.props.onValueChange(option);
                    }
                }}
                getOptionValue={({id}) => id}
                getOptionLabel={({text}) => text} 
                formatOptionLabel={this.formatOptionLabel}
                loadOptions={this.getOptions.bind(this)}
                defaultOptions={this.state.defaultOptions}
                noOptionsMessage={() => __wprm( 'No products found' ) }
                clearable={false}
            />
        );
    }
}
