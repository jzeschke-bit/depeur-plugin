import React, { Fragment } from 'react';

import ContextMenu from '../general/ContextMenu';
import Icon from '../general/Icon';
import Button from '../../../shared/Button';

import { __wprm } from 'Shared/Translations';

const GridHeaderActions = (props) => {
    // Starter Templates.
    let starterTemplates = [];
    if ( props.hasStarterTemplates ) {
        for ( let template of wprmprc_public.starter_templates ) {
            starterTemplates.push(
                {
                    label: template.name,
                    action: () => {
                        props.onAdd( 'saved', false, template, 'top' );
                    }
                },
            );
        }
    }

    // Quick Add Collections.
    let quickAddCollections = [];
    if ( props.hasQuickAddCollections ) {
        for ( let template of wprmprc_public.quick_add_collections ) {
            quickAddCollections.push(
                {
                    label: template.name,
                    action: () => {
                        props.onAdd( 'saved', false, template, 'top' );
                    }
                },
            );
        }
    }

    return (
        <div className="wprmprc-container-header-actions">
            <Button
                className="wprmprc-container-header-action wprmprc-container-header-action-add-collection"
                onClick={() => {
                    if ( ! props.hasStarterTemplates ) {
                        props.onAdd( 'user', false, false, 'top' );
                    }
                }}
                isButton={ ! props.hasStarterTemplates }
                aria-label={ __wprm( 'Add a new collection' ) }
            >
                {
                    props.hasStarterTemplates
                    ?
                    <ContextMenu
                        icon={ "plus-thin" }
                        title={ __wprm( 'Add Collection' ) }
                        menu={ [
                            ...starterTemplates,
                            {
                                divider: true,
                            },
                            {
                                label: __wprm( 'Empty Collection' ),
                                action: () => {
                                    props.onAdd( 'user', false, false, 'top' );
                                }
                            }
                        ] }
                    />
                    :
                    <Icon type="plus-thin" title={ __wprm( 'Add Collection' )  } />
                }
            </Button>
            {
                props.hasQuickAddCollections
                &&
                <div
                    className="wprmprc-container-header-action wprmprc-container-header-action-add-premade-collection"
                >
                    <ContextMenu
                        icon={ "calendar-add" }
                        title={ __wprm( 'Add Pre-made Collection' ) }
                        menu={ quickAddCollections }
                    />
                </div>
            }
        </div>
    );
}

export default GridHeaderActions;