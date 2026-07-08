const collectionsHelperEndpoint = window.wprmp_public ? wprmp_public.endpoints.collections_helper : '';
const nutritionEndpoint = window.wprmp_public ? wprmp_public.endpoints.nutrition : '';
const debounceTime = 500;

let needsSaveConfirm = window.hasOwnProperty( 'wprmprc_public' ) && parseInt( wprmprc_public.user ) !== parseInt( wprmprc_public.collections_user );

let searchRequest = '';
let searchRequestTimer = null;
let searchPromise = false;

let saveRequest = false;
let saveRequestTimer = null;

let saveShoppingListRequest = false;
let saveShoppingListRequestTimer = null;

// Flush pending saves when page is about to unload.
// This prevents data loss when user refreshes or navigates away too quickly.
const flushPendingSaves = () => {
    // Clear any pending timers.
    if ( saveRequestTimer ) {
        clearTimeout( saveRequestTimer );
        saveRequestTimer = null;
    }
    
    // If there's a pending save request, execute it immediately.
    if ( saveRequest ) {
        const collections = saveRequest;
        saveRequest = false;
        
        const url = `${collectionsHelperEndpoint}/user/${wprmprc_public.collections_user}`;
        const data = JSON.stringify( {
            collections,
        } );
        
        // Use fetch with keepalive for reliable delivery during page unload.
        // This allows custom headers (including nonce) and is supported in modern browsers.
        // The browser will queue and send this request even after the page unloads.
        try {
            fetch( url, {
                method: 'POST',
                headers: apiHeaders,
                credentials: 'same-origin',
                body: data,
                keepalive: true, // Keep request alive even after page unloads.
            } );
        } catch ( e ) {
            // Fallback to sendBeacon if fetch fails (e.g., older browsers).
            // Note: sendBeacon doesn't support custom headers, so nonce won't be sent.
            // This may fail for logged-in users but is better than losing data.
            if ( navigator.sendBeacon ) {
                const blob = new Blob( [ data ], { type: 'application/json' } );
                navigator.sendBeacon( url, blob );
            }
        }
    }
    
    // Also handle shopping list saves if needed.
    if ( saveShoppingListRequestTimer ) {
        clearTimeout( saveShoppingListRequestTimer );
        saveShoppingListRequestTimer = null;
    }
    
    if ( saveShoppingListRequest ) {
        const request = saveShoppingListRequest;
        saveShoppingListRequest = false;
        
        const url = `${collectionsHelperEndpoint}/shopping-list/${request.uid}`;
        const data = JSON.stringify( {
            data: request.data,
        } );
        
        // Use fetch with keepalive for reliable delivery during page unload.
        try {
            fetch( url, {
                method: 'POST',
                headers: apiHeaders,
                credentials: 'same-origin',
                body: data,
                keepalive: true,
            } );
        } catch ( e ) {
            // Fallback to sendBeacon if fetch fails (e.g., older browsers).
            if ( navigator.sendBeacon ) {
                const blob = new Blob( [ data ], { type: 'application/json' } );
                navigator.sendBeacon( url, blob );
            }
        }
    }
};

// Set up event listeners to flush pending saves on page unload.
if ( typeof window !== 'undefined' ) {
    // Use beforeunload to ensure we have time to send the request.
    window.addEventListener( 'beforeunload', flushPendingSaves );
    
    // Also use visibilitychange as a fallback (fires when tab becomes hidden).
    document.addEventListener( 'visibilitychange', () => {
        if ( document.visibilityState === 'hidden' ) {
            flushPendingSaves();
        }
    } );
}

let apiHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    // Don't cache API calls.
    'Cache-Control': 'no-cache, no-store, must-revalidate',
    'Pragma': 'no-cache',
    'Expires': 0,
};

// Only require nonce when logged in (and always in backend) to prevent caching problems for regular visitors.
const apiNonce = window.hasOwnProperty( 'wprm_public' ) ? wprm_public.api_nonce : wprm_admin.api_nonce;

if ( ! window.hasOwnProperty( 'wprmp_public' ) || 0 < parseInt( wprmp_public.user ) ) {
    apiHeaders['X-WP-Nonce'] = apiNonce;
}

export default {
    getCollections() {
        let collections = false;
        let collectionsNeedSave = false;

        // Use collections in local storage.
        const localCollections = localStorage.getItem( 'wprm-recipe-collection' );

        if ( localCollections ) {
            collections = JSON.parse(localCollections);
        }

        // Logged in user with saved collections.
        const savedCollections = wprmprc_public.collections;
        if ( savedCollections ) {
            collections = savedCollections;
        }

        // If logged in user but only localCollections, save these to make sure they are associated with this user now.
        if ( 0 < parseInt( wprmp_public.user ) && localCollections && ! savedCollections ) {
            collectionsNeedSave = true;
        }

        // Use default collections if there are no saved or local ones.
        if ( ! collections ) {
            collections = wprmprc_public.collections_default;
        }

        // Add new temp shopping list feature.
        if ( ! collections.hasOwnProperty( 'temp' ) ) {
            collections.temp = {
                id: 0,
                columns: [{ id: 0, name: '' }],
                groups: [{ id: 0, name: '' }],
                items: {'0-0': []},
                nbrItems: 0,
                created: Math.floor(Date.now() / 1000),
            };
        }

        // Check if someone added a recipe before being logged in.
        const addedBeforeLogin = localStorage.getItem( 'wprm-added-to-collection-before-login' );

        if ( addedBeforeLogin ) {
            const addedBeforeLoginData = JSON.parse( addedBeforeLogin );

            // If it's been less than 30 minutes, add to collection.
            if ( addedBeforeLoginData.timestamp && ( Date.now() - addedBeforeLoginData.timestamp ) < 30 * 60 * 1000 ) {
                let recipe = addedBeforeLoginData.recipe;
                recipe.servings = parseInt( addedBeforeLoginData.servings );

                if ( ! collections[ addedBeforeLoginData.collection ].items.hasOwnProperty( '0-0' ) ) {
                    collections[ addedBeforeLoginData.collection ].items['0-0'] = [];
                }

                collections[ addedBeforeLoginData.collection ].items['0-0'] = [
                    ...collections[ addedBeforeLoginData.collection ].items['0-0'],
                    recipe,
                ];

                collections[ addedBeforeLoginData.collection ].nbrItems++;
                collectionsNeedSave = true;
            }

            localStorage.removeItem( 'wprm-added-to-collection-before-login' );
        }

        // Maybe fix inbox recipes.
        const minColumnId = collections.inbox.columns ? Math.min.apply( Math, collections.inbox.columns.map( function(column) { return column.id; } ) ) : 0;
        const minGroupId = collections.inbox.groups ? Math.min.apply( Math, collections.inbox.groups.map( function(group) { return group.id; } ) ) : 0;

        if ( 0 < minColumnId || 0 < minGroupId ) {
            const inboxColumnGroup = `${minColumnId}-${minGroupId}`;

            // Make sure inbox items exist.
            if ( ! collections.inbox.items.hasOwnProperty( inboxColumnGroup ) ) {
                collections.inbox.items[ inboxColumnGroup ] = [];
            }

            // Move items in default 0-0 group to actual inbox group.
            if ( collections.inbox.items.hasOwnProperty( '0-0' ) && 0 < collections.inbox.items['0-0'].length ) {
                collections.inbox.items[ inboxColumnGroup ] = [
                    ...collections.inbox.items[ inboxColumnGroup ],
                    ...collections.inbox.items[ '0-0' ],
                ];
                collections.inbox.items['0-0'] = [];
            }
        }

        // Push these saved collections.
        const pushedCollections = collections.hasOwnProperty( 'pushedCollections' ) ? collections.pushedCollections : [];

        if ( wprmprc_public.push_collections ) {
            let maxId = Math.max.apply( Math, collections.user.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;

            for ( let pushCollection of wprmprc_public.push_collections ) {
                // If hasn't been pushed before, add now.
                if ( ! pushedCollections.includes( pushCollection.id ) ) {
                    // Store ID to make sure this same collection doesn't get added again.
                    pushedCollections.push( pushCollection.id );

                    // Set new unique ID.
                    maxId++;
                    pushCollection.id = maxId;
                    collections.user.push( pushCollection );

                    // Need to save if we made any changes.
                    collectionsNeedSave = true;
                }
            }
         
            // Update pushedCollections.
            collections.pushedCollections = pushedCollections;
        }

        // Always include fixed collections.
        if ( wprmprc_public.fixed_collections ) {
            let maxId = Math.max.apply( Math, collections.user.map( function(collection) { return collection.id; } ) );
            maxId = maxId < 0 ? -1 : maxId;

            // Track which fixed collections are already present.
            const existingFixedCollectionIds = collections.user
                .filter( collection => collection.fixed && collection.fixedCollectionId )
                .map( collection => collection.fixedCollectionId );

            for ( let fixedCollection of wprmprc_public.fixed_collections ) {
                // If this fixed collection is not already present, add it.
                if ( ! existingFixedCollectionIds.includes( fixedCollection.id ) ) {
                    // Create a copy and mark it as fixed.
                    const fixedCollectionCopy = JSON.parse( JSON.stringify( fixedCollection ) );
                    fixedCollectionCopy.fixed = true;
                    fixedCollectionCopy.fixedCollectionId = fixedCollection.id; // Store original ID for reference.
                    
                    // Set new unique ID for user's collection.
                    maxId++;
                    fixedCollectionCopy.id = maxId;
                    collections.user.push( fixedCollectionCopy );

                    // Need to save if we made any changes.
                    collectionsNeedSave = true;
                } else {
                    // Update existing fixed collection to match the current version.
                    const existingIndex = collections.user.findIndex( 
                        collection => collection.fixed && collection.fixedCollectionId === fixedCollection.id 
                    );
                    if ( existingIndex >= 0 ) {
                        // Update the collection while preserving the user's ID.
                        const existingId = collections.user[ existingIndex ].id;
                        const fixedCollectionCopy = JSON.parse( JSON.stringify( fixedCollection ) );
                        fixedCollectionCopy.fixed = true;
                        fixedCollectionCopy.fixedCollectionId = fixedCollection.id;
                        fixedCollectionCopy.id = existingId;
                        collections.user[ existingIndex ] = fixedCollectionCopy;
                        collectionsNeedSave = true;
                    }
                }
            }

            // Remove any fixed collections that are no longer marked as fixed.
            collections.user = collections.user.filter( collection => {
                if ( collection.fixed && collection.fixedCollectionId ) {
                    const stillFixed = wprmprc_public.fixed_collections.some( 
                        fc => fc.id === collection.fixedCollectionId 
                    );
                    if ( ! stillFixed ) {
                        collectionsNeedSave = true;
                        return false;
                    }
                }
                return true;
            } );
        }

        // Save collections if needed.
        if ( collectionsNeedSave ) {
            this.saveCollections( collections );
        }

        // Check for stale items and refresh them asynchronously.
        // This happens in the background and doesn't block the return.
        // If refresh fails, original collections remain unchanged.
        // Note: Changes trigger an event to update React state, which will handle saving.
        // We don't save directly here to avoid race conditions with user changes.
        this.checkAndRefreshStaleItems( collections ).then( ( refreshedCollections ) => {
            if ( refreshedCollections ) {
                // Trigger custom event to notify React components that collections have been refreshed.
                // This allows components to update their state with the refreshed data.
                // The React component's componentDidUpdate will handle saving, ensuring it always
                // saves the latest state (including any user changes made during refresh).
                if ( typeof window !== 'undefined' && window.dispatchEvent ) {
                    window.dispatchEvent( new CustomEvent( 'wprmprc-collections-refreshed', {
                        detail: { collections: refreshedCollections }
                    } ) );
                }
            }
        } ).catch( ( error ) => {
            // Silently fail - don't break the app if refresh fails.
            // Original collections remain unchanged.
        } );

        // Decouple and return immediately (don't wait for refresh).
        return JSON.parse(JSON.stringify(collections));
    },
    checkAndRefreshStaleItems( collections ) {
        if ( ! collections ) {
            return Promise.resolve( false );
        }

        // Collect all items that need to be checked.
        const itemsToCheck = [];
        const itemLocations = []; // Track where each item is located

        // Helper function to collect items from all collections.
        const collectItems = ( collectionItems, collectionType, collectionId ) => {
            if ( ! collectionItems || typeof collectionItems !== 'object' ) {
                return;
            }

            Object.keys( collectionItems ).forEach( ( columnGroup ) => {
                const items = collectionItems[ columnGroup ];
                if ( Array.isArray( items ) ) {
                    items.forEach( ( item, index ) => {
                        // Only process recipe and nutrition-ingredient types.
                        if ( item && typeof item === 'object' && ( item.type === 'recipe' || item.type === 'nutrition-ingredient' ) ) {
                            // Validate required IDs exist before processing.
                            const hasValidId = ( item.type === 'recipe' && item.recipeId ) || 
                                             ( item.type === 'nutrition-ingredient' && item.ingredientId );
                            
                            if ( ! hasValidId ) {
                                return; // Skip items without required IDs.
                            }

                            // Check if item is potentially stale.
                            // Note: We can't check the current modification time client-side,
                            // so we send items to server which will compare current modification
                            // time vs cachedAt. Server will only return refreshed data if needed.
                            const cachedAt = item.cachedAt || 0;
                            
                            const missingServingsData = item.type === 'recipe'
                                && ( ! item.hasOwnProperty( 'servingsUnitRaw' ) || ! item.hasOwnProperty( 'originalServingsParsed' ) );

                            // Check if item should be sent to server:
                            // - Recipe items missing servings baseline data get checked immediately
                            // - Items without cachedAt (old items) always get checked
                            // - Items with cachedAt only get checked if it's been more than 24 hours
                            const shouldCheck = missingServingsData || ! cachedAt || ( Date.now() / 1000 - cachedAt > 24 * 60 * 60 );

                            if ( shouldCheck ) {
                                itemsToCheck.push( item );
                                itemLocations.push( {
                                    type: collectionType,
                                    id: collectionId,
                                    columnGroup: columnGroup,
                                    index: index,
                                } );
                            }
                        }
                    } );
                }
            } );
        };

        // Check inbox items.
        if ( collections.inbox && collections.inbox.items ) {
            collectItems( collections.inbox.items, 'inbox', 'inbox' );
        }

        // Check temp items.
        if ( collections.temp && collections.temp.items ) {
            collectItems( collections.temp.items, 'temp', 'temp' );
        }

        // Check user collection items.
        if ( collections.user && Array.isArray( collections.user ) ) {
            collections.user.forEach( ( userCollection ) => {
                if ( userCollection.items ) {
                    collectItems( userCollection.items, 'user', userCollection.id );
                }
            } );
        }

        // If no stale items found, nothing to do.
        if ( itemsToCheck.length === 0 ) {
            return Promise.resolve( false );
        }

        // Deduplicate items by recipeId/ingredientId before sending to server.
        // Same recipe/ingredient might appear in multiple locations, but we only need to refresh once.
        const uniqueItemsToCheck = [];
        const itemKeyToOriginalItems = {}; // Map to track which original items map to each unique item

        itemsToCheck.forEach( ( item, index ) => {
            const itemKey = item.type === 'recipe' 
                ? `recipe:${item.recipeId}` 
                : `ingredient:${item.ingredientId}`;

            if ( ! itemKeyToOriginalItems[ itemKey ] ) {
                // First time seeing this recipe/ingredient - add to unique list
                uniqueItemsToCheck.push( item );
                itemKeyToOriginalItems[ itemKey ] = [];
            }
            
            // Track this location for this unique item
            itemKeyToOriginalItems[ itemKey ].push( index );
        } );

        // Refresh unique items only.
        return this.refreshItems( uniqueItemsToCheck ).then( ( refreshedItems ) => {
            if ( ! refreshedItems || refreshedItems.length === 0 ) {
                return false;
            }

            // Update items in collections with refreshed data.
            // Match refreshed items to original items by recipeId/ingredientId.
            // Note: Same recipe/ingredient can appear in multiple locations, so we update ALL matches.
            refreshedItems.forEach( ( refreshedItem ) => {
                // Validate refreshed item has required fields.
                if ( ! refreshedItem || typeof refreshedItem !== 'object' ) {
                    return; // Skip invalid items.
                }

                const hasValidId = ( refreshedItem.type === 'recipe' && refreshedItem.recipeId ) || 
                                 ( refreshedItem.type === 'nutrition-ingredient' && refreshedItem.ingredientId );
                
                if ( ! hasValidId ) {
                    return; // Skip items without required IDs.
                }

                // Find ALL matching locations for this refreshed item using our deduplication map.
                const itemKey = refreshedItem.type === 'recipe' 
                    ? `recipe:${refreshedItem.recipeId}` 
                    : `ingredient:${refreshedItem.ingredientId}`;

                const matchingLocationIndices = itemKeyToOriginalItems[ itemKey ] || [];
                
                if ( matchingLocationIndices.length === 0 ) {
                    return; // Skip if we couldn't find any matches.
                }

                // Get all matching locations for this unique item.
                const matchingLocations = matchingLocationIndices.map( ( index ) => itemLocations[ index ] );

                // Update all matching locations.
                matchingLocations.forEach( ( matchingLocation ) => {

                    let targetCollection = null;

                    // Find the target collection.
                    switch ( matchingLocation.type ) {
                        case 'inbox':
                            if ( collections.inbox && typeof collections.inbox === 'object' ) {
                                targetCollection = collections.inbox;
                            }
                            break;
                        case 'temp':
                            if ( collections.temp && typeof collections.temp === 'object' ) {
                                targetCollection = collections.temp;
                            }
                            break;
                        case 'user':
                            if ( collections.user && Array.isArray( collections.user ) ) {
                                targetCollection = collections.user.find( ( c ) => c && c.id === matchingLocation.id );
                            }
                            break;
                    }

                    if ( targetCollection && 
                        targetCollection.items && 
                        typeof targetCollection.items === 'object' &&
                        targetCollection.items[ matchingLocation.columnGroup ] &&
                        Array.isArray( targetCollection.items[ matchingLocation.columnGroup ] ) ) {
                        
                        // Validate index is within bounds.
                        const itemsArray = targetCollection.items[ matchingLocation.columnGroup ];
                        if ( matchingLocation.index < 0 || matchingLocation.index >= itemsArray.length ) {
                            return; // Skip if index is out of bounds.
                        }

                        // Update the item in place.
                        const originalItem = itemsArray[ matchingLocation.index ];
                        if ( originalItem && typeof originalItem === 'object' ) {
                            // Verify we're updating the correct item by matching IDs.
                            const stillMatches = ( originalItem.type === 'recipe' && 
                                                originalItem.recipeId && 
                                                originalItem.recipeId === refreshedItem.recipeId ) ||
                                            ( originalItem.type === 'nutrition-ingredient' && 
                                                originalItem.ingredientId && 
                                                originalItem.ingredientId === refreshedItem.ingredientId );

                            if ( stillMatches ) {
                                // Merge refreshed data with original item (preserve custom fields like servings, id, etc.).
                                // Preserve all non-standard fields from original item.
                                const preservedFields = {};
                                
                                // Preserve servings if it exists.
                                if ( originalItem.servings !== undefined ) {
                                    preservedFields.servings = originalItem.servings;
                                } else if ( refreshedItem.servings !== undefined ) {
                                    preservedFields.servings = refreshedItem.servings;
                                }
                                
                                // Preserve item id.
                                if ( originalItem.id !== undefined ) {
                                    preservedFields.id = originalItem.id;
                                } else if ( refreshedItem.id !== undefined ) {
                                    preservedFields.id = refreshedItem.id;
                                } else {
                                    preservedFields.id = matchingLocation.index;
                                }

                                // Preserve any other custom fields that might exist.
                                Object.keys( originalItem ).forEach( ( key ) => {
                                    // Don't overwrite standard fields, but preserve custom ones.
                                    const standardFields = [ 'type', 'recipeId', 'ingredientId', 'name', 'image', 'servings', 'servingsUnit',
                                                            'servingsUnitRaw', 'originalServings', 'originalServingsParsed',
                                                            'parent_id', 'parent_url', 'cachedAt', 'modifiedAt', 'amount', 'amountOriginal',
                                                            'unit', 'nutrition', 'id' ];
                                    if ( ! standardFields.includes( key ) && originalItem[ key ] !== undefined ) {
                                        preservedFields[ key ] = originalItem[ key ];
                                    }
                                } );

                                // Update item with merged data.
                                targetCollection.items[ matchingLocation.columnGroup ][ matchingLocation.index ] = {
                                    ...refreshedItem,
                                    ...preservedFields,
                                };
                            }
                        }
                    }
                } ); // End forEach matchingLocations - update all locations where this item appears
            } );

            return collections;
        } ).catch( ( error ) => {
            // Silently fail - don't break the app if refresh fails.
            console.error( 'Failed to refresh stale collection items:', error );
            return false;
        } );
    },
    refreshItems( items ) {
        if ( ! items || items.length === 0 ) {
            return Promise.resolve( [] );
        }

        return fetch( `${collectionsHelperEndpoint}/refresh-items`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify( {
                items: items,
            } ),
        } ).then( ( response ) => {
            return response.json().then( ( json ) => {
                return response.ok ? json : [];
            } );
        } );
    },
    saveCollections(collections) {
        if ( 0 === parseInt( wprmp_public.user ) ) {
            // Not logged in, save in local storage.
            localStorage.setItem( 'wprm-recipe-collection', JSON.stringify( collections ) );
        } else {
            // Confirm save when editing someone else's collection the first time.
            if ( ! needsSaveConfirm || confirm( 'Warning: Are you sure you want to edit the collections of this user? This warning will only show once.' ) ) {
                needsSaveConfirm = false;
                saveRequest = collections;

                clearTimeout(saveRequestTimer);
                saveRequestTimer = setTimeout(() => {
                    this.saveCollectionsDebounced();
                }, debounceTime);
            }
        }

        return collections;
    },
    saveCollectionsDebounced() {
        const collections = saveRequest;
        saveRequest = false;

        if ( collections ) {
            return fetch(`${collectionsHelperEndpoint}/user/${wprmprc_public.collections_user}`, {
                method: 'POST',
                headers: apiHeaders,
                credentials: 'same-origin',
                body: JSON.stringify({
                    collections,
                }),
            });
        }
    },
    getSharedCollection( userId, collectionId ) {
        return fetch(`${collectionsHelperEndpoint}/shared/${userId}/${collectionId}`, {
            method: 'GET',
            headers: apiHeaders,
            credentials: 'same-origin',
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    searchRecipes(search) {
        searchRequest = search;

        clearTimeout(searchRequestTimer);
        searchRequestTimer = setTimeout(() => {
            this.searchRecipesDebounced();
        }, debounceTime);

        if ( searchPromise ) {
            searchPromise(false)
        }

        return new Promise( r => searchPromise = r );
    },
    searchRecipesDebounced() {
        const promise = searchPromise;
        const search = searchRequest;
        searchPromise = false;
        searchRequest = '';

        let url = `${collectionsHelperEndpoint}/recipes`;

        if ( window.hasOwnProperty( 'wprmprc_public' ) ) {
            const wpessid = parseInt( wprmprc_public.settings.recipe_collections_search_recipes_wpessid );

            if ( wpessid ) {
                url += `?wpessid=${wpessid}`;
            }
        }

        return fetch(url, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                search,
            }),
        }).then(response => {
            return response.json().then(json => {
                let result = response.ok ? json : false;
                promise( result );
            });
        });
    },
    searchIngredients(search) {
        searchRequest = search;

        clearTimeout(searchRequestTimer);
        searchRequestTimer = setTimeout(() => {
            this.searchIngredientsDebounced();
        }, debounceTime);

        if ( searchPromise ) {
            searchPromise(false)
        }

        return new Promise( r => searchPromise = r );
    },
    searchIngredientsDebounced() {
        const promise = searchPromise;
        const search = searchRequest;
        searchPromise = false;
        searchRequest = '';

        return fetch(`${collectionsHelperEndpoint}/nutrition-ingredients`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                search,
            }),
        }).then(response => {
            return response.json().then(json => {
                let result = response.ok ? json : false;
                promise( result );
            });
        });
    },
    getRecipe(id) {
        return fetch(`${collectionsHelperEndpoint}/recipe/${id}?t=${ Date.now() }`, {
            method: 'GET',
            headers: apiHeaders,
            credentials: 'same-origin',
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    getRecipeData(id) {
        return fetch(`${collectionsHelperEndpoint}/recipe-data/${id}?t=${ Date.now() }`, {
            method: 'GET',
            headers: apiHeaders,
            credentials: 'same-origin',
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    getIngredients(recipes, ingredients) {
        return fetch(`${collectionsHelperEndpoint}/ingredients`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                recipes,
                ingredients,
            }),
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    getNutrition(recipes) {
        return fetch(`${collectionsHelperEndpoint}/nutrition`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                recipes,
            }),
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    saveCollectionToCollections(collectionId) {
        return fetch(`${collectionsHelperEndpoint}/save`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                collectionId,
            }),
        }).then(response => {
            return response.ok;
        });
    },
    saveSharedCollectionToCollections( userId, collectionId ) {
        return fetch(`${collectionsHelperEndpoint}/save`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                type: 'shared',
                userId,
                collectionId,
            }),
        }).then(response => {
            return response.ok;
        });
    },
    getShoppingList(uid) {
        return fetch(`${collectionsHelperEndpoint}/shopping-list/${uid}?t=${ Date.now() }`, {
            method: 'GET',
            headers: apiHeaders,
            credentials: 'same-origin',
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    saveShoppingList( uid, data ) {
        saveShoppingListRequest = {
            uid,
            data,
        };

        clearTimeout(saveShoppingListRequestTimer);
        saveShoppingListRequestTimer = setTimeout(() => {
            this.saveShoppingListDebounced();
        }, debounceTime);
    },
    saveShoppingListDebounced() {
        const request = saveShoppingListRequest;
        saveShoppingListRequest = false;

        if ( request ) {
            return fetch(`${collectionsHelperEndpoint}/shopping-list/${request.uid}`, {
                method: 'POST',
                headers: apiHeaders,
                credentials: 'same-origin',
                body: JSON.stringify({
                    data: request.data,
                }),
            });
        }
    },
    shopShoppingList( uid, integration ) {
        return fetch(`${collectionsHelperEndpoint}/shop-shopping-list`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                uid,
                integration,
            }),
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    generateShoppingList( type, collection, options ) {
        return fetch(`${collectionsHelperEndpoint}/shopping-list`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                type,
                collection,
                options,
            }),
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
    getCalculated(nutrition) {
        return fetch(`${nutritionEndpoint}/calculated`, {
            method: 'POST',
            headers: apiHeaders,
            credentials: 'same-origin',
            body: JSON.stringify({
                nutrition,
            }),
        }).then(response => {
            return response.json().then(json => {
                return response.ok ? json : false;
            });
        });
    },
};
