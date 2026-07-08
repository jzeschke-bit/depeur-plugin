import React, { Fragment } from 'react';

import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';

import Button from '../../../shared/Button';
import { __wprm } from 'Shared/Translations';

const Header = (props) => {
    const customAction = props.hasOwnProperty( 'customAction' ) ? props.customAction : false;
    const menu = props.hasOwnProperty( 'menu' ) ? props.menu : false;
    const name = props.hasOwnProperty( 'name' ) ? props.name : false;
    const showActions = props.hasOwnProperty( 'showActions' ) ? props.showActions : true;

    return (
        <div
            className={ `wprmprc-collection-header wprmprc-collection-${ props.type }-header` }
            onClick={ () => {
                if ( props.editing ) {
                    props.onEditing( false );
                }
            } }
        >
            {
                false !== name
                ?
                <div className={ `wprmprc-collection-header-name wprmprc-collection-${ props.type }-name` }>
                    {
                        props.editing
                        ?
                        <input
                            type="text"
                            value={ name }
                            onClick={ (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                            } }
                            onChange={ (e) => {
                                props.onChangeName( e.target.value );
                            } }
                            onKeyDown={ (e) => {
                                if( 'Enter' === e.key || 'Escape' === e.key || 'Tab' === e.key ) {
                                    props.onEditing( false );
                                }
                            } }
                            autoFocus
                        />
                        :
                        <Fragment>
                            { name }
                            {
                                ! name
                                && 'grid' === props.layout
                                && props.hasOwnProperty( 'onEditing' )
                                && showActions // Only if the name can actually be edited.
                                && ( ! props.hasOwnProperty( 'editStructureMode' ) || 'icons' === props.editStructureMode )
                                &&
                                <Button
                                    tag="span"
                                    className="wprmprc-collection-header-name-empty"
                                    onClick={() => {
                                        props.onEditing( true );
                                    }}
                                >{ __wprm( 'Click to set name' ) }</Button>
                            }
                            {
                                ! name
                                && 'grid' === props.layout
                                && props.hasOwnProperty( 'editStructureMode' )
                                && 'modal' === props.editStructureMode
                                && 'column' === props.type
                                &&
                                <Fragment>&nbsp;</Fragment>
                            }
                        </Fragment>
                    }
                </div>
                :
                props.children
            }
            {
                'grid' === props.layout
                && showActions
                &&
                <div className={ `wprmprc-collection-header-actions wprmprc-collection-${ props.type }-actions` }>
                    {
                        false !== customAction
                        &&
                        <Button
                            className={ `wprmprc-collection-header-action wprmprc-collection-${ props.type }-action` }
                            onClick={ customAction.action }
                            tabIndex="-1"
                            aria-label={ customAction.title }
                        ><Icon type={ customAction.icon } title={ customAction.title } /></Button>
                    }
                    {
                        false !== menu
                        &&
                        <ContextMenu
                            menu={ menu }
                        />
                    }
                </div>
            }
        </div>
    );
}

export default Header;