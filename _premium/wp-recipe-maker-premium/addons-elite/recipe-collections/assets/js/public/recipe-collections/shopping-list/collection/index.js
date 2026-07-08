import React, { Fragment } from 'react';

import { __wprm } from 'Shared/Translations';

import Checkbox from '../../general/Checkbox';
import Item from './Item';

const Collection = (props) => {
    const { collection, type, onChangeCollection } = props;

    const allowChange = false !== onChangeCollection;
    const onChangeItem = ( columnId, groupId, index, field, value ) => {
        let items = JSON.parse( JSON.stringify( collection.items ) );
    
        if ( items[`${columnId}-${groupId}`] && items[`${columnId}-${groupId}`][index] && 0 <= value ) {
            items[`${columnId}-${groupId}`][index][field] = value;
    
            onChangeCollection( type, collection.id, { items } );
        }
    }
    const onDeleteItem = ( columnId, groupId, index ) => {
        let items = JSON.parse( JSON.stringify( collection.items ) );
    
        if ( items[`${columnId}-${groupId}`] && items[`${columnId}-${groupId}`][index] ) {
            items[`${columnId}-${groupId}`].splice(index, 1);
    
            onChangeCollection( type, collection.id, { items } );
        }
    }

    if ( 0 === collection.nbrItems ) {
        return null;
    }

    let columnsInShoppingList = 0;
    const nbrColumns = collection.columns.length;

    const SelectAllCheckbox = () => (
        <div className="wprmprc-shopping-list-select-all-container">
            <Checkbox
                checked={ columnsInShoppingList === nbrColumns }
                onChange={ () => {
                    let columns = JSON.parse( JSON.stringify( collection.columns ) );
                    columns.map( ( column, index ) => {
                        columns[index].inShoppingList = columnsInShoppingList === nbrColumns ? false : true;
                    });

                    onChangeCollection( type, collection.id, { columns } );
                } }
                id="wprmprc-shopping-list-select-all"
            />
            <label
                htmlFor="wprmprc-shopping-list-select-all"
            >{ columnsInShoppingList === nbrColumns ? __wprm( 'Deselect all' ) : __wprm( 'Select all' ) }</label>
        </div>
    );

    return (
        <div className="wprmprc-shopping-list-collection">
            <div className="wprmprc-shopping-list-collection-header">
                <div className="wprmprc-shopping-list-collection-name">
                    { __wprm( 'Collection' ) }
                </div>
            </div>
            {
                1 < nbrColumns
                && allowChange
                && <SelectAllCheckbox/>
            }
            {
                collection.columns.map( (column, columnIndex) => {
                    let inShoppingList;

                    if ( column.hasOwnProperty( 'inShoppingList' ) ) {
                        inShoppingList = column.inShoppingList;
                    } else {
                        // Default to false, unless there's only 1 column.
                        inShoppingList = 1 === collection.columns.length;
                    }

                    if ( inShoppingList ) {
                        columnsInShoppingList++;
                    }

                    // Get all items in this collection.
                    let totalCollectionItems = 0;
                    collection.groups.map( (group, groupIndex) => {
                        const groupItems = collection.items[`${column.id}-${group.id}`] ? collection.items[`${column.id}-${group.id}`] : [];
                        totalCollectionItems += groupItems.length;
                    });

                    // Don't show column if there's nothing in it.
                    if ( 0 === totalCollectionItems ) {
                        return null;
                    }

                    return (
                        <div className="wprmprc-shopping-list-column" key={columnIndex}>
                            {
                                ( '' !== column.name || allowChange )
                                && ( inShoppingList || allowChange ) // Show if already in shopping list or it might get added to the shopping list.
                                && 'temp' !== props.type
                                &&
                                <div className="wprmprc-shopping-list-column-header">
                                    {
                                        allowChange
                                        ?
                                        <Fragment>
                                            {
                                                <Checkbox
                                                    checked={ inShoppingList }
                                                    onChange={ ( checked ) => {
                                                        let columns = JSON.parse( JSON.stringify( collection.columns ) );
                                                        columns[ columnIndex ].inShoppingList = checked;
                
                                                        onChangeCollection( type, collection.id, { columns } );
                                                    } }
                                                    id={ `wprmprc-shopping-list-column-${columnIndex}` }
                                                />
                                            }
                                            <label
                                                htmlFor={ `wprmprc-shopping-list-column-${columnIndex}` }
                                                className="wprmprc-shopping-list-column-name"
                                            >{ column.name ? column.name : __wprm( 'Unnamed' ) }</label>
                                        </Fragment>
                                        :
                                        <div className="wprmprc-shopping-list-column-name">{ column.name }</div>
                                    }
                                </div>
                            }
                            {
                                inShoppingList
                                &&
                                <div className="wprmprc-shopping-list-column-items">
                                    {
                                        collection.groups.map( (group, groupIndex) => {
                                            const groupItems = collection.items[`${column.id}-${group.id}`] ? collection.items[`${column.id}-${group.id}`] : [];

                                            return (
                                                <Fragment key={groupIndex}>
                                                    {
                                                        groupItems.map( (item, itemIndex) =>
                                                            <Item
                                                                type={ props.type }
                                                                shoppingList={props.shoppingList}
                                                                item={item}
                                                                allowChange={ allowChange }
                                                                onChangeAmount={(amount) => onChangeItem( column.id, group.id, itemIndex, 'amount', amount )}
                                                                onChangeServings={(servings) => onChangeItem( column.id, group.id, itemIndex, 'servings', servings )}
                                                                onDeleteItem={() => onDeleteItem( column.id, group.id, itemIndex )}
                                                                key={item.id}
                                                            />
                                                        )
                                                    }
                                                </Fragment>
                                            )
                                        })
                                    }
                                </div>
                            }
                        </div>
                    );
                } )
            }
            {
                1 < nbrColumns
                && allowChange
                && <SelectAllCheckbox/>
            }
        </div>
    );
}
export default Collection;