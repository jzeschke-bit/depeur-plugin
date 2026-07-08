import React, { Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import Header from '../recipe-collections/collection/Header';
import ItemContent from '../recipe-collections/collection/Item/ItemContent';
import Nutrition from '../recipe-collections/collection/Nutrition';

const showQR = wprmprc_public.settings.recipe_collections_print_qr_codes;

const Collection = (props) => {
    const { collection } = props;

    return (
        <div className={ `wprm-recipe-collections-layout-${ props.layout }` }>
            <div className="wprmprc-container-header-container">
                    <div className="wprmprc-container-header">
                        <span className="wprmprc-container-header-name">{collection.name}</span>
                    </div>
                </div>
            <div className="wprmprc-collection-description" dangerouslySetInnerHTML={ { __html: collection.description } }/>
            <div className="wprmprc-collection">
                {
                    collection.columns.map( (column, columnIndex) => {
                        let itemsInColumn = [];

                        return (
                            <div className="wprmprc-collection-column wprmprc-collection-column-width" key={ columnIndex }>
                                {
                                    '' !== column.name
                                    &&
                                    <Header
                                        layout={ props.layout }
                                        type="column"
                                        name={ column.name }
                                        
                                    />
                                }
                                <div className="wprmprc-collection-column-groups">
                                    {
                                        collection.groups.map( (group, groupIndex) => {
                                            const groupItems = collection.items[`${column.id}-${group.id}`] ? collection.items[`${column.id}-${group.id}`] : [];
                                            itemsInColumn = [
                                                ...itemsInColumn,
                                                ...groupItems,
                                            ];

                                            if ( ! groupItems.length ) {
                                                return null;
                                            }

                                            return (
                                                <div className="wprmprc-collection-group" key={ groupIndex }>
                                                    {
                                                        '' !== group.name
                                                        &&
                                                        <Header
                                                            layout={ props.layout }
                                                            type="group"
                                                            name={ group.name }
                                                        />
                                                    }
                                                    <div className="wprmprc-collection-group-items">
                                                        {
                                                            groupItems.map( (item, itemIndex) =>
                                                                <ItemContent
                                                                    layout={ props.layout }
                                                                    type={ 'saved' }
                                                                    item={ item }
                                                                    showNutrition={ collection.showNutrition }
                                                                    showQR={ showQR }
                                                                    recipes={ props.recipes }
                                                                    draggable={ false }
                                                                    index={ 0 }
                                                                    key={ itemIndex }
                                                                />
                                                            )
                                                        }
                                                    </div>
                                                </div>
                                            )
                                        })
                                    }
                                </div>
                                {
                                    collection.showNutrition
                                    &&
                                    <Nutrition
                                        items={itemsInColumn}
                                        recipes={props.recipes}
                                        onUpdateRecipes={props.onUpdateRecipes}
                                    />
                                }
                            </div>
                        )
                    } )
                }
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
            </div>
        </div>
    )
}

export default Collection;