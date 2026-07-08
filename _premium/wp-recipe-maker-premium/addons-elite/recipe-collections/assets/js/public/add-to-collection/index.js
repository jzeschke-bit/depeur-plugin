import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import '../../../css/public/add-to-collection-button.scss';

import { __wprm } from 'Shared/Translations';

const AddToCollection = {
    checkInbox: (recipeId) => {
        const localCollections = localStorage.getItem( 'wprm-recipe-collection' );

        if ( localCollections ) {
            const collections = JSON.parse(localCollections);
            const matches = collections.inbox.items['0-0'].filter((item) => item.recipeId === recipeId );
            
            if ( matches.length > 0 ) {
                AddToCollection.updateButtons(recipeId);
            }
        }
    },
    updateButtons: (recipeId) => {
        const buttons = document.querySelectorAll( '.wprm-recipe-add-to-collection' );

        for ( let button of buttons ) {
            if ( recipeId === parseInt( button.dataset.recipeId ) ) {
                if ( button.classList.contains( 'wprm-recipe-not-in-collection' ) ) {
                    button.style.display = 'none';
                } else if ( button.classList.contains( 'wprm-recipe-in-collection' ) ) {
                    button.style.display = '';
                }
            }
        }
    }
}

export default AddToCollection;

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.collections = {
	init() {
        // Check local storage for recipe if user is not logged in.
        if ( 0 === parseInt( wprmp_public.user ) ) {
            const buttons = document.querySelectorAll( '.wprm-recipe-add-to-collection-recipe.wprm-recipe-not-in-collection' );

            for ( let button of buttons ) {
                const recipeId = parseInt( button.dataset.recipeId );
                AddToCollection.checkInbox( recipeId );
            }

            // Add collection options from localStorage.
            if ( 'choose' === wprmp_public.add_to_collection.behaviour ) {
                const localCollections = localStorage.getItem( 'wprm-recipe-collection' );

                if ( localCollections ) {
                    const collections = JSON.parse(localCollections);
                    wprmp_public.add_to_collection.collections.user = collections.user;
                }
            }
        }

        // Check for click.
        document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-recipe-add-to-collection.wprm-recipe-not-in-collection' ) ) {
					window.WPRecipeMaker.collections.onClick( target, e );
					break;
				}
			}
		}, false );

        // Optional message to show when not logged in.
        if ( 'logged_in' === wprmp_public.add_to_collection.access && 0 === parseInt( wprmp_public.user ) && wprmp_public.add_to_collection.not_logged_in_tooltip ) {
            const buttons = document.querySelectorAll( '.wprm-recipe-add-to-collection' );

            for ( let button of buttons ) {
                tippy( button, {
                    theme: 'wprm',
                    content: wprmp_public.add_to_collection.not_logged_in_tooltip,
                });
            }
        }
    },
    onClick( el, e ) {
        e.preventDefault();

        // Only open to logged in users?
        if ( 'logged_in' === wprmp_public.add_to_collection.access && 0 === parseInt( wprmp_public.user ) ) {
            if ( 'redirect' === wprmp_public.add_to_collection.not_logged_in ) {
                const redirectUrl = wprmp_public.add_to_collection.not_logged_in_redirect;

                if ( redirectUrl ) {
                    // For grids, just redirect to the URL.
                    if ( el.classList.contains( 'wprm-recipe-add-to-collection-grid' ) ) {
                        window.location = redirectUrl;
                    } else {
                        const recipeId = parseInt( el.dataset.recipeId );
                        const recipeServings = WPRecipeMaker.collections.getRecipeServings( recipeId );
                        
                        window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
                            // Temporarily store recipe collection data in local storage.
                            if ( recipe ) {
                                let data = {
                                    timestamp: Date.now(),
                                    recipe: recipe.data.collection,
                                    servings: recipeServings,
                                    collection: 'inbox',
                                }
                                localStorage.setItem( 'wprm-added-to-collection-before-login', JSON.stringify(data) );
                            }

                            // Redirect.
                            window.location = redirectUrl;
                        } );
                    }
                }
            }
        } else {
            // The button works, check if recipe or grid type.
            if ( el.classList.contains( 'wprm-recipe-add-to-collection-grid' ) ) {
                WPRecipeMaker.collections.onClickGrid( el );
            } else {
                WPRecipeMaker.collections.onClickRecipe( el );
            }
        }
    },
    onClickGrid( el ) {
        const gridId = el.dataset.gridId;

        if ( window.hasOwnProperty( 'WPUPG_Grids' ) && window.WPUPG_Grids.hasOwnProperty( `wpupg-grid-${ gridId}` ) ) {
            const grid = WPUPG_Grids[ `wpupg-grid-${ gridId}` ];

            if ( grid ) {
                const visibleItems = grid.isotope.getFilteredItemElements();
                let recipes = [];

                for ( let item of visibleItems ) {
                    if ( item.classList.contains( 'wpupg-type-wprm_recipe' ) ) {
                        recipes.push( {
                            id: parseInt( item.dataset.id ),
                        } );
                    }
                }

                if ( 0 < recipes.length ) {
                    // Have user choose or immediately add to specific one.
                    if ( 'choose' === wprmp_public.add_to_collection.behaviour ) {
                        WPRecipeMaker.collections.showCollectionPicker( el, ( collection ) => {
                            WPRecipeMaker.collections.addRecipesToCollection( el, recipes, collection );
                        } );
                    } else {
                        WPRecipeMaker.collections.addRecipesToCollection( el, recipes, { id: 'inbox' } );
                    }
                }
            }
        }
    },
    onClickRecipe( el ) {
        const recipeId = parseInt( el.dataset.recipeId );

        const recipes = [{
            id: recipeId,
            servings: WPRecipeMaker.collections.getRecipeServings( recipeId ),
        }];

        // Have user choose or immediately add to specific one.
        if ( 'choose' === wprmp_public.add_to_collection.behaviour ) {
            WPRecipeMaker.collections.showCollectionPicker( el, ( collection ) => {
                WPRecipeMaker.collections.addRecipesToCollection( el, recipes, collection );
            } );
        } else {
            WPRecipeMaker.collections.addRecipesToCollection( el, recipes, { id: 'inbox' } );
        }
    },
    getRecipeServings( recipeId ) {
        const servingsContainers = document.querySelectorAll( '.wprm-recipe-servings-' + recipeId );
        let servings = 0 < servingsContainers.length ? servingsContainers[0].dataset.servings : false;
        if ( isNaN( servings ) ) {
            servings = false;
        }

        return servings;
    },
    currentTooltip: false,
    showCollectionPicker( el, callback ) {
        // Destroy optional existing instance.
        if ( el.hasOwnProperty( '_tippy' ) ) {
            const wasShowing = el._tippy.state.isShown;
            el._tippy.destroy();

            // If a tooltip was already showing, don't show a new one.
            if ( wasShowing ) { return; }
        }

        tippy( el, {
            theme: 'wprm',
            trigger: 'click',
            showOnCreate: true,
            allowHTML: true,
            interactive: true,
            content(ref) {
                window.WPRecipeMaker.collections.currentTooltip = {
                    ref,
                    callback,
                    collection: '',
                    column: '',
                    group: '',
                };

                let html = '<div class="wprm-add-to-collection-tooltip-container">';
                html += '<select class="wprm-add-to-collection-tooltip" id="wprm-add-to-collection-tooltip-collection" onchange="window.WPRecipeMaker.collections.selectCollection( this )">';
                html += '<option value="">' + __wprm( 'Select a collection' ) + '</option>';
                html += '<option value="inbox">' + wprmp_public.add_to_collection.collections.inbox + '</option>';

                for ( let collection of wprmp_public.add_to_collection.collections.user ) {
                    html += '<option value="user-' + collection.id + '">' + collection.name + '</option>';
                }

                html += '</select>';
                if ( 'choose_column' === wprmp_public.add_to_collection.choice || 'choose_column_group' === wprmp_public.add_to_collection.choice ) {
                    html += '<select class="wprm-add-to-collection-tooltip" id="wprm-add-to-collection-tooltip-column" onchange="window.WPRecipeMaker.collections.selectColumn( this )" disabled="disabled">';
                    html += '<option value="">' + __wprm( 'Select a column' ) + '</option>';
                    html += '</select>';   
                }

                if ( 'choose_column_group' === wprmp_public.add_to_collection.choice ) {
                    html += '<select class="wprm-add-to-collection-tooltip" id="wprm-add-to-collection-tooltip-group" onchange="window.WPRecipeMaker.collections.selectGroup( this )" disabled="disabled">';
                    html += '<option value="">' + __wprm( 'Select a group' ) + '</option>';
                    html += '</select>';   
                }
                html += '</div>';

                return html;
            },
            onShown(instance) {
                instance.popper.addEventListener( 'click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                }, false );
            },
        });
    },
    selectCollection( select ) {
        const value = select.value;

        window.WPRecipeMaker.collections.currentTooltip.collection = value;
        window.WPRecipeMaker.collections.currentTooltip.column = '';
        window.WPRecipeMaker.collections.currentTooltip.group = '';

        if ( 'inbox' === value ) {
            // Inbox doesn't have columns or groups, so selection was made.
            window.WPRecipeMaker.collections.madeTooltipSelection();
        } else {
            window.WPRecipeMaker.collections.updateDropdowns();
        }
    },
    selectColumn( select ) {
        const value = select.value;

        window.WPRecipeMaker.collections.currentTooltip.column = value;
        window.WPRecipeMaker.collections.currentTooltip.group = '';

        // If selecting a column we always need to select a group, so update dropdowns.
        window.WPRecipeMaker.collections.updateDropdowns();
    },
    selectGroup( select ) {
        const value = select.value;

        window.WPRecipeMaker.collections.currentTooltip.group = value;

        // Group is the last selection to be made, so we're finished.
        window.WPRecipeMaker.collections.madeTooltipSelection();
    },
    updateDropdowns() {
        const tooltip = window.WPRecipeMaker.collections.currentTooltip;

        const columnDropdown = document.getElementById( 'wprm-add-to-collection-tooltip-column' );
        const groupDropdown = document.getElementById( 'wprm-add-to-collection-tooltip-group' );

        const collection = window.WPRecipeMaker.collections.getSelectedCollection( tooltip.collection );

        // No column/group selections => update those dropdowns.
        if ( ! tooltip.column ) {
            // Remove existing.
            if ( columnDropdown ) {
                const columnOptions = columnDropdown.querySelectorAll( 'option:not([value=""])' );
                columnOptions.forEach( e => e.parentNode.removeChild( e ) );
            }

            if ( groupDropdown ) {
                const groupOptions = groupDropdown.querySelectorAll( 'option:not([value=""])' );
                groupOptions.forEach( e => e.parentNode.removeChild( e ) );
            }

            // Add options from collection.
            if ( collection ) {
                collection.columns.every( e => {
                    let option = document.createElement( 'option' );
                    option.value = e.id;
                    option.text = e.name;

                    if ( columnDropdown ) {
                        columnDropdown.add( option );
                    } else {
                        window.WPRecipeMaker.collections.currentTooltip.column = e.id;
                        return false; // Stop after first column.
                    }

                    return true;
                });

                collection.groups.every( e => {
                    let option = document.createElement( 'option' );
                    option.value = e.id;
                    option.text = e.name;

                    if ( groupDropdown ) {
                        groupDropdown.add( option );
                    } else {
                        window.WPRecipeMaker.collections.currentTooltip.group = e.id;

                        // Group is the last selection to be made, so we're finished if column is set as well.
                        if ( '' !== window.WPRecipeMaker.collections.currentTooltip.column ) {
                            window.WPRecipeMaker.collections.madeTooltipSelection();
                        }
                        
                        return false; // Stop after first group.
                    }

                    return true;
                });

                // Only 1 column, select immediately.
                if ( 1 === collection.columns.length && columnDropdown ) {
                    columnDropdown.value = collection.columns[0].id;
                    window.WPRecipeMaker.collections.selectColumn( columnDropdown );
                }
            }
        } else {
            // Column already selected.
            // Only 1 group, or no dropdown to be found, select immediately.
            if ( 1 === collection.groups.length && groupDropdown ) {
                groupDropdown.value = collection.groups[0].id;
                window.WPRecipeMaker.collections.selectGroup( groupDropdown );
            } else if ( ! groupDropdown ) {
                window.WPRecipeMaker.collections.madeTooltipSelection();
            }
        }

        // Disable/enable correct dropdowns.
        if ( columnDropdown ) {
            columnDropdown.disabled = ! collection;
        }
        if ( groupDropdown ) {
            groupDropdown.disabled = ! collection || '' === tooltip.column;
        }
    },
    getSelectedCollection( value ) {
        let collection = false;

        if ( 'user-' === value.substring( 0, 5 ) ) {
            const collectionId = parseInt( value.substring( 5 ) );
            const match = wprmp_public.add_to_collection.collections.user.find( ( col ) => col.id === collectionId );

            if ( match ) {
                collection = match;
            }
        }

        return collection;
    },
    madeTooltipSelection() {
        const tooltip = window.WPRecipeMaker.collections.currentTooltip;

        tooltip.ref._tippy.destroy();
        tooltip.callback({
            id: tooltip.collection,
            column: tooltip.column,
            group: tooltip.group,
        });
    },
    addRecipesToCollection( button, recipes, collection ) {
        const localStorageCollections = localStorage.getItem( 'wprm-recipe-collection' );

        let localCollections = false;
        if ( localStorageCollections ) {
            localCollections = JSON.parse( localStorageCollections );
        }

        // Loader text for button, keeping size (which would have been based on the width).
        const currentButtonText = button.innerHTML;
        button.style.width = `${button.offsetWidth}px`;
        button.style.height = `${button.offsetHeight}px`;
        if ( 'inline' === getComputedStyle( button ).display ) {
            button.style.display = 'inline-block';
        }
        button.style.opacity = '0.5';
        button.innerHTML = '...';

        let headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };

        // Only require nonce when logged in to prevent caching problems for regular visitors.
		if ( 0 < parseInt( wprmp_public.user ) ) {
			headers['X-WP-Nonce'] = wprm_public.api_nonce;
		}

        fetch(`${wprmp_public.endpoints.collections_helper}/add`, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify({
                localCollections,
                recipes,
                collection,
            }),
        }).then(function (response) {
            if ( response.ok ) {
                response.json().then( ( result ) => {
                    if ( false === result ) {
                        alert( 'Something went wrong. Please try again later.' );
                    } else if ( true !== result ) {
                        const localCollectionsToStore = result.hasOwnProperty( 'collections' ) ? result.collections : false;

                        if ( localCollectionsToStore ) {
                            localStorage.setItem( 'wprm-recipe-collection', JSON.stringify( localCollectionsToStore ) );
                        }
                    }

                    // Restore button.
                    button.innerHTML = currentButtonText;
                    button.style.width = '';
                    button.style.height = '';
                    button.style.display = '';
                    button.style.opacity = '';

                    // Update add to collection buttons for these recipes.
                    for ( let recipe of recipes ) {
                        AddToCollection.updateButtons( recipe.id );
                    }
                });
            } else {
                alert( 'Something went wrong. Please try again later.' );
            }
        });
    },
};

ready(() => {
	window.WPRecipeMaker.collections.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}