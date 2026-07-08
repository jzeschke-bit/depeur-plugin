import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import { DragDropContext } from 'react-beautiful-dnd';
import Hashids from 'hashids'

import { __wprm } from 'Shared/Translations';

import Button from '../../../shared/Button';
import ContextMenu from '../general/ContextMenu';
import Loader from '../general/Loader';
import Print from '../general/Print';
import Icon from '../general/Icon';

import GridHeaderActions from './GridHeaderActions';
import ActionsClassic from './ActionsClassic';
import AddItem from './ActionsClassic/AddItem';
import EditItem from './ActionsClassic/EditItem';
import Group from './Group';
import Header from './Header';
import Nutrition from './Nutrition';
import Modal from './Modal';

const HISTORY_LIMIT = 100;

const cloneData = ( data ) => {
    if ( undefined === data ) {
        return undefined;
    }

    return JSON.parse( JSON.stringify( data ) );
};

const sameLocation = ( first, second ) => {
    return !! first
        && !! second
        && first.columnId === second.columnId
        && first.groupId === second.groupId;
};

const sameEditingItem = ( first, second ) => {
    if ( false === first && false === second ) {
        return true;
    }

    if ( ! first || ! second ) {
        return false;
    }

    return first.column === second.column
        && first.group === second.group
        && first.item === second.item;
};

const historyWithEntry = ( history, entry ) => {
    const timestamp = Date.now();
    const nextEntries = history.entries.slice( 0, history.cursor + 1 );
    nextEntries.push( {
        ...entry,
        timestamp,
        uid: `${ timestamp }-${ Math.random() }`,
    } );

    let cursor = nextEntries.length - 1;
    let entries = nextEntries;

    if ( entries.length > HISTORY_LIMIT ) {
        const toRemove = entries.length - HISTORY_LIMIT;
        entries = entries.slice( toRemove );
        cursor -= toRemove;
    }

    return {
        ...history,
        entries,
        cursor,
    };
};

class Collection extends Component {
    constructor(props) {
        super(props);

        // Check how we're edit the structure.
        let editStructureMode = 'icons';
        if ( 'grid' === this.props.layout && 'modal' === wprmprc_public.settings.recipe_collections_appearance_structure_layout && this.props.hasOwnProperty( 'modal' ) && false !== this.props.modal ) {
            editStructureMode = 'modal';
        }

        // Check how we're adding items.
        let addMode = 'column';
        if ( 'grid' === this.props.layout && 'modal' === wprmprc_public.settings.recipe_collections_appearance_adding_layout && this.props.hasOwnProperty( 'modal' ) && false !== this.props.modal ) {
            addMode = 'modal';
        }

        const showNutrition = this.props.collection.showNutrition ? this.props.collection.showNutrition : wprmprc_public.settings.recipe_collections_nutrition_facts_hidden_default;
        this.state = {
            addItems: {
                'collection': [],
                'search': [],
                'ingredient': [],
                'custom': [],
                'note': [],
            },
            mode: 'overview',
            editStructureMode,
            isEditingStructure: false,
            addMode,
            modeOptions: {
                'add-item': {
                    mode: 'admin' === this.props.type || 'inbox' === this.props.type ? 'search' : wprmprc_public.settings.recipe_collections_default_add,
                    group: false,
                    collection: 'inbox',
                    searchRecipe: '',
                    searchIngredient: '',
                },
            },
            editingItem: false,
            editingHeader: false,
            showNutrition, // Only used when displaying saved nutrition.
            isViewingHistory: false,
            history: this.getHistoryStateFromProps( props ),
        }

        this.onPrintCollection = this.onPrintCollection.bind(this);
        this.onPrintRecipes = this.onPrintRecipes.bind(this);
    }

    componentDidUpdate( prevProps, prevState ) {
        const previousCollectionKey = this.getCollectionHistoryKey( prevProps );
        const currentCollectionKey = this.getCollectionHistoryKey( this.props );

        if ( previousCollectionKey !== currentCollectionKey ) {
            this.setState( {
                history: this.getHistoryStateFromProps( this.props ),
            } );
            return;
        }

        const wasEditingItem = false !== prevState.editingItem;
        const isEditingItem = false !== this.state.editingItem;
        const wasEditingStructure = !! prevState.isEditingStructure;
        const isEditingStructure = !! this.state.isEditingStructure;
        const changedEditingTarget = wasEditingItem && isEditingItem && ! sameEditingItem( prevState.editingItem, this.state.editingItem );

        if ( ( wasEditingItem && ! isEditingItem ) || changedEditingTarget ) {
            this.finalizePendingEditSession();
        }

        if ( wasEditingStructure && ! isEditingStructure ) {
            this.finalizePendingDescriptionSession( () => {
                this.finalizePendingStructureNameSession();
            } );
        }

        const hasChangedEditingHeader = prevState.editingHeader !== this.state.editingHeader;
        if ( prevState.editingHeader && hasChangedEditingHeader ) {
            this.finalizePendingStructureNameSession();
        }

        if ( prevState.history !== this.state.history && this.props.onHistoryStateChange && wprmprc_public.settings.recipe_collections_history ) {
            this.props.onHistoryStateChange( this.getHistoryStateForPersistence( this.state.history ) );
        }
    }

    getDefaultHistoryState() {
        return {
            entries: [],
            cursor: -1,
            isReplaying: false,
            pendingEditSession: false,
            pendingDescriptionSession: false,
            pendingStructureNameSession: false,
            initializedAt: Date.now(),
        };
    }

    getHistoryStateFromProps( props = this.props ) {
        if ( props && props.historyState ) {
            return this.normalizeHistoryState( props.historyState );
        }

        return this.getDefaultHistoryState();
    }

    normalizeHistoryState( historyState ) {
        const defaultState = this.getDefaultHistoryState();
        if ( ! historyState || 'object' !== typeof historyState ) {
            return defaultState;
        }

        const entries = Array.isArray( historyState.entries ) ? cloneData( historyState.entries ).slice( -1 * HISTORY_LIMIT ) : [];
        let cursor = parseInt( historyState.cursor );
        cursor = Number.isNaN( cursor ) ? entries.length - 1 : cursor;
        cursor = Math.min( Math.max( cursor, -1 ), entries.length - 1 );

        const initializedAt = historyState.initializedAt ? parseInt( historyState.initializedAt ) : Date.now();

        return {
            entries,
            cursor,
            isReplaying: false,
            pendingEditSession: false,
            pendingDescriptionSession: historyState.pendingDescriptionSession
                ? cloneData( historyState.pendingDescriptionSession )
                : false,
            pendingStructureNameSession: historyState.pendingStructureNameSession
                ? cloneData( historyState.pendingStructureNameSession )
                : false,
            initializedAt: Number.isNaN( initializedAt ) ? Date.now() : initializedAt,
        };
    }

    getHistoryStateForPersistence( historyState = this.state.history ) {
        const normalized = this.normalizeHistoryState( historyState );

        return {
            entries: normalized.entries,
            cursor: normalized.cursor,
            pendingDescriptionSession: normalized.pendingDescriptionSession ? cloneData( normalized.pendingDescriptionSession ) : false,
            pendingStructureNameSession: normalized.pendingStructureNameSession ? cloneData( normalized.pendingStructureNameSession ) : false,
            initializedAt: normalized.initializedAt,
        };
    }

    getStructureRenameSessionKey( type, before = [], after = [] ) {
        if ( before.length !== after.length ) {
            return false;
        }

        const changedIds = [];

        for ( let i = 0; i < before.length; i++ ) {
            const beforeItem = before[ i ] ? before[ i ] : {};
            const afterItem = after[ i ] ? after[ i ] : {};

            if ( beforeItem.id !== afterItem.id ) {
                return false;
            }

            const beforeWithoutName = { ...beforeItem };
            const afterWithoutName = { ...afterItem };
            delete beforeWithoutName.name;
            delete afterWithoutName.name;

            if ( JSON.stringify( beforeWithoutName ) !== JSON.stringify( afterWithoutName ) ) {
                return false;
            }

            const beforeName = beforeItem.name ? beforeItem.name : '';
            const afterName = afterItem.name ? afterItem.name : '';

            if ( beforeName !== afterName ) {
                changedIds.push( afterItem.id );
            }
        }

        if ( ! changedIds.length ) {
            return false;
        }

        if ( 1 === changedIds.length ) {
            return `${ type }-${ changedIds[0] }`;
        }

        // Fallback for atypical cases where multiple names change in one update.
        return `${ type }-multiple`;
    }

    getCollectionHistoryKey( props = this.props ) {
        if ( 'shared' === props.type && props.collection && props.collection.sharedEncoded ) {
            return `${ props.type }-${ props.collection.sharedEncoded }`;
        }

        return `${ props.type }-${ props.collection.id }`;
    }

    getItemDisplayName( item ) {
        if ( ! item || 'object' !== typeof item ) {
            return __wprm( 'Item' );
        }

        const name = item.name && 'string' === typeof item.name ? item.name.trim() : '';
        if ( name ) {
            if ( 'note' === item.type ) {
                const condensed = name.replace( /\s+/g, ' ' );

                if ( 40 < condensed.length ) {
                    return `${condensed.substr( 0, 37 )}...`;
                }

                return condensed;
            }

            return name;
        }

        switch ( item.type ) {
            case 'recipe':
                return __wprm( 'Recipe' );
            case 'note':
                return __wprm( 'Note' );
            case 'ingredient':
                return __wprm( 'Custom Recipe' );
            case 'nutrition-ingredient':
                return __wprm( 'Ingredient' );
            default:
                return __wprm( 'Item' );
        }
    }

    getColumnDisplayName( column ) {
        if ( ! column || 'object' !== typeof column ) {
            return __wprm( 'Column' );
        }

        const name = column.name && 'string' === typeof column.name ? column.name.trim() : '';
        return name ? name : __wprm( 'Column' );
    }

    getGroupDisplayName( group ) {
        if ( ! group || 'object' !== typeof group ) {
            return __wprm( 'Group' );
        }

        const name = group.name && 'string' === typeof group.name ? group.name.trim() : '';
        return name ? name : __wprm( 'Group' );
    }

    getListChangeLabel( type, before = [], after = [] ) {
        const beforeById = new Map( before.map( ( item ) => [ item.id, item ] ) );
        const afterById = new Map( after.map( ( item ) => [ item.id, item ] ) );
        const entityName = 'column' === type ? __wprm( 'column' ) : __wprm( 'group' );
        const entityNamePlural = 'column' === type ? __wprm( 'columns' ) : __wprm( 'groups' );
        const getDisplayName = 'column' === type ? this.getColumnDisplayName.bind( this ) : this.getGroupDisplayName.bind( this );

        const added = after.find( ( item ) => ! beforeById.has( item.id ) );
        if ( added ) {
            return `${ __wprm( 'Added' ) } ${ entityName } "${ getDisplayName( added ) }"`;
        }

        const removed = before.find( ( item ) => ! afterById.has( item.id ) );
        if ( removed ) {
            return `${ __wprm( 'Removed' ) } ${ entityName } "${ getDisplayName( removed ) }"`;
        }

        const renamed = after.find( ( item ) => {
            if ( ! beforeById.has( item.id ) ) {
                return false;
            }

            const beforeName = beforeById.get( item.id ).name ? beforeById.get( item.id ).name : '';
            const afterName = item.name ? item.name : '';

            return beforeName !== afterName;
        } );
        if ( renamed ) {
            return `${ __wprm( 'Changed' ) } ${ entityName } ${ __wprm( 'name to' ) } "${ getDisplayName( renamed ) }"`;
        }

        const beforeOrder = before.map( ( item ) => item.id ).join( ',' );
        const afterOrder = after.map( ( item ) => item.id ).join( ',' );
        if ( beforeOrder !== afterOrder ) {
            return `${ __wprm( 'Reordered' ) } ${ entityNamePlural }`;
        }

        return `${ __wprm( 'Updated' ) } ${ entityNamePlural }`;
    }

    getHistoryEntryLabel( type, item = false, before = false, after = false ) {
        if ( 'edit' === type ) {
            return this.getEditLabel( before, after );
        }

        const itemLabel = this.getItemDisplayName( item );

        switch ( type ) {
            case 'add':
                return `${ __wprm( 'Added' ) } "${ itemLabel }"`;
            case 'duplicate':
                return `${ __wprm( 'Duplicated' ) } "${ itemLabel }"`;
            case 'remove':
                return `${ __wprm( 'Removed' ) } "${ itemLabel }"`;
            case 'move':
                return `${ __wprm( 'Moved' ) } "${ itemLabel }"`;
            default:
                return `${ __wprm( 'Edited' ) } "${ itemLabel }"`;
        }
    }

    getEditLabel( before, after ) {
        const beforeItem = before ? before : {};
        const afterItem = after ? after : {};
        const itemLabel = this.getItemDisplayName( after ? after : before );

        if ( beforeItem.leftovers !== afterItem.leftovers ) {
            return afterItem.leftovers
                ? `${ __wprm( 'Marked recipe as leftovers' ) } ("${ itemLabel }")`
                : `${ __wprm( 'Unmarked leftovers' ) } ("${ itemLabel }")`;
        }

        if ( beforeItem.servings !== afterItem.servings ) {
            return `${ __wprm( 'Changed servings to' ) } ${ afterItem.servings } ("${ itemLabel }")`;
        }

        if ( beforeItem.amount !== afterItem.amount ) {
            return `${ __wprm( 'Changed amount to' ) } ${ afterItem.amount } ("${ itemLabel }")`;
        }

        if ( 'note' === afterItem.type && beforeItem.name !== afterItem.name ) {
            return `${ __wprm( 'Changed note text' ) } ("${ itemLabel }")`;
        }

        const beforeIngredients = beforeItem.ingredients ? beforeItem.ingredients : [];
        const afterIngredients = afterItem.ingredients ? afterItem.ingredients : [];
        if ( 'ingredient' === afterItem.type && JSON.stringify( beforeIngredients ) !== JSON.stringify( afterIngredients ) ) {
            return `${ __wprm( 'Changed ingredients' ) } ("${ itemLabel }")`;
        }

        if ( 'ingredient' === afterItem.type && ( beforeItem.text ? beforeItem.text : '' ) !== ( afterItem.text ? afterItem.text : '' ) ) {
            return `${ __wprm( 'Changed custom recipe text' ) } ("${ itemLabel }")`;
        }

        if ( ( beforeItem.color ? beforeItem.color : '' ) !== ( afterItem.color ? afterItem.color : '' ) ) {
            return `${ __wprm( 'Changed color' ) } ("${ itemLabel }")`;
        }

        return `${ __wprm( 'Edited' ) } "${ itemLabel }"`;
    }

    getLocationKey( location ) {
        return `${ location.columnId }-${ location.groupId }`;
    }

    getLocationFromDroppableId( droppableId ) {
        const [ columnIndex, groupIndex ] = droppableId.split( '-' ).map( ( value ) => parseInt( value ) );
        const column = this.props.collection.columns[ columnIndex ];
        const group = this.props.collection.groups[ groupIndex ];

        if ( ! column || ! group ) {
            return false;
        }

        return {
            columnId: column.id,
            groupId: group.id,
        };
    }

    ensureLocationItems( items, location ) {
        const key = this.getLocationKey( location );

        if ( ! items.hasOwnProperty( key ) || ! Array.isArray( items[ key ] ) ) {
            items[ key ] = [];
        }

        return items[ key ];
    }

    getItemByLocation( items, location, itemId = false, fallbackIndex = false ) {
        const key = this.getLocationKey( location );
        const locationItems = items[ key ];

        if ( ! Array.isArray( locationItems ) ) {
            return false;
        }

        if ( false !== itemId ) {
            const itemIndex = locationItems.findIndex( ( item ) => item && item.id === itemId );
            if ( 0 <= itemIndex ) {
                return {
                    index: itemIndex,
                    item: locationItems[ itemIndex ],
                };
            }
        }

        if ( false !== fallbackIndex && locationItems[ fallbackIndex ] ) {
            return {
                index: fallbackIndex,
                item: locationItems[ fallbackIndex ],
            };
        }

        return false;
    }

    removeItemFromLocation( items, location, itemId = false, fallbackIndex = false ) {
        const key = this.getLocationKey( location );
        const foundItem = this.getItemByLocation( items, location, itemId, fallbackIndex );

        if ( ! foundItem ) {
            return false;
        }

        const removedItem = items[ key ].splice( foundItem.index, 1 )[0];

        return {
            item: removedItem,
            index: foundItem.index,
        };
    }

    insertItemAtLocation( items, location, item, fallbackIndex = false ) {
        const locationItems = this.ensureLocationItems( items, location );
        let insertIndex = fallbackIndex;

        if ( false === insertIndex || insertIndex < 0 || insertIndex > locationItems.length ) {
            insertIndex = locationItems.length;
        }

        locationItems.splice( insertIndex, 0, item );

        return insertIndex;
    }

    replaceItemAtLocation( items, location, itemId = false, fallbackIndex = false, newItem = false ) {
        const key = this.getLocationKey( location );
        const foundItem = this.getItemByLocation( items, location, itemId, fallbackIndex );

        if ( ! foundItem ) {
            return false;
        }

        items[ key ][ foundItem.index ] = newItem;

        return true;
    }

    moveItemInItems( items, source, destination, itemId = false, sourceIndex = false, destinationIndex = false ) {
        const removed = this.removeItemFromLocation( items, source, itemId, sourceIndex );

        if ( ! removed ) {
            return false;
        }

        const insertIndex = this.insertItemAtLocation( items, destination, removed.item, destinationIndex );

        return {
            item: removed.item,
            sourceIndex: removed.index,
            destinationIndex: insertIndex,
        };
    }

    getCollectionStateSnapshot( collection = this.props.collection ) {
        return {
            columns: cloneData( collection && collection.columns ? collection.columns : [] ),
            groups: cloneData( collection && collection.groups ? collection.groups : [] ),
            items: cloneData( collection && collection.items ? collection.items : {} ),
            description: collection && collection.hasOwnProperty( 'description' ) ? collection.description : '',
        };
    }

    cleanItemsForStructure( columns, groups, items ) {
        const safeItems = items && 'object' === typeof items ? items : {};
        const validKeys = new Set();
        const cleanedItems = {};

        for ( let column of columns ) {
            for ( let group of groups ) {
                validKeys.add( `${ column.id }-${ group.id }` );
            }
        }

        for ( let key of Object.keys( safeItems ) ) {
            if ( validKeys.has( key ) ) {
                cleanedItems[ key ] = cloneData( safeItems[ key ] );
            }
        }

        return cleanedItems;
    }

    hasDifferentEntityIds( before = [], after = [] ) {
        if ( before.length !== after.length ) {
            return true;
        }

        const beforeIds = before.map( ( item ) => item.id ).sort().join( ',' );
        const afterIds = after.map( ( item ) => item.id ).sort().join( ',' );

        return beforeIds !== afterIds;
    }

    recordCollectionPatchChange( changes, label, beforePatch, afterPatch ) {
        if ( JSON.stringify( beforePatch ) === JSON.stringify( afterPatch ) ) {
            return;
        }

        this.finalizePendingEditSession( () => {
            this.pushHistoryEntry( {
                type: 'collection',
                label,
                payload: {
                    before: cloneData( beforePatch ),
                    after: cloneData( afterPatch ),
                },
            } );

            this.props.onChangeCollection( this.props.type, this.props.collection.id, changes );
        } );
    }

    startOrUpdatePendingDescriptionSession( beforeDescription, afterDescription ) {
        this.setState( ( prevState ) => {
            if ( prevState.history.isReplaying ) {
                return null;
            }

            const pending = prevState.history.pendingDescriptionSession;
            const nextPending = pending
                ? {
                    before: pending.before,
                    after: afterDescription,
                }
                : {
                    before: beforeDescription,
                    after: afterDescription,
                };

            return {
                history: {
                    ...prevState.history,
                    pendingDescriptionSession: nextPending,
                },
            };
        } );
    }

    finalizePendingDescriptionSession( callback = false ) {
        const pendingDescriptionSession = this.state.history.pendingDescriptionSession;

        if ( ! pendingDescriptionSession ) {
            if ( callback ) {
                callback();
            }
            return;
        }

        this.setState( ( prevState ) => {
            const pending = prevState.history.pendingDescriptionSession;

            if ( ! pending ) {
                return null;
            }

            let history = {
                ...prevState.history,
                pendingDescriptionSession: false,
            };

            if ( pending.before !== pending.after ) {
                history = historyWithEntry( history, {
                    type: 'collection',
                    label: __wprm( 'Changed collection description' ),
                    payload: {
                        before: {
                            description: pending.before,
                        },
                        after: {
                            description: pending.after,
                        },
                    },
                } );
            }

            return {
                history,
            };
        }, () => {
            if ( callback ) {
                callback();
            }
        } );
    }

    startOrUpdatePendingStructureNameSession( key, label, beforePatch, afterPatch ) {
        this.setState( ( prevState ) => {
            if ( prevState.history.isReplaying ) {
                return null;
            }

            let history = {
                ...prevState.history,
            };
            const pending = history.pendingStructureNameSession;

            if ( pending && pending.key === key ) {
                history.pendingStructureNameSession = {
                    ...pending,
                    label,
                    afterPatch: cloneData( afterPatch ),
                };

                return {
                    history,
                };
            }

            if ( pending && JSON.stringify( pending.beforePatch ) !== JSON.stringify( pending.afterPatch ) ) {
                history = historyWithEntry( history, {
                    type: 'collection',
                    label: pending.label,
                    payload: {
                        before: cloneData( pending.beforePatch ),
                        after: cloneData( pending.afterPatch ),
                    },
                } );
            }

            history.pendingStructureNameSession = {
                key,
                label,
                beforePatch: cloneData( beforePatch ),
                afterPatch: cloneData( afterPatch ),
            };

            return {
                history,
            };
        } );
    }

    finalizePendingStructureNameSession( callback = false ) {
        const pendingStructureNameSession = this.state.history.pendingStructureNameSession;

        if ( ! pendingStructureNameSession ) {
            if ( callback ) {
                callback();
            }
            return;
        }

        this.setState( ( prevState ) => {
            const pending = prevState.history.pendingStructureNameSession;

            if ( ! pending ) {
                return null;
            }

            let history = {
                ...prevState.history,
                pendingStructureNameSession: false,
            };

            if ( JSON.stringify( pending.beforePatch ) !== JSON.stringify( pending.afterPatch ) ) {
                history = historyWithEntry( history, {
                    type: 'collection',
                    label: pending.label,
                    payload: {
                        before: cloneData( pending.beforePatch ),
                        after: cloneData( pending.afterPatch ),
                    },
                } );
            }

            return {
                history,
            };
        }, () => {
            if ( callback ) {
                callback();
            }
        } );
    }

    finalizePendingHistorySessions( callback = false ) {
        this.finalizePendingEditSession( () => {
            this.finalizePendingDescriptionSession( () => {
                this.finalizePendingStructureNameSession( callback );
            } );
        } );
    }

    onChangeColumns( columns, label = false ) {
        const beforeColumns = cloneData( this.props.collection.columns );
        const beforeItems = cloneData( this.props.collection.items );
        const nextColumns = cloneData( columns );
        const changes = {
            columns: nextColumns,
        };
        const beforePatch = {
            columns: beforeColumns,
        };
        const afterPatch = {
            columns: nextColumns,
        };

        if ( this.hasDifferentEntityIds( beforeColumns, nextColumns ) ) {
            const nextItems = this.cleanItemsForStructure( nextColumns, this.props.collection.groups, beforeItems );

            if ( JSON.stringify( beforeItems ) !== JSON.stringify( nextItems ) ) {
                changes.items = nextItems;
                beforePatch.items = beforeItems;
                afterPatch.items = nextItems;
            }
        }

        const actionLabel = label ? label : this.getListChangeLabel( 'column', beforeColumns, nextColumns );
        const renameSessionKey = this.state.isEditingStructure ? this.getStructureRenameSessionKey( 'columns', beforeColumns, nextColumns ) : false;
        const isRenameOnly = !! renameSessionKey;

        this.finalizePendingDescriptionSession( () => {
            if ( isRenameOnly ) {
                this.startOrUpdatePendingStructureNameSession( renameSessionKey, actionLabel, beforePatch, afterPatch );
                this.props.onChangeCollection( this.props.type, this.props.collection.id, changes );
                return;
            }

            this.finalizePendingStructureNameSession( () => {
                this.recordCollectionPatchChange( changes, actionLabel, beforePatch, afterPatch );
            } );
        } );
    }

    onChangeGroups( groups, label = false ) {
        const beforeGroups = cloneData( this.props.collection.groups );
        const beforeItems = cloneData( this.props.collection.items );
        const nextGroups = cloneData( groups );
        const changes = {
            groups: nextGroups,
        };
        const beforePatch = {
            groups: beforeGroups,
        };
        const afterPatch = {
            groups: nextGroups,
        };

        if ( this.hasDifferentEntityIds( beforeGroups, nextGroups ) ) {
            const nextItems = this.cleanItemsForStructure( this.props.collection.columns, nextGroups, beforeItems );

            if ( JSON.stringify( beforeItems ) !== JSON.stringify( nextItems ) ) {
                changes.items = nextItems;
                beforePatch.items = beforeItems;
                afterPatch.items = nextItems;
            }
        }

        const actionLabel = label ? label : this.getListChangeLabel( 'group', beforeGroups, nextGroups );
        const renameSessionKey = this.state.isEditingStructure ? this.getStructureRenameSessionKey( 'groups', beforeGroups, nextGroups ) : false;
        const isRenameOnly = !! renameSessionKey;

        this.finalizePendingDescriptionSession( () => {
            if ( isRenameOnly ) {
                this.startOrUpdatePendingStructureNameSession( renameSessionKey, actionLabel, beforePatch, afterPatch );
                this.props.onChangeCollection( this.props.type, this.props.collection.id, changes );
                return;
            }

            this.finalizePendingStructureNameSession( () => {
                this.recordCollectionPatchChange( changes, actionLabel, beforePatch, afterPatch );
            } );
        } );
    }

    onChangeDescription( description ) {
        const beforeDescription = this.props.collection.hasOwnProperty( 'description' ) ? this.props.collection.description : '';
        const afterDescription = description;

        if ( this.state.isEditingStructure ) {
            this.startOrUpdatePendingDescriptionSession( beforeDescription, afterDescription );
            this.props.onChangeCollection( this.props.type, this.props.collection.id, { description: afterDescription } );
            return;
        }

        this.finalizePendingDescriptionSession( () => {
            this.recordCollectionPatchChange(
                {
                    description: afterDescription,
                },
                __wprm( 'Changed collection description' ),
                {
                    description: beforeDescription,
                },
                {
                    description: afterDescription,
                }
            );
        } );
    }

    onClearItems() {
        const beforeItems = cloneData( this.props.collection.items );

        this.finalizePendingDescriptionSession( () => {
            this.recordCollectionPatchChange(
                {
                    items: {},
                },
                __wprm( 'Cleared all items' ),
                {
                    items: beforeItems,
                },
                {
                    items: {},
                }
            );
        } );
    }

    onDuplicateColumn( columnIndex ) {
        const collection = this.props.collection;
        const sourceColumn = collection.columns[ columnIndex ];

        if ( ! sourceColumn ) {
            return;
        }

        const beforeColumns = cloneData( collection.columns );
        const beforeItems = cloneData( collection.items );
        const newColumns = cloneData( collection.columns );

        let maxColumnId = Math.max.apply( Math, newColumns.map( function(item) { return item.id; } ) );
        maxColumnId = maxColumnId < 0 ? -1 : maxColumnId;
        const newColumnId = maxColumnId + 1;

        newColumns.splice( columnIndex + 1, 0, {
            ...cloneData( sourceColumn ),
            id: newColumnId,
        } );

        const newItems = cloneData( collection.items );
        const allItems = Object.values( collection.items ).reduce( ( all, groupItems ) => all.concat( groupItems ), [] );
        let maxItemId = Math.max.apply( Math, allItems.map( function(item) { return item.id; } ) );
        maxItemId = maxItemId < 0 ? -1 : maxItemId;
        let itemId = maxItemId + 1;

        collection.groups.map( ( group ) => {
            const groupItems = [];

            if ( collection.items[ `${ sourceColumn.id }-${ group.id }` ] ) {
                for ( let groupItem of collection.items[ `${ sourceColumn.id }-${ group.id }` ] ) {
                    groupItems.push( {
                        ...groupItem,
                        added: Math.floor( Date.now() / 1000 ),
                        id: itemId,
                    } );
                    itemId++;
                }
            }

            newItems[ `${ newColumnId }-${ group.id }` ] = cloneData( groupItems );
        } );

        this.finalizePendingDescriptionSession( () => {
            this.recordCollectionPatchChange(
                {
                    columns: newColumns,
                    items: newItems,
                },
                `${ __wprm( 'Duplicated column' ) } "${ this.getColumnDisplayName( sourceColumn ) }"`,
                {
                    columns: beforeColumns,
                    items: beforeItems,
                },
                {
                    columns: newColumns,
                    items: newItems,
                }
            );
        } );

        if ( 'modal' !== this.state.editStructureMode ) {
            this.setState( {
                editingHeader: `column-${ newColumnId }`,
            } );
        }
    }

    onGridHeaderChangeCollection( changes ) {
        if ( ! changes || 'object' !== typeof changes ) {
            return;
        }

        if ( changes.hasOwnProperty( 'description' ) ) {
            this.onChangeDescription( changes.description );
            return;
        }

        if ( changes.hasOwnProperty( 'items' ) && Object.keys( changes.items ).length === 0 ) {
            this.onClearItems();
            return;
        }

        this.props.onChangeCollection( this.props.type, this.props.collection.id, changes );
    }

    pushHistoryEntry( entry ) {
        if ( ! wprmprc_public.settings.recipe_collections_history ) {
            return;
        }

        this.setState( ( prevState ) => {
            if ( prevState.history.isReplaying ) {
                return null;
            }

            return {
                history: historyWithEntry( prevState.history, entry ),
            };
        } );
    }

    startOrUpdatePendingEditSession( session ) {
        this.setState( ( prevState ) => {
            if ( prevState.history.isReplaying ) {
                return null;
            }

            let history = {
                ...prevState.history,
            };
            const pending = history.pendingEditSession;

            if ( pending && pending.itemId === session.itemId && sameLocation( pending.location, session.location ) ) {
                history.pendingEditSession = {
                    ...pending,
                    after: cloneData( session.after ),
                    index: session.index,
                };
                return {
                    history,
                };
            }

            if ( pending && JSON.stringify( pending.before ) !== JSON.stringify( pending.after ) ) {
                history = historyWithEntry( history, {
                    type: 'edit',
                    label: this.getHistoryEntryLabel( 'edit', pending.after, pending.before, pending.after ),
                    payload: {
                        itemId: pending.itemId,
                        location: cloneData( pending.location ),
                        index: pending.index,
                        before: cloneData( pending.before ),
                        after: cloneData( pending.after ),
                    },
                } );
            }

            history.pendingEditSession = {
                itemId: session.itemId,
                location: cloneData( session.location ),
                index: session.index,
                before: cloneData( session.before ),
                after: cloneData( session.after ),
            };

            return {
                history,
            };
        } );
    }

    finalizePendingEditSession( callback = false ) {
        const pendingEditSession = this.state.history.pendingEditSession;

        if ( ! pendingEditSession ) {
            if ( callback ) {
                callback();
            }
            return;
        }

        this.setState( ( prevState ) => {
            const pending = prevState.history.pendingEditSession;

            if ( ! pending ) {
                return null;
            }

            let history = {
                ...prevState.history,
                pendingEditSession: false,
            };

            if ( JSON.stringify( pending.before ) !== JSON.stringify( pending.after ) ) {
                history = historyWithEntry( history, {
                    type: 'edit',
                    label: this.getHistoryEntryLabel( 'edit', pending.after, pending.before, pending.after ),
                    payload: {
                        itemId: pending.itemId,
                        location: cloneData( pending.location ),
                        index: pending.index,
                        before: cloneData( pending.before ),
                        after: cloneData( pending.after ),
                    },
                } );
            }

            return {
                history,
            };
        }, () => {
            if ( callback ) {
                callback();
            }
        } );
    }

    applyCollectionPatch( collectionState, patch ) {
        if ( ! patch || 'object' !== typeof patch ) {
            return false;
        }

        let changed = false;
        const allowedKeys = [ 'columns', 'groups', 'items', 'description' ];

        for ( let key of allowedKeys ) {
            if ( patch.hasOwnProperty( key ) ) {
                collectionState[ key ] = cloneData( patch[ key ] );
                changed = true;
            }
        }

        return changed;
    }

    applyHistoryOperation( collectionState, entry, direction = 'redo' ) {
        if ( ! entry || ! entry.payload ) {
            return false;
        }

        const { payload } = entry;
        const items = collectionState.items;

        switch ( entry.type ) {
            case 'add':
            case 'duplicate':
                if ( 'redo' === direction ) {
                    this.insertItemAtLocation( items, payload.destination, cloneData( payload.item ), payload.destination.index );
                    return true;
                }

                return !! this.removeItemFromLocation( items, payload.destination, payload.itemId, payload.destination.index );
            case 'remove':
                if ( 'redo' === direction ) {
                    return !! this.removeItemFromLocation( items, payload.source, payload.itemId, payload.source.index );
                }

                this.insertItemAtLocation( items, payload.source, cloneData( payload.item ), payload.source.index );
                return true;
            case 'move':
                if ( 'redo' === direction ) {
                    return !! this.moveItemInItems( items, payload.source, payload.destination, payload.itemId, payload.source.index, payload.destination.index );
                }

                return !! this.moveItemInItems( items, payload.destination, payload.source, payload.itemId, payload.destination.index, payload.source.index );
            case 'edit':
                if ( 'redo' === direction ) {
                    return this.replaceItemAtLocation( items, payload.location, payload.itemId, payload.index, cloneData( payload.after ) );
                }

                return this.replaceItemAtLocation( items, payload.location, payload.itemId, payload.index, cloneData( payload.before ) );
            case 'collection':
                if ( 'redo' === direction ) {
                    return this.applyCollectionPatch( collectionState, payload.after );
                }

                return this.applyCollectionPatch( collectionState, payload.before );
        }

        return false;
    }

    applyHistoryCursor( cursor ) {
        const history = this.state.history;

        if ( cursor === history.cursor ) {
            return;
        }

        const initialCollectionState = this.getCollectionStateSnapshot();
        const collectionState = this.getCollectionStateSnapshot();
        let appliedCursor = history.cursor;
        let didChange = false;
        let replayFailed = false;

        if ( cursor < history.cursor ) {
            for ( let i = history.cursor; i > cursor; i-- ) {
                if ( this.applyHistoryOperation( collectionState, history.entries[ i ], 'undo' ) ) {
                    appliedCursor = i - 1;
                    didChange = true;
                } else {
                    replayFailed = true;
                    break;
                }
            }
        } else {
            for ( let i = history.cursor + 1; i <= cursor; i++ ) {
                if ( this.applyHistoryOperation( collectionState, history.entries[ i ], 'redo' ) ) {
                    appliedCursor = i;
                    didChange = true;
                } else {
                    replayFailed = true;
                    break;
                }
            }
        }

        // Prevent partial history replays from writing an intermediate, potentially invalid state.
        if ( replayFailed ) {
            return;
        }

        this.setState( ( prevState ) => ( {
            history: {
                ...prevState.history,
                isReplaying: true,
                cursor: appliedCursor,
            },
        } ), () => {
            if ( didChange ) {
                const changes = {};

                if ( JSON.stringify( initialCollectionState.columns ) !== JSON.stringify( collectionState.columns ) ) {
                    changes.columns = collectionState.columns;
                }

                if ( JSON.stringify( initialCollectionState.groups ) !== JSON.stringify( collectionState.groups ) ) {
                    changes.groups = collectionState.groups;
                }

                if ( JSON.stringify( initialCollectionState.items ) !== JSON.stringify( collectionState.items ) ) {
                    changes.items = collectionState.items;
                }

                if ( initialCollectionState.description !== collectionState.description ) {
                    changes.description = collectionState.description;
                }

                if ( Object.keys( changes ).length ) {
                    this.props.onChangeCollection( this.props.type, this.props.collection.id, changes );
                }
            }

            this.setState( ( prevState ) => ( {
                history: {
                    ...prevState.history,
                    isReplaying: false,
                },
            } ) );
        } );
    }

    onHistoryUndo() {
        this.finalizePendingHistorySessions( () => {
            const { cursor } = this.state.history;

            if ( 0 <= cursor ) {
                this.applyHistoryCursor( cursor - 1 );
            }
        } );
    }

    onHistoryRedo() {
        this.finalizePendingHistorySessions( () => {
            const { cursor, entries } = this.state.history;

            if ( cursor < entries.length - 1 ) {
                this.applyHistoryCursor( cursor + 1 );
            }
        } );
    }

    onHistoryJump( cursor ) {
        this.finalizePendingHistorySessions( () => {
            this.applyHistoryCursor( cursor );
        } );
    }

    onHistoryJumpAndClose( cursor ) {
        this.setState( {
            isViewingHistory: false,
        }, () => {
            this.onHistoryJump( cursor );
        } );
    }

    onShowHistory() {
        if ( false === this.props.modal ) {
            return;
        }

        this.finalizePendingHistorySessions( () => {
            this.setState( {
                isViewingHistory: true,
            } );
        });
    }

    onEditItem( item ) {
        if ( ! this.state.editingItem ) {
            return;
        }

        const location = {
            columnId: this.state.editingItem.column,
            groupId: this.state.editingItem.group,
        };
        const key = this.getLocationKey( location );
        let newItems = cloneData( this.props.collection.items );

        if ( ! newItems[ key ] || ! newItems[ key ][ this.state.editingItem.item ] ) {
            return;
        }

        const before = cloneData( newItems[ key ][ this.state.editingItem.item ] );
        const after = cloneData( item );
        newItems[ key ][ this.state.editingItem.item ] = after;

        this.startOrUpdatePendingEditSession( {
            itemId: before.id,
            location,
            index: this.state.editingItem.item,
            before,
            after,
        } );

        this.props.onChangeCollection( this.props.type, this.props.collection.id, { items: newItems } );
    }
    
    onDragEnd(result) {
        if ( result.destination && 'RECIPE' === result.type ) {
            this.finalizePendingEditSession();

            let items = cloneData( this.props.collection.items );
            if ( Object !== items.constructor ) {
                items = {};
            }

            const destination = this.getLocationFromDroppableId( result.destination.droppableId );
            if ( ! destination ) {
                return;
            }
            const requestedDestinationIndex = result.destination.hasOwnProperty( 'index' ) ? result.destination.index : false;

            let item = false;
            let historyEntry = false;
            const isAddAction = 'click' === result.source || ( result.source && 'select-items' === result.source.droppableId );

            if ( isAddAction ) {
                const allItems = Object.values( items ).reduce( (allItems, groupItems) => allItems.concat(groupItems), [] );
                let maxId = Math.max.apply( Math, allItems.map( function(item) { return item.id; } ) );
                maxId = maxId < 0 ? -1 : maxId;

                if ( result.source && 'select-items' === result.source.droppableId ) {
                    const itemId = parseInt( result.draggableId.substr( 7 ) );
                    const selectedItem = this.state.addItems[ this.state.modeOptions['add-item'].mode ].find( ( item ) => itemId === item.id );
                    if ( ! selectedItem ) {
                        return;
                    }
                    item = cloneData( selectedItem );
                } else {
                    item = cloneData( result.item );
                }

                if ( ! item ) {
                    return;
                }

                item.id = maxId + 1;
                const destinationIndex = this.insertItemAtLocation( items, destination, item, requestedDestinationIndex );
                const historyType = 'duplicate' === result.historyType ? 'duplicate' : 'add';

                historyEntry = {
                    type: historyType,
                    label: this.getHistoryEntryLabel( historyType, item ),
                    payload: {
                        itemId: item.id,
                        item: cloneData( item ),
                        destination: {
                            ...destination,
                            index: destinationIndex,
                        },
                    },
                };
            } else {
                const source = this.getLocationFromDroppableId( result.source.droppableId );
                if ( ! source ) {
                    return;
                }

                const sourceIndex = result.source.index;
                if ( sameLocation( source, destination ) && sourceIndex === requestedDestinationIndex ) {
                    return;
                }

                const move = this.moveItemInItems(
                    items,
                    source,
                    destination,
                    false,
                    sourceIndex,
                    requestedDestinationIndex
                );

                if ( ! move || ! move.item ) {
                    return;
                }

                historyEntry = {
                    type: 'move',
                    label: this.getHistoryEntryLabel( 'move', move.item ),
                    payload: {
                        itemId: move.item.id,
                        source: {
                            ...source,
                            index: sourceIndex,
                        },
                        destination: {
                            ...destination,
                            index: move.destinationIndex,
                        },
                    },
                };
            }

            if ( historyEntry ) {
                this.pushHistoryEntry( historyEntry );
            }

            this.props.onChangeCollection( this.props.type, this.props.collection.id, { items } );
        }
    }

    onChangeAddItems(items) {
        // Clean up items and add index.
        items = items.map( (item, index) => {
            item.id = index;
            item.servings = 0 < parseFloat( item.servings ) ? parseFloat( item.servings ) : 1;

            if ( 'recipe' === item.type ) {
                item.recipeId = parseInt( item.recipeId );
            }

            item.added = Math.floor( Date.now() / 1000 );

            return item;
        } );

        this.setState({
            addItems: {
                ...this.state.addItems,
                [this.state.modeOptions['add-item'].mode]: items,
            },
        })
    }

    onChangeModeOptions(options) {
        let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
        modeOptions[ this.state.mode ] = options;

        this.setState({
            modeOptions,
        });
    }

    onDeleteItem( columnId, groupId, index ) {
        this.finalizePendingEditSession();

        const source = {
            columnId,
            groupId,
        };
        let items = cloneData( this.props.collection.items );
        const removed = this.removeItemFromLocation( items, source, false, index );

        if ( ! removed ) {
            return;
        }

        this.pushHistoryEntry( {
            type: 'remove',
            label: this.getHistoryEntryLabel( 'remove', removed.item ),
            payload: {
                itemId: removed.item.id,
                item: cloneData( removed.item ),
                source: {
                    ...source,
                    index: removed.index,
                },
            },
        } );

        this.props.onChangeCollection( this.props.type, this.props.collection.id, { items } );
    }

    onChangeItem( columnId, groupId, index, field, value ) {
        let items = cloneData( this.props.collection.items );
        const location = {
            columnId,
            groupId,
        };
        const key = this.getLocationKey( location );

        if ( items[ key ] && items[ key ][ index ] && 0 <= value ) {
            const before = cloneData( items[ key ][ index ] );
            items[ key ][ index ][ field ] = value;
            const after = cloneData( items[ key ][ index ] );

            if ( this.state.editingItem
                && this.state.editingItem.column === columnId
                && this.state.editingItem.group === groupId
                && this.state.editingItem.item === index
            ) {
                this.startOrUpdatePendingEditSession( {
                    itemId: before.id,
                    location,
                    index,
                    before,
                    after,
                } );
            } else {
                this.finalizePendingEditSession();
                this.pushHistoryEntry( {
                    type: 'edit',
                    label: this.getHistoryEntryLabel( 'edit', after, before, after ),
                    payload: {
                        itemId: before.id,
                        location: cloneData( location ),
                        index,
                        before,
                        after,
                    },
                } );
            }

            this.props.onChangeCollection( this.props.type, this.props.collection.id, { items } );
        }
    }

    onPrintCollection() {
        const printWindow = this.openPrint( 'collection' );
        const collection = this.props.collection;
        const showNutrition = this.showNutrition( collection );

        if ( printWindow ) {
            printWindow.onload = () => {
                printWindow.focus();
                printWindow.WPRMPrint.setArgs({
                    collection: collection,
                    showingNutrition: showNutrition,
                    recipes: this.props.recipes,
                });
            };
        }
    }

    onPrintRecipes() {
        // Get recipes to print.
        const { collection } = this.props;
        let recipesToPrint = [];

        if ( collection.items ) {
            // Follow order of columns and groups.
            for ( let column of collection.columns ) {
                for ( let group of collection.groups ) {
                    const items = collection.items[`${column.id}-${group.id}`] ? collection.items[`${column.id}-${group.id}`] : [];

                    for ( let item of items ) {
                        if ( 'recipe' === item.type && item.recipeId ) {
                            const isLeftovers = wprmprc_public.settings.recipe_collections_items_leftovers && item.hasOwnProperty( 'leftovers' ) && item.leftovers;

                            if ( ! isLeftovers && 0 < item.servings ) {
                                recipesToPrint.push(item.recipeId);
        
                                // Problem: hashids doesn't work with decimals. Pass along as large number.
                                recipesToPrint.push( Math.floor( item.servings * Math.pow( 10, 6 ) ) );
                            }
                        }
                    }
                }
            }
        }

        // Encode recipes to print.
        const hashids = new Hashids( 'wp-recipe-maker' );
        const recipesToPrintEncoded = hashids.encode( recipesToPrint );

        this.openPrint( 'recipes', [ recipesToPrintEncoded ] );
    }

    openPrint( page, args = false ) {
        // Get Print URL.
        const urlParts = wprm_public.home_url.split(/\?(.+)/);
        let printUrl = urlParts[0];

        if ( wprm_public.permalinks ) {
            printUrl += wprm_public.print_slug + '/' + page;

            if ( args ) {
                for ( let arg of args ) {
                    printUrl += '/' + arg;
                }   
            } else {
                printUrl += '/';
            }

            if ( urlParts[1] ) {
                printUrl += '?' + urlParts[1];
            }
        } else {
            printUrl += '?' + wprm_public.print_slug + '=' + page;

            if ( args ) {
                for ( let arg of args ) {
                    printUrl += '$' + arg;
                }
            }

            if ( urlParts[1] ) {
                printUrl += '&' + urlParts[1];
            }
        }

        return window.open( printUrl, '_blank' );
    }

    showNutrition( collection ) {
        // Use value from collection unless displaying a saved/shared collection in frontend.
        let showNutrition = collection.hasOwnProperty( 'showNutrition' ) ? collection.showNutrition : wprmprc_public.settings.recipe_collections_nutrition_facts_hidden_default;
        if ( 'saved' === this.props.type || 'shared' === this.props.type ) {
            showNutrition = this.state.showNutrition;
        }

        return showNutrition;
    }

    render() {
        const { collection } = this.props;
        const isLoading = ! collection.columns || ! collection.groups;
        const showNutrition = this.showNutrition( collection );
        const isAddingItems = false !== this.state.modeOptions['add-item'].group;
        let editingItem = false;

        // Check if we're trying to edit an item.
        if ( false !== this.state.editingItem ) {
            // Check if we can access item.
            if ( collection.items.hasOwnProperty( `${this.state.editingItem.column}-${this.state.editingItem.group}` ) ) {
                if ( collection.items[ `${this.state.editingItem.column}-${this.state.editingItem.group}` ][ this.state.editingItem.item ] ) {
                    editingItem = collection.items[ `${this.state.editingItem.column}-${this.state.editingItem.group}` ][ this.state.editingItem.item ];
                }
            }
        }

        return (
            <Fragment>
                {
                    ( this.state.isViewingHistory || 'modal' === this.state.addMode || 'modal' === this.state.editStructureMode )
                    &&
                    <Modal
                        modal={ this.props.modal }
                        useModalForAdd={ 'modal' === this.state.addMode }
                        useModalForStructure={ 'modal' === this.state.editStructureMode }
                        type={ this.props.type }
                        open={ this.state.isViewingHistory || this.state.isEditingStructure || isAddingItems || false !== editingItem }
                        mode={ this.state.isViewingHistory ? 'history' : ( this.state.isEditingStructure ? 'structure' : ( isAddingItems ? 'add' : 'edit' ) ) }
                        onClose={() => {
                            let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                            modeOptions['add-item'].group = false;

                            this.setState({
                                modeOptions,
                                editingItem: false,
                                isEditingStructure: false,
                                isViewingHistory: false,
                            });
                        }}
                        collections={this.props.collections}
                        collection={this.props.collection}
                        onChangeColumns={ this.onChangeColumns.bind( this ) }
                        onChangeGroups={ this.onChangeGroups.bind( this ) }
                        onChangeDescription={ this.onChangeDescription.bind( this ) }
                        onDuplicateColumn={ this.onDuplicateColumn.bind( this ) }
                        onClearItems={ this.onClearItems.bind( this ) }
                        addItems={this.state.addItems[ this.state.modeOptions['add-item'].mode ]}
                        onChangeAddItems={this.onChangeAddItems.bind(this)}
                        options={this.state.modeOptions['add-item']}
                        onChangeModeOptions={(options) => {
                            let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                            modeOptions['add-item'] = options;

                            this.setState({
                                modeOptions,
                                editingItem: false,
                                isEditingStructure: false,
                            });
                        }}
                        onAddItem={(item) => {
                            // Emulate dragEnd.
                            this.onDragEnd({
                                type: 'RECIPE',
                                destination: {
                                    droppableId: `${this.state.modeOptions['add-item'].group.column}-${this.state.modeOptions['add-item'].group.row}`,
                                },
                                source: 'click',
                                item,
                            });

                            // Cancel adding.
                            let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                            modeOptions['add-item'].group = false;

                            this.setState({
                                modeOptions,
                                editingItem: false,
                                isEditingStructure: false,
                            });
                        }}
                        editingItem={ editingItem }
                        onEdit={ (item) => {
                            this.onEditItem( item );
                        } }
                        historyEntries={ this.state.history.entries }
                        historyCursor={ this.state.history.cursor }
                        historyInitialTimestamp={ this.state.history.initializedAt }
                        onHistoryJump={ this.onHistoryJumpAndClose.bind( this ) }
                    />
                }
                <div className="wprmprc-container-header-container">
                    <div className="wprmprc-container-header">
                        {
                            'saved' !== this.props.type
                            && 'admin' !== this.props.type
                            &&
                            <Fragment>
                                <Button
                                    tag="span"
                                    className="wprmprc-header-link"
                                    onClick={() => {
                                        this.props.history.push(`/`);
                                    }}
                                >{ __wprm( 'Your Collections' ) }{ parseInt( wprmprc_public.user ) !== parseInt( wprmprc_public.collections_user ) ? ` (${ __wprm( 'Editing User' ) } #${ wprmprc_public.collections_user }${ wprmprc_public.collections_user_name ? ` - ${ wprmprc_public.collections_user_name }` : '' })` : ''}</Button>
                                <span className="wprmprc-header-link-separator">&gt;</span>
                            </Fragment>
                        }
                        <span className="wprmprc-container-header-name">{ 'shared' === this.props.type && `${ __wprm( 'Shared Collection:' ) } ` }{collection.name}</span>
                    </div>
                    {
                        'grid' === this.props.layout
                        &&
                        <GridHeaderActions
                            type={ this.props.type }
                            collection={this.props.collection}
                            onEditStructure={() => {
                                this.setState( {
                                    isEditingStructure: true,
                                    isViewingHistory: false,
                                } );
                            }}
                            onChangeCollection={ (changes) => {
                                this.onGridHeaderChangeCollection( changes );
                            }}
                            historyEntries={ this.state.history.entries }
                            historyCursor={ this.state.history.cursor }
                            onHistoryUndo={ this.onHistoryUndo.bind( this ) }
                            onHistoryRedo={ this.onHistoryRedo.bind( this ) }
                            onShowHistory={ this.onShowHistory.bind( this ) }
                            showNutrition={showNutrition}
                            onChangeShowNutrition={(showNutrition) => {
                                // Store value in collection unless displaying a saved collection in frontend.
                                if ( 'saved' === this.props.type || 'shared' === this.props.type ) {
                                    this.setState({
                                        showNutrition,
                                    });
                                } else {
                                    this.props.onChangeCollection(this.props.type, this.props.collection.id, { showNutrition } );
                                }
                            }}
                            openShoppingList={() => {
                                if ( 'inbox' === this.props.type ) {
                                    this.props.history.push(`/shopping-list/inbox/`);
                                } else if ( 'shared' === this.props.type ) {
                                    this.props.history.push(`/shopping-list/share/${this.props.collection.sharedEncoded}`);
                                } else {
                                    this.props.history.push(`/shopping-list/${this.props.type}/${this.props.collection.id}`);
                                }
                            }}
                            onPrintCollection={ this.onPrintCollection }
                            onPrintRecipes={ this.onPrintRecipes }
                        />
                    }
                </div>
                {
                    'grid' === this.props.layout
                    && collection.hasOwnProperty( 'description' ) && '' !== collection.description
                    &&
                    <div className="wprmprc-collection-description" dangerouslySetInnerHTML={ { __html: collection.description } }/>
                }
                {
                    isLoading
                    ?
                    <Loader />
                    :
                    <div className="wprmprc-collection">
                        <DragDropContext
                            onDragEnd={this.onDragEnd.bind(this)}
                        >
                            {
                                collection.columns.map( (column, columnIndex) => {
                                    let itemsInColumn = [];
                                    const isAddingItemsInColumn = 'column' === this.state.addMode && isAddingItems;
                                    const showAddItemsColumn = isAddingItemsInColumn && columnIndex === this.state.modeOptions['add-item'].group.column;

                                    // Only allow editing of item if not also adding somewhere.
                                    let showEditItemsColumn = false;
                                    if ( ! isAddingItemsInColumn && 'column' === this.state.addMode && false !== editingItem ) {
                                        showEditItemsColumn = column.id === this.state.editingItem.column;
                                    }
                                    
                                    // Hide current column if adding/editing in place. Otherwise make transparant.
                                    let columnStyle = {};
                                    if ( isAddingItemsInColumn ) {
                                        columnStyle = showAddItemsColumn ? { display: 'none' } : { opacity: 0.2 };
                                    } else if ( false !== editingItem ) {
                                        columnStyle = showEditItemsColumn ? { display: 'none' } : { opacity: 0.2 };
                                    }

                                    return (
                                        <Fragment key={ columnIndex }>
                                            {
                                                showAddItemsColumn
                                                &&
                                                <div className="wprmprc-collection-column-width wprmprc-collection-actions wprmprc-collection-actions-add-item">
                                                    <Header
                                                        layout={ this.props.layout }
                                                        type="action"
                                                        customAction={ {
                                                            icon: 'close',
                                                            title: __wprm( 'Cancel' ),
                                                            action: () => {
                                                                let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                                                modeOptions['add-item'].group = false;

                                                                this.setState({
                                                                    modeOptions,
                                                                });
                                                            },
                                                        } }
                                                        menu={ false }
                                                    >
                                                        <span>
                                                            <span className="wprmprc-header-link" onClick={ () => {
                                                                let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                                                modeOptions['add-item'].group = false;

                                                                this.setState({
                                                                    modeOptions,
                                                                });
                                                            } }>{
                                                                '' !== column.name
                                                                ?
                                                                column.name
                                                                :
                                                                __wprm( 'Cancel' )
                                                            }</span>
                                                            <span className="wprmprc-header-link-separator">&gt;</span>
                                                            { __wprm( 'Add Item' ) }
                                                        </span>
                                                    </Header>
                                                    <AddItem
                                                        layout={this.props.layout}
                                                        collections={this.props.collections}
                                                        type={this.props.type}
                                                        collection={this.props.collection}
                                                        addItems={this.state.addItems[ this.state.modeOptions['add-item'].mode ]}
                                                        onChangeAddItems={this.onChangeAddItems.bind(this)}
                                                        options={this.state.modeOptions['add-item']}
                                                        onChangeModeOptions={(options) => {
                                                            let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                                            modeOptions['add-item'] = options;

                                                            this.setState({
                                                                modeOptions,
                                                                editingItem: false,
                                                            });
                                                        }}
                                                        onAddItem={(item) => {
                                                            // Emulate dragEnd.
                                                            this.onDragEnd({
                                                                type: 'RECIPE',
                                                                destination: {
                                                                    droppableId: `${this.state.modeOptions['add-item'].group.column}-${this.state.modeOptions['add-item'].group.row}`,
                                                                },
                                                                source: 'click',
                                                                item,
                                                            });

                                                            // Cancel adding.
                                                            let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                                            modeOptions['add-item'].group = false;

                                                            this.setState({
                                                                modeOptions,
                                                                editingItem: false,
                                                            });
                                                        }}
                                                        interface="click"
                                                    />
                                                </div>
                                            }
                                            {
                                                showEditItemsColumn
                                                &&
                                                <div className="wprmprc-collection-column-width wprmprc-collection-actions wprmprc-collection-actions-edit-item">
                                                    <Header
                                                        layout={ this.props.layout }
                                                        type="action"
                                                        customAction={ {
                                                            icon: 'close',
                                                            title: __wprm( 'Go Back' ),
                                                            action: () => {
                                                                this.setState({
                                                                    editingItem: false,
                                                                });
                                                            },
                                                        } }
                                                        menu={ false }
                                                    >
                                                        <span>
                                                            <span className="wprmprc-header-link" onClick={ () => {
                                                                this.setState({
                                                                    editingItem: false,
                                                                });
                                                            } }>{
                                                                '' !== column.name
                                                                ?
                                                                column.name
                                                                :
                                                                __wprm( 'Go Back' )
                                                            }</span>
                                                            <span className="wprmprc-header-link-separator">&gt;</span>
                                                            { __wprm( 'Edit Item' ) }
                                                        </span>
                                                    </Header>
                                                    <EditItem
                                                        layout={ this.props.layout }
                                                        item={ editingItem }
                                                        onEdit={ (item) => {
                                                            this.onEditItem( item );
                                                        } }
                                                    />
                                                </div>
                                            }
                                            <div className="wprmprc-collection-column wprmprc-collection-column-width" style={ columnStyle }>
                                                {
                                                    ( 'grid' === this.props.layout || '' !== column.name )
                                                    &&
                                                    <Header
                                                        layout={ this.props.layout }
                                                        editStructureMode={ this.state.editStructureMode }
                                                        type="column"
                                                        showActions={ 'saved' !== this.props.type && 'shared' !== this.props.type }
                                                        customAction={ 'icons' !== this.state.editStructureMode ? false : {
                                                            icon: 'plus',
                                                            title: __wprm( 'Add Group' ),
                                                            action: () => {
                                                                let newGroups = JSON.parse( JSON.stringify( collection.groups ) );

                                                                let maxId = Math.max.apply( Math, newGroups.map( function(item) { return item.id; } ) );
                                                                maxId = maxId < 0 ? -1 : maxId;

                                                                newGroups.push({ id: maxId + 1, name: '' });

                                                                this.onChangeGroups( newGroups );

                                                                this.setState({
                                                                    editingHeader: `column-${ column.id }-group-${ maxId + 1 }`,
                                                                });
                                                            },
                                                        } }
                                                        menu={ 'icons' !== this.state.editStructureMode ? false : [
                                                            {
                                                                label: __wprm( 'Change Name' ),
                                                                action: () => {
                                                                    this.setState({
                                                                        editingHeader: `column-${ column.id }`,
                                                                    });
                                                                }
                                                            },
                                                            {
                                                                disabled: 0 === columnIndex,
                                                                label: __wprm( 'Move Left' ),
                                                                action: () => {
                                                                    let newColumns = JSON.parse( JSON.stringify( collection.columns ) );

                                                                    const item = newColumns.splice( columnIndex, 1 )[0];
                                                                    newColumns.splice( columnIndex - 1, 0, item );

                                                                    this.onChangeColumns( newColumns, `${ __wprm( 'Moved column left' ) } "${ this.getColumnDisplayName( item ) }"` );
                                                                }
                                                            },
                                                            {
                                                                disabled: collection.columns.length - 1 === columnIndex,
                                                                label: __wprm( 'Move Right' ),
                                                                action: () => {
                                                                    let newColumns = JSON.parse( JSON.stringify( collection.columns ) );

                                                                    const item = newColumns.splice( columnIndex, 1 )[0];
                                                                    newColumns.splice( columnIndex + 1, 0, item );

                                                                    this.onChangeColumns( newColumns, `${ __wprm( 'Moved column right' ) } "${ this.getColumnDisplayName( item ) }"` );
                                                                }
                                                            },
                                                            {
                                                                label: __wprm( 'Duplicate' ),
                                                                action: () => {
                                                                    this.onDuplicateColumn( columnIndex );
                                                                },
                                                            },
                                                            {
                                                                divider: true,
                                                            },
                                                            {
                                                                disabled: 1 === collection.columns.length,
                                                                label: __wprm( 'Delete Column' ),
                                                                confirm: __wprm( 'Are you sure you want to delete?' ),
                                                                action: () => {
                                                                    let newColumns = JSON.parse( JSON.stringify( collection.columns ) );
                                                                    const removedColumn = newColumns[ columnIndex ];
                                                                    newColumns.splice( columnIndex, 1 );
                                                                    this.onChangeColumns( newColumns, `${ __wprm( 'Removed column' ) } "${ this.getColumnDisplayName( removedColumn ) }"` );
                                                                },
                                                            }
                                                        ] }
                                                        name={ column.name }
                                                        onChangeName={ ( name ) => {
                                                            let newColumns = JSON.parse( JSON.stringify( collection.columns ) );
                                                            newColumns[ columnIndex ].name = name;
                                                            this.onChangeColumns( newColumns, `${ __wprm( 'Changed column name to' ) } "${ this.getColumnDisplayName( newColumns[ columnIndex ] ) }"` );
                                                        } }
                                                        editing={ `column-${ column.id }` === this.state.editingHeader }
                                                        onEditing={ ( editing ) => { 
                                                            this.setState({
                                                                editingHeader: editing ? `column-${ column.id }` : false,
                                                            });
                                                        } }
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

                                                            const onDeleteItem = ( ! collection.fixed || 'admin' === this.props.type ) && ( 'grid' === this.props.layout || 'remove-items' === this.state.mode ) ? (id, index) => {
                                                                this.onDeleteItem(column.id, group.id, index);
                                                            } : false;

                                                            return (
                                                                <Group
                                                                    mode={ this.state.mode }
                                                                    editStructureMode={ this.state.editStructureMode }
                                                                    type={ this.props.type }
                                                                    layout={ this.props.layout }
                                                                    collection={collection}
                                                                    group={group}
                                                                    items={groupItems}
                                                                    recipes={this.props.recipes}
                                                                    showNutrition={showNutrition}
                                                                    onDeleteItem={onDeleteItem}
                                                                    onChangeAmount={(index, amount) => {
                                                                        this.onChangeItem( column.id, group.id, index, 'amount', amount );
                                                                    }}
                                                                    onChangeServings={(index, servings) => {
                                                                        this.onChangeItem( column.id, group.id, index, 'servings', servings );
                                                                    }}
                                                                    onChangeLeftovers={(index, leftovers) => {
                                                                        this.onChangeItem( column.id, group.id, index, 'leftovers', leftovers );
                                                                    }}
                                                                    onAddItem={ ( collection.fixed && 'admin' !== this.props.type ) ? false : () => {
                                                                        let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                                                        modeOptions['add-item'].group = {
                                                                            column: columnIndex,
                                                                            row: groupIndex,
                                                                        };

                                                                        this.setState({
                                                                            modeOptions,
                                                                        });
                                                                    }}
                                                                    onDuplicateItem={(itemIndex) => {
                                                                        let duplicatedItem = JSON.parse( JSON.stringify( groupItems[ itemIndex ] ) );
                                                                        duplicatedItem.added = Math.floor( Date.now() / 1000 );

                                                                        // Emulate dragEnd to duplicate item.
                                                                        this.onDragEnd({
                                                                            type: 'RECIPE',
                                                                            destination: {
                                                                                droppableId: `${columnIndex}-${groupIndex}`,
                                                                                index: itemIndex + 1,
                                                                            },
                                                                            source: 'click',
                                                                            item: duplicatedItem,
                                                                            historyType: 'duplicate',
                                                                        });
                                                                    }}
                                                                    onEditItem={(itemIndex) => {
                                                                        this.setState({
                                                                            editingItem: {
                                                                                column: column.id,
                                                                                group: group.id,
                                                                                item: itemIndex,
                                                                            },
                                                                        });
                                                                    }}
                                                                    allowMoveUp={ 0 !== groupIndex }
                                                                    allowMoveDown={ collection.groups.length - 1 !== groupIndex }
                                                                    onMove={(up) => {
                                                                        let newGroups = JSON.parse( JSON.stringify( collection.groups ) );

                                                                        const item = newGroups.splice( groupIndex, 1 )[0];
                                                                        const newIndex = up ? groupIndex - 1 : groupIndex + 1;
                                                                        newGroups.splice( newIndex, 0, item );

                                                                        this.onChangeGroups( newGroups, `${ __wprm( up ? 'Moved group up' : 'Moved group down' ) } "${ this.getGroupDisplayName( item ) }"` );
                                                                    }}
                                                                    onDelete={() => {
                                                                        let newGroups = JSON.parse( JSON.stringify( collection.groups ) );
                                                                        const removedGroup = newGroups[ groupIndex ];
                                                                        newGroups.splice( groupIndex, 1 );
                                                                        this.onChangeGroups( newGroups, `${ __wprm( 'Removed group' ) } "${ this.getGroupDisplayName( removedGroup ) }"` );
                                                                    }}
                                                                    onChangeHeaderName={ ( name ) => {
                                                                        let newGroups = JSON.parse( JSON.stringify( collection.groups ) );
                                                                        newGroups[ groupIndex ].name = name;
                                                                        this.onChangeGroups( newGroups, `${ __wprm( 'Changed group name to' ) } "${ this.getGroupDisplayName( newGroups[ groupIndex ] ) }"` );
                                                                    } }
                                                                    editingHeader={ `column-${ column.id }-group-${ group.id }` === this.state.editingHeader }
                                                                    onEditingHeader={ ( editing ) => {
                                                                        this.setState({
                                                                            editingHeader: editing ? `column-${ column.id }-group-${ group.id }` : false,
                                                                        });
                                                                    } }
                                                                    index={`${columnIndex}-${groupIndex}`}
                                                                    key={`${columnIndex}-${groupIndex}`}
                                                                />
                                                            )
                                                        })
                                                    }
                                                </div>
                                                {
                                                    showNutrition
                                                    &&
                                                    <Nutrition
                                                        items={itemsInColumn}
                                                        recipes={this.props.recipes}
                                                        onUpdateRecipes={this.props.onUpdateRecipes}
                                                    />
                                                }
                                            </div>
                                        </Fragment>
                                    )
                                } )
                            }
                            {
                                'classic' === this.props.layout
                                &&
                                <ActionsClassic
                                    layout={this.props.layout}
                                    collections={this.props.collections}
                                    collection={collection}
                                    type={this.props.type}
                                    mode={this.state.mode}
                                    modeOptions={this.state.modeOptions}
                                    columns={collection.columns}
                                    groups={collection.groups}
                                    addItems={this.state.addItems[ this.state.modeOptions['add-item'].mode ]}
                                    onRemoveAll={ this.onClearItems.bind( this ) }
                                    onChangeColumns={ this.onChangeColumns.bind( this ) }
                                    onChangeGroups={ this.onChangeGroups.bind( this ) }
                                    onChangeAddItems={this.onChangeAddItems.bind(this)}
                                    onChangeMode={(mode) => {
                                        let modeOptions = JSON.parse( JSON.stringify( this.state.modeOptions ) );
                                        modeOptions['add-item'].group = false;

                                        this.setState({
                                            mode,
                                            modeOptions,
                                        });
                                    }}
                                    onChangeModeOptions={this.onChangeModeOptions.bind(this)}
                                    showNutrition={showNutrition}
                                    onChangeShowNutrition={(showNutrition) => {
                                        // Store value in collection unless displaying a saved collection in frontend.
                                        if ( 'saved' === this.props.type || 'shared' === this.props.type) {
                                            this.setState({
                                                showNutrition,
                                            });
                                        } else {
                                            this.props.onChangeCollection(this.props.type, this.props.collection.id, { showNutrition } );
                                        }
                                    }}
                                    onPrint={this.onPrintCollection}
                                    onPrintRecipes={this.onPrintRecipes}
                                />
                            }
                        </DragDropContext>
                        {
                            'grid' === this.props.layout
                            && 'saved' !== this.props.type
                            && 'shared' !== this.props.type
                            && 'icons' === this.state.editStructureMode
                            &&
                            <div className="wprmprc-collection-column-width">
                                <Button
                                    className="wprmprc-action wprmprc-collection-add-column"
                                    onClick={() => {
                                        let newColumns = JSON.parse( JSON.stringify( collection.columns ) );

                                        let maxId = Math.max.apply( Math, newColumns.map( function(item) { return item.id; } ) );
                                        maxId = maxId < 0 ? -1 : maxId;

                                        newColumns.push({ id: maxId + 1, name: '' });

                                        this.onChangeColumns( newColumns );

                                        this.setState({
                                            editingHeader: `column-${ maxId + 1 }`,
                                        });
                                    }}
                                    aria-label={ __wprm( 'Add a column to this collection' ) }
                                >{ __wprm( 'Add Column' ) }</Button>
                            </div>
                        }
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                        <div className="wprmprc-collection-column-balancer wprmprc-collection-column-width"></div>
                    </div>
                }
            </Fragment>
        );
    }
}

export default withRouter(Collection);
