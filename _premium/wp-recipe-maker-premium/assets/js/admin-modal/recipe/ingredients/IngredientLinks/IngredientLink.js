import React, { Fragment } from 'react';

import striptags from 'striptags';

import Loader from 'Shared/Loader';
import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';
import FieldDropdown from 'Modal/fields/FieldDropdown';
import FieldText from 'Modal/fields/FieldText';

const IngredientLink = (props) => {
    const { ingredient } = props;

    let link = {
        url: '',
        nofollow: 'default',
        eafl: '',
    };
    let nofollowLabel = '';

    if ( 'global' === props.type || 'edit-global' === props.type ) {
        if ( ingredient.hasOwnProperty('globalLink') && false !== ingredient.globalLink ) {
            link = ingredient.globalLink;

            const nofollowOption = wprm_admin_modal.options.ingredient_link_nofollow.find((option) => option.value === link.nofollow );
            if ( nofollowOption ) {
                nofollowLabel = nofollowOption.label;
            }
        }
    } else {
        if ( ingredient.hasOwnProperty('link') ) {
            link = ingredient.link;
        }
    }

    const usingEafl = window.hasOwnProperty( 'EAFL_Modal' );
    const hasEafl = usingEafl && link && link.eafl;
    const hasLink = link && ( link.url || hasEafl );

    return (
        <div className="wprm-admin-modal-field-ingredient-links-link-container">
            <div className="wprm-admin-modal-field-ingredient-links-link-ingredient">
                { striptags( ingredient.name ) }
                {
                    'edit-global' === props.type
                    && props.hasChanged
                    &&
                    <div className="wprm-admin-modal-field-ingredient-links-link-ingredient-count">
                        {
                            0 < link.count - 1
                            ?
                            `${link.count - 1} ${ __wprm( 'other recipe(s) affected' ) }`
                            :
                            __wprm( 'This can affect other recipes' )
                        }
                    </div>
                }
            </div>
            {
                'global' === props.type
                ?
                <Fragment>
                    {
                        props.isUpdating
                        ?
                        <Loader />
                        :
                        <Fragment>
                            <div
                                className={ `wprm-admin-modal-field-ingredient-links-link-url${ hasLink ? '' : ' wprm-admin-modal-field-ingredient-links-link-url-none'}` }
                            >
                                {
                                    hasEafl
                                    ?
                                <Fragment>{ __wprm( 'Affiliate Link' ) } #{ link.eafl }</Fragment>
                                    :
                                    <Fragment>
                                        { hasLink ? link.url : __wprm( 'No link set' ) }
                                    </Fragment>
                                }
                            </div>
                            <div className="wprm-admin-modal-field-ingredient-links-link-nofollow">{ hasLink && ! hasEafl ? nofollowLabel : '' }</div>
                        </Fragment>
                    }
                </Fragment>
                :
                <Fragment>
                    {
                        usingEafl
                        &&
                        <Fragment>
                            {
                                hasEafl
                                ?
                                <Fragment>
                                    <Icon
                                        type="eafl-link"
                                        title={ __wprm( 'Edit Link' ) }
                                        onClick={() => {
                                            EAFL_Modal.open('edit', { linkId: link.eafl });
                                        }}
                                    />
                                    &nbsp;
                                    <Icon
                                        type="eafl-unlink"
                                        title={ __wprm( 'Remove Link' ) }
                                        onClick={() => {
                                            if( confirm( __wprm( 'Are you sure you want to delete this link?' ) ) ) {
                                                props.onLinkChange( {
                                                    ...link,
                                                    eafl: '',
                                                } );
                                            }
                                        }}
                                    />
                                    &nbsp;{ __wprm( 'Affiliate Link' ) } #{ link.eafl }
                                </Fragment>
                                :
                                <Icon
                                    type="eafl-link"
                                    title={ __wprm( 'Set Affiliate Link' ) }
                                    onClick={() => {
                                        EAFL_Modal.open('insert', {
                                            insertCallback: function(link) {
                                                props.onLinkChange( {
                                                    ...link,
                                                    eafl: link.id,
                                                } );
                                            },
                                            selectedText: ingredient.name,
                                        });
                                    }}
                                />
                            }
                        </Fragment>
                    }
                    {
                        ! hasEafl
                        &&
                        <Fragment>
                            <FieldText
                                name="ingredient-link"
                                type="url"
                                value={ link.url }
                                onChange={ (url) => {
                                    props.onLinkChange( {
                                        ...link,
                                        url,
                                    } );
                                }}
                            />
                            <FieldDropdown
                                options={ wprm_admin_modal.options.ingredient_link_nofollow }
                                value={ link.nofollow }
                                onChange={ (nofollow) => {
                                    props.onLinkChange( {
                                        ...link,
                                        nofollow,
                                    } );
                                }}
                                width={ 200 }
                            />
                        </Fragment>
                    }
                </Fragment>
            }
        </div>
    );
}
export default IngredientLink;