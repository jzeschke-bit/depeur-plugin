import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import '../../../css/public/add-to-collection-button.scss';

import { __wprm } from 'Shared/Translations';

const AddToShoppingList = {
    checkTemp: (recipeId) => {
        const localCollections = localStorage.getItem( 'wprm-recipe-collection' );

        if ( localCollections ) {
            const collections = JSON.parse(localCollections);

            if ( collections.hasOwnProperty( 'temp' ) && collections.temp.items.hasOwnProperty( '0-0' ) ) {
                const matches = collections.temp.items['0-0'].filter((item) => item.recipeId === recipeId );
            
                if ( matches.length > 0 ) {
                    AddToShoppingList.updateButtons(recipeId);
                }
            }
        }
    },
    updateButtons: ( recipeId, type = 'add' ) => {
        const buttons = document.querySelectorAll( '.wprm-recipe-add-to-shopping-list' );

        for ( let button of buttons ) {
            if ( recipeId === parseInt( button.dataset.recipeId ) ) {
                if ( button.classList.contains( 'wprm-recipe-not-in-shopping-list' ) ) {
                    button.style.display = 'add' === type ? 'none' : '';
                } else if ( button.classList.contains( 'wprm-recipe-in-shopping-list' ) ) {
                    button.style.display = 'add' === type ? '' : 'none';
                }
            }
        }
    }
}

export default AddToShoppingList;

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.shoppingList = {
	init() {
        // Check local storage for recipe if user is not logged in.
        if ( 0 === parseInt( wprmp_public.user ) ) {
            const buttons = document.querySelectorAll( '.wprm-recipe-add-to-shopping-list.wprm-recipe-not-in-shopping-list' );

            for ( let button of buttons ) {
                const recipeId = parseInt( button.dataset.recipeId );
                AddToShoppingList.checkTemp( recipeId );
            }
        }

        // Check for click.
        document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-recipe-add-to-shopping-list.wprm-recipe-not-in-shopping-list, .wprm-recipe-add-to-shopping-list.wprm-recipe-remove-from-shopping-list' ) ) {
					window.WPRecipeMaker.shoppingList.onClick( target, e );
					break;
				}
			}
		}, false );

        // Optional message to show when not logged in.
        if ( 'logged_in' === wprmp_public.quick_access_shopping_list.access && 0 === parseInt( wprmp_public.user ) && wprmp_public.add_to_collection.not_logged_in_tooltip ) {
            const buttons = document.querySelectorAll( '.wprm-recipe-add-to-shopping-list' );

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
        if ( 'logged_in' === wprmp_public.quick_access_shopping_list.access && 0 === parseInt( wprmp_public.user ) ) {
            if ( 'redirect' === wprmp_public.add_to_collection.not_logged_in ) {
                const redirectUrl = wprmp_public.add_to_collection.not_logged_in_redirect;
                if ( redirectUrl ) {
                    const recipeId = parseInt( el.dataset.recipeId );
                    const recipeServings = WPRecipeMaker.shoppingList.getRecipeServings( recipeId );
                    
                    window.WPRecipeMaker.manager.getRecipe( recipeId ).then( ( recipe ) => {
                        // Temporarily store recipe collection data in local storage.
                        if ( recipe ) {
                            let data = {
                                timestamp: Date.now(),
                                recipe: recipe.data.collection,
                                servings: recipeServings,
                                collection: 'temp',
                            }
                            localStorage.setItem( 'wprm-added-to-collection-before-login', JSON.stringify(data) );
                        }

                        // Redirect.
                        window.location = redirectUrl;
                    } );
                }
            }
        } else {
            if ( el.classList.contains( 'wprm-recipe-remove-from-shopping-list' ) ) {
                WPRecipeMaker.shoppingList.onClickRecipeRemove( el );
            } else {
                WPRecipeMaker.shoppingList.onClickRecipe( el );
            }
        }
    },
    onClickRecipe( el ) {
        const recipeId = parseInt( el.dataset.recipeId );

        const recipes = [{
            id: recipeId,
            servings: WPRecipeMaker.shoppingList.getRecipeServings( recipeId ),
        }];

        WPRecipeMaker.shoppingList.addRecipesToShoppingList( el, recipes );
    },
    getRecipeServings( recipeId ) {
        const servingsContainers = document.querySelectorAll( '.wprm-recipe-servings-' + recipeId );
        let servings = 0 < servingsContainers.length ? servingsContainers[0].dataset.servings : false;
        if ( isNaN( servings ) ) {
            servings = false;
        }

        return servings;
    },
    addRecipesToShoppingList( button, recipes ) {
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
                collection: {
                    id: 'temp',
                },
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
                        AddToShoppingList.updateButtons( recipe.id, 'add' );
                    }
                });
            } else {
                alert( 'Something went wrong. Please try again later.' );
            }
        });
    },
    onClickRecipeRemove( el ) {
        const recipeId = parseInt( el.dataset.recipeId );

        const recipes = [ recipeId ];

        WPRecipeMaker.shoppingList.removeRecipesFromShoppingList( el, recipes );
    },
    removeRecipesFromShoppingList( button, recipes ) {
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

        fetch(`${wprmp_public.endpoints.collections_helper}/remove`, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify({
                localCollections,
                recipes,
                collection: {
                    id: 'temp',
                },
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
                        AddToShoppingList.updateButtons( recipe, 'remove' );
                    }
                });
            } else {
                alert( 'Something went wrong. Please try again later.' );
            }
        });
    },
};

ready(() => {
	window.WPRecipeMaker.shoppingList.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}