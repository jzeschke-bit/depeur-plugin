import React, { Component, Fragment } from 'react';

import '../../../css/admin/modal/amazon.scss';

import Api from 'Shared/Api';
import Loader from 'Shared/Loader';
import { __wprm } from 'Shared/Translations';

import Header from 'Modal/general/Header';
import Footer from 'Modal/general/Footer';

import FieldContainer from 'Modal/fields/FieldContainer';
import FieldDropdown from 'Modal/fields/FieldDropdown';
import FieldText from 'Modal/fields/FieldText';

export default class Amazon extends Component {
    constructor(props) {
        super(props);

        // Default to searching for ASIN, otherwise use term name.
        const term = props.args.hasOwnProperty( 'term' ) ? JSON.parse( JSON.stringify( props.args.term ) ) : false;
        const currentAsin = term && term.hasOwnProperty( 'amazon_asin' ) ? term.amazon_asin : '';

        // Use search that's passed along, otherwise by asin or term.
        let search = props.args.hasOwnProperty( 'search' ) ? props.args.search : '';
        if ( ! search ) {
            search = currentAsin ? currentAsin : ( term ? term.name : '' );
        }

        this.state = {
            term,
            currentAsin,
            prevSearch: false,
            search,
            isSearching: false,
            products: false,
            error: false,
        };

        this.searchInput = React.createRef();
        this.onSearch = this.onSearch.bind(this);
        this.searchApi = this.searchApi.bind(this);
        this.selectProduct = this.selectProduct.bind(this);
    }

    componentDidMount() {
        this.searchInput.current.focus();
        this.onSearch();
    }

    onSearch() {
        if ( ! this.state.isSearching && '' !== this.state.search ) {
            this.setState({
                isSearching: true,
            }, () => {
                this.searchApi( this.state.search );
            });
        }
    }

    searchApi(search) {
        Api.amazon.searchProducts(search).then((data) => {
            let newState = {
                prevSearch: search,
                isSearching: false,
            };

            if ( data ) {
                newState.products = data.products;
                newState.error = data.error;
            } else {
                newState.products = [];
                newState.error = {
                    code: 'unknown',
                    message: __wprm( 'Something went wrong. Please try again.' ),
                }
            }

            this.setState( newState );
        });
    }

    selectProduct( product ) {
        if ( 'function' === typeof this.props.args.selectCallback ) {
            this.props.args.selectCallback( product );
        }
        this.props.maybeCloseModal();
    }

    allowCloseModal() {
        return true;
    }

    render() {
        return (
            <Fragment>
                <Header
                    onCloseModal={ this.props.maybeCloseModal }
                >
                    {
                        ! this.state.currentAsin
                        ?
                        __wprm( 'Select Amazon Product' )
                        :
                        __wprm( 'Change Amazon Product' )
                    }
                </Header>
                <div className="wprm-admin-modal-amazon-container">
                    <p><strong>{ __wprm( 'Find Amazon Product:' ) }</strong></p>
                    <div className="wprm-admin-modal-amazon-search">
                        <input
                            ref={ this.searchInput }
                            type="text"
                            value={ this.state.search }
                            onChange={(e) => {
                                this.setState({
                                    search: e.target.value,
                                });
                            }}
                            onKeyDown={(e) => {
                                if (e.which === 13 || e.keyCode === 13) {
                                    this.onSearch();
                                }
                            }}
                            disabled={ this.state.isSearching }
                        />
                        <button
                            className="button button-primary button-compact"
                            onClick={this.onSearch}
                            disabled={ this.state.isSearching || '' === this.state.search || this.state.prevSearch === this.state.search }
                        >{ __wprm( 'Search' ) }</button>
                    </div>
                    {
                        this.state.isSearching
                        ?
                        <Loader />
                        :
                        <Fragment>
                            {
                                false !== this.state.error
                                ?
                                <p>{ __wprm( 'Error' ) }: { this.state.error.message }</p>
                                :
                                <Fragment>
                                    {
                                        ! Array.isArray( this.state.products )
                                        || 0 === this.state.products.length
                                        ?
                                        <Fragment>
                                            {
                                                this.state.prevSearch
                                                ?
                                                <p>{ __wprm( 'No products found for' ) } "{ this.state.prevSearch }".</p>
                                                :
                                                <p>{ __wprm( 'No products found.' ) }</p>
                                            }
                                        </Fragment>
                                        :
                                        <Fragment>
                                            {
                                                false !== this.state.prevSearch
                                                &&
                                                <p>{ __wprm( 'Results for' ) } "{ this.state.prevSearch }":</p>
                                            }
                                            <div className="wprm-admin-modal-amazon-matches">
                                                {
                                                    this.state.products.map((match, index) => (
                                                        <div
                                                            className="wprm-admin-modal-amazon-matches-option"
                                                            key={index}
                                                        >
                                                            <div className="wprm-admin-modal-amazon-matches-option-details">
                                                                <a
                                                                    href={ match.link }
                                                                    target="_blank"
                                                                    className="wprm-admin-modal-amazon-matches-option-asin"
                                                                >{ match.asin }</a>
                                                                <div
                                                                    className="wprm-admin-modal-amazon-matches-option-price"
                                                                >{ match.price }</div>
                                                                {
                                                                    match.image
                                                                    ?
                                                                    <img
                                                                        className="wprm-admin-modal-amazon-matches-option-image"
                                                                        src={ match.image }
                                                                    />
                                                                    :
                                                                    null
                                                                }
                                                                <div
                                                                    className="wprm-admin-modal-amazon-matches-option-name"
                                                                >{ match.name }</div>
                                                            </div>
                                                            <div className="wprm-admin-modal-amazon-matches-option-actions">
                                                                {
                                                                    this.state.currentAsin === match.asin
                                                                    ?
                                                                    <button
                                                                        className="button button-secondary button-compact"
                                                                        disabled={ true }
                                                                    >
                                                                        { __wprm( 'Current Product' ) }
                                                                    </button>
                                                                    :
                                                                    <button
                                                                        className="button button-primary button-compact"
                                                                        onClick={ () => {
                                                                            this.selectProduct( match );
                                                                        } }
                                                                    >
                                                                        { __wprm( 'Select Product' ) }
                                                                    </button>
                                                                }
                                                            </div>
                                                        </div>
                                                    ))
                                                }
                                            </div>
                                        </Fragment>
                                    }
                                </Fragment>
                            }
                        </Fragment>
                    }
                </div>
            </Fragment>
        );
    }
}