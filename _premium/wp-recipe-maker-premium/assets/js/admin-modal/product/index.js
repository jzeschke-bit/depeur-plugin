import React, { Component, Fragment } from 'react';

import '../../../css/admin/modal/product.scss';

import Api from 'Shared/Api';
import { __wprm } from 'Shared/Translations';

import Header from 'Modal/general/Header';
import Footer from 'Modal/general/Footer';

import FieldContainer from 'Modal/fields/FieldContainer';
import FieldDropdown from 'Modal/fields/FieldDropdown';
import FieldText from 'Modal/fields/FieldText';

import SelectProduct from './SelectProduct';
import SelectVariation from './SelectVariation';

const emptyProduct = {
    plugin: 'woocommerce',
    id: 0,
    name: '',
    variation_id: null,
    variation_name: '',
    variation_image_url: '',
};

export default class Menu extends Component {
    constructor(props) {
        super(props);

        let product = emptyProduct;

        let editing = false;
        if ( props.args.hasOwnProperty( 'product' ) && props.args.product ) {
            editing = true;
            product = JSON.parse( JSON.stringify( props.args.product ) );
        }

        this.state = {
            editing,
            label: props.args.hasOwnProperty( 'label' ) ? props.args.label : false,
            product,
            originalProduct: JSON.parse( JSON.stringify( product ) ),
            savingChanges: false,
            variations: [],
        };

        this.changesMade = this.changesMade.bind(this);
        this.saveChanges = this.saveChanges.bind(this);
        this.loadVariations = this.loadVariations.bind(this);
    }

    componentDidMount() {
        // Load variations if we're editing a product with variations
        if (this.state.product.has_variations && this.state.product.id > 0) {
            this.loadVariations();
        }
    }

    loadVariations() {
        if (this.state.product.id > 0) {
            Api.product.getVariations(this.state.product.id).then((data) => {
                if (data && data.variations) {
                    this.setState({ variations: data.variations });
                }
            }).catch(() => {
                this.setState({ variations: [] });
            });
        }
    }

    saveChanges() {
        this.setState({
            savingChanges: true,
        }, () => {
            // If we have a term (taxonomy editing), save to taxonomy meta
            if ( this.props.args.term ) {
                // Extract taxonomy type from full taxonomy name (e.g., 'wprm_equipment' -> 'equipment')
                const taxonomyType = this.props.args.taxonomy.replace('wprm_', '');
                Api.manage.updateTaxonomyMeta( taxonomyType, this.props.args.term, { product: this.state.product } ).then(() => {
                    this.setState({
                        savingChanges: false,
                    },() => {
                        if ( 'function' === typeof this.props.args.saveCallback ) {
                            this.props.args.saveCallback( this.state.product );
                        }
                        this.props.maybeCloseModal();
                    });
                });
            } else {
                // If no term (item editing), just call the callback
                this.setState({
                    savingChanges: false,
                },() => {
                    if ( 'function' === typeof this.props.args.saveCallback ) {
                        this.props.args.saveCallback( this.state.product );
                    }
                    this.props.maybeCloseModal();
                });
            }
        })
    }

    allowCloseModal() {
        return ! this.state.savingChanges && ( ! this.changesMade() || confirm( __wprm( 'Are you sure you want to close without saving changes?' ) ) );
    }

    changesMade() {
        return JSON.stringify( this.state.product ) !== JSON.stringify( this.state.originalProduct );
    }

    render() {
        return (
            <Fragment>
                <Header
                    onCloseModal={ this.props.maybeCloseModal }
                >
                    {
                        this.state.editing
                        ?
                        `${ __wprm( 'Editing Product' ) }${ this.state.label ? ` - ${ this.state.label }` : '' }`
                        :
                        `${ __wprm( 'Setting Product' ) }${ this.state.label ? ` - ${ this.state.label }` : '' }`
                    }
                </Header>
                <div className="wprm-admin-modal-product-container">
                    <FieldContainer id="search" label={ __wprm( 'Search' ) }>
                        <SelectProduct
                            value={ this.state.product.id }
                            initialSearch={ !this.state.editing && this.state.label ? this.state.label : null }
                            onValueChange={(product) => {
                                let newProduct = JSON.parse( JSON.stringify( this.state.product ) );
                                newProduct.id = product.id;
                                newProduct.name = product.name;
                                newProduct.has_variations = product.has_variations;
                                // Reset variation when product changes
                                newProduct.variation_id = null;
                                newProduct.variation_name = '';
                                newProduct.variation_image_url = '';

                                this.setState({ product: newProduct }, () => {
                                    // Load variations for the new product if it has variations
                                    if (newProduct.has_variations && newProduct.id > 0) {
                                        this.loadVariations();
                                    }
                                });
                            }}
                        />
                    </FieldContainer>
                    <FieldContainer id="id" label={ __wprm( 'Product ID' ) }>
                        <p>
                            {
                                0 < this.state.product.id
                                ?
                                this.state.product.id
                                :
                                __wprm( 'No product set yet' )
                            }
                        </p>
                    </FieldContainer>
                    <FieldContainer id="name" label={ __wprm( 'Product Name' ) }>
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                            <p style={{ margin: 0 }}>
                                {
                                    0 < this.state.product.id
                                    ?
                                    this.state.product.name
                                    :
                                    __wprm( 'No product set yet' )
                                }
                            </p>
                            {
                                0 < this.state.product.id && this.state.product.image_url
                                &&
                                <img 
                                    src={ this.state.product.image_url } 
                                    alt={ this.state.product.name }
                                    style={{ maxWidth: '50px', maxHeight: '50px', borderRadius: '4px' }}
                                />
                            }
                        </div>
                    </FieldContainer>
                    {
                        this.state.product.has_variations && 0 < this.state.product.id
                        &&
                        <div className="wprm-admin-modal-product-variation">
                            <FieldContainer id="variation" label={ __wprm( 'Product Variation' ) }>
                                <SelectVariation
                                    variations={ this.state.variations }
                                    value={ this.state.product.variation_id }
                                    onValueChange={(variation) => {
                                        let newProduct = JSON.parse( JSON.stringify( this.state.product ) );
                                        newProduct.variation_id = variation ? variation.id : null;
                                        newProduct.variation_name = variation ? variation.name : '';
                                        newProduct.variation_image_url = variation ? variation.image_url : '';
                                        this.setState({ product: newProduct });
                                    }}
                                />
                            </FieldContainer>
                            <FieldContainer id="variation-id" label={ __wprm( 'Variation ID' ) }>
                                <p>
                                    {
                                        this.state.product.variation_id
                                        ?
                                        this.state.product.variation_id
                                        :
                                        __wprm( 'No variation selected' )
                                    }
                                </p>
                            </FieldContainer>
                            <FieldContainer id="variation-name" label={ __wprm( 'Variation Name' ) }>
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                    <p style={{ margin: 0 }}>
                                        {
                                            this.state.product.variation_name
                                            ?
                                            this.state.product.variation_name
                                            :
                                            __wprm( 'No variation selected' )
                                        }
                                    </p>
                                    {
                                        this.state.product.variation_image_url
                                        &&
                                        <img 
                                            src={ this.state.product.variation_image_url } 
                                            alt={ this.state.product.variation_name }
                                            style={{ maxWidth: '50px', maxHeight: '50px', borderRadius: '4px' }}
                                        />
                                    }
                                </div>
                            </FieldContainer>
                        </div>
                    }
                </div>
                <Footer
                    savingChanges={ this.state.savingChanges }
                >
                    {
                        0 < this.state.product.id
                        &&
                        <button
                            className="button button-secondary button-compact"
                            onClick={ () => {
                                this.setState({
                                    product: emptyProduct,
                                });
                            } }
                        >
                            { __wprm( 'Unset Product' ) }
                        </button>
                    }
                    <button
                        className="button button-primary button-compact"
                        onClick={ this.saveChanges }
                        disabled={ ! this.changesMade() }
                    >
                        { __wprm( 'Save' ) }
                    </button>
                </Footer>
            </Fragment>
        );
    }
}