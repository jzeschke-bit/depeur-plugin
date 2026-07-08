import React, { Component, Fragment } from 'react';

import '../../../css/admin/modal/nutrition.scss';
import '../../../css/admin/modal/recipe/nutrition-calculation.scss';

import Api from 'Shared/Api';
import Loader from 'Shared/Loader';
import { __wprm } from 'Shared/Translations';

import Header from 'Modal/general/Header';

const getIngredientText = ( ingredient = {} ) => {
    if ( ingredient.display ) {
        return ingredient.display;
    }

    const parts = [];
    if ( ingredient.amount ) { parts.push( ingredient.amount ); }
    if ( ingredient.unit ) { parts.push( ingredient.unit ); }
    if ( ingredient.name ) { parts.push( ingredient.name ); }

    let fullIngredientText = parts.join( ' ' );

    if ( ingredient.notes ) {
        fullIngredientText += ` (${ ingredient.notes })`;
    }

    return fullIngredientText;
};

export default class NutritionSearch extends Component {
    constructor(props) {
        super(props);

        this.searchInput = React.createRef();

        const ingredient = props.args.ingredient || {};
        const initialSearch = props.args.initialSearch || ingredient.name || ingredient.display || '';

        this.state = {
            ingredient,
            search: initialSearch,
            prevSearch: initialSearch,
            options: false,
            isSearching: !! initialSearch,
        };

        this.onSearch = this.onSearch.bind(this);
        this.searchApi = this.searchApi.bind(this);
    }

    componentDidMount() {
        if ( this.searchInput.current ) {
            this.searchInput.current.focus();
        }

        if ( this.state.isSearching ) {
            this.searchApi( this.state.search );
        }
    }

    onSearch() {
        if ( ! this.state.isSearching && '' !== this.state.search ) {
            this.setState( {
                isSearching: true,
            }, () => {
                this.searchApi( this.state.search );
            } );
        }
    }

    searchApi(search) {
        Api.nutrition.getApiOptions(search).then((data) => {
            this.setState({
                options: data && data.matchOptions ? data.matchOptions : [],
                prevSearch: search,
                isSearching: false,
            });
        });
    }

    render() {
        const ingredientText = getIngredientText( this.state.ingredient );

        return (
            <Fragment>
                <Header onCloseModal={ this.props.maybeCloseModal }>
                    { __wprm( 'Pick Nutrition Match' ) }
                </Header>
                <div className="wprm-admin-modal-nutrition-container">
                    <div className="wprm-admin-modal-recipe-nutrition-calculation-match">
                        {
                            ingredientText
                            &&
                            <p><strong>{ __wprm( 'Find a match for:' ) }</strong> { ingredientText }</p>
                        }
                        <div className="wprm-admin-modal-recipe-nutrition-calculation-match-search">
                            <input
                                ref={ this.searchInput }
                                type="text"
                                value={ this.state.search }
                                onChange={ (e) => {
                                    this.setState({
                                        search: e.target.value,
                                    });
                                } }
                                onKeyDown={ (e) => {
                                    if ( e.which === 13 || e.keyCode === 13 ) {
                                        this.onSearch();
                                    }
                                } }
                                disabled={ this.state.isSearching }
                            />
                            <button
                                className="button button-primary button-compact"
                                onClick={ this.onSearch }
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
                                    ! Array.isArray( this.state.options )
                                    || 0 === this.state.options.length
                                    ?
                                    <Fragment>
                                        {
                                            this.state.prevSearch
                                            ?
                                            <p>{ __wprm( 'No ingredients found for' ) } "{ this.state.prevSearch }".</p>
                                            :
                                            <p>{ __wprm( 'No ingredients found.' ) }</p>
                                        }
                                    </Fragment>
                                    :
                                    <Fragment>
                                        {
                                            false !== this.state.prevSearch
                                            &&
                                            <p>{ __wprm( 'Results for' ) } "{ this.state.prevSearch }":</p>
                                        }
                                        <div className="wprm-admin-modal-recipe-nutrition-calculation-matches">
                                            {
                                                this.state.options.map((match, index) => (
                                                    <div
                                                        className="wprm-admin-modal-recipe-nutrition-calculation-matches-option"
                                                        onClick={ () => {
                                                            if ( 'function' === typeof this.props.args.saveCallback ) {
                                                                this.props.args.saveCallback( match, this.state.search, this.state.options );
                                                            }
                                                            this.props.maybeCloseModal();
                                                        } }
                                                        key={ index }
                                                    >
                                                        {
                                                            match.image
                                                            ?
                                                            <img
                                                                className="wprm-admin-modal-recipe-nutrition-calculation-matches-option-image"
                                                                src={ `https://spoonacular.com/cdn/ingredients_100x100/${match.image}` }
                                                            />
                                                            :
                                                            null
                                                        }
                                                        <div className="wprm-admin-modal-recipe-nutrition-calculation-matches-option-name">
                                                            { match.name }{ match.aisle ? ` (${ match.aisle.toLowerCase() })` : '' }
                                                        </div>
                                                    </div>
                                                ))
                                            }
                                        </div>
                                    </Fragment>
                                }
                            </Fragment>
                        }
                    </div>
                </div>
            </Fragment>
        );
    }
}
