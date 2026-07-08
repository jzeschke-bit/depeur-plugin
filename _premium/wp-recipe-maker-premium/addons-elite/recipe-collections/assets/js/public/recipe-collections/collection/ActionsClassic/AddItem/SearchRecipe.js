import React, { Component } from 'react';

import { __wprm } from 'Shared/Translations';

import Api from '../../../general/Api';
import Loader from '../../../general/Loader';

export default class SearchRecipe extends Component {
    constructor(props) {
        super(props);

        this.searchInput = React.createRef();

        this.state = {
            searching: false,
        }
    }

    componentDidMount() {
        this.searchInput.current.select();
    }

    onSearch(search) {
        // Clear current results.
        this.props.onChangeSearch(search);
        this.props.onChangeAddItems([]);

        // Update state.
        this.setState({
            searching: true,
        });

        // Search via API.
        Api.searchRecipes(search).then((recipes) => {
            if ( false !== recipes ) {
                this.props.onChangeAddItems(recipes);

                this.setState({
                    searching: false,
                });
            }
        });
    }
    
    render() {
        return (
            <div className='wprmprc-collection-action-search-recipe'>
                <input
                    ref={this.searchInput}
                    type="text"
                    value={this.props.search}
                    placeholder={ __wprm( 'Start typing to search...' ) }
                    onChange={(event) => { this.onSearch(event.target.value) }}
                />
                {
                    this.state.searching
                    &&
                    <div style={{marginTop: 10}}>
                        <Loader/>
                    </div>
                }
            </div>
        );
    }
}
