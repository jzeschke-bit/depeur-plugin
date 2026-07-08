window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.favorites = {
    favorites: false,
    readyPromise: false,
    renderTimeout: false,
    getStorageKey() {
        return wprmp_public.favorites.storage_key || 'wprm-recipe-favorites';
    },
    getMergeSessionKey() {
        return wprmp_public.favorites.merge_session_key || `${ window.WPRecipeMaker.favorites.getStorageKey() }-merged`;
    },
    isLoggedIn() {
        return 0 < parseInt( wprmp_public.user );
    },
    init() {
        document.addEventListener( 'click', function(e) {
            for ( var target = e.target; target && target !== this; target = target.parentNode ) {
                if ( target.matches( '.wprm-recipe-favorite[data-recipe-id]' ) ) {
                    window.WPRecipeMaker.favorites.onClickFavorite( target, e );
                    break;
                }
            }
        }, false );

        document.addEventListener( 'wprm-favorites-changed', () => {
            window.WPRecipeMaker.favorites.refreshButtons();
            window.WPRecipeMaker.favorites.scheduleRenderFavoriteLists();
        } );

        window.WPRecipeMaker.favorites.ensureReady().then( () => {
            window.WPRecipeMaker.favorites.refreshButtons();
            window.WPRecipeMaker.favorites.renderFavoriteLists();
        } );
    },
    normalizeFavorites( favorites ) {
        if ( ! Array.isArray( favorites ) ) {
            return [];
        }

        const normalized = [];

        for ( let favorite of favorites ) {
            favorite = parseInt( favorite );

            if ( favorite && ! normalized.includes( favorite ) ) {
                normalized.push( favorite );
            }
        }

        return normalized;
    },
    getLocalFavorites() {
        try {
            const localFavorites = localStorage.getItem( window.WPRecipeMaker.favorites.getStorageKey() );

            if ( localFavorites ) {
                return window.WPRecipeMaker.favorites.normalizeFavorites( JSON.parse( localFavorites ) );
            }
        } catch ( e ) {
            return [];
        }

        return [];
    },
    storeFavorites( favorites ) {
        try {
            localStorage.setItem( window.WPRecipeMaker.favorites.getStorageKey(), JSON.stringify( favorites ) );
        } catch ( e ) {
        }
    },
    hasMergedThisSession() {
        try {
            return '1' === sessionStorage.getItem( window.WPRecipeMaker.favorites.getMergeSessionKey() );
        } catch ( e ) {
            return false;
        }
    },
    markMergedThisSession() {
        try {
            sessionStorage.setItem( window.WPRecipeMaker.favorites.getMergeSessionKey(), '1' );
        } catch ( e ) {
        }
    },
    getFavorites() {
        if ( Array.isArray( window.WPRecipeMaker.favorites.favorites ) ) {
            return window.WPRecipeMaker.favorites.favorites;
        }

        return window.WPRecipeMaker.favorites.getLocalFavorites();
    },
    isFavorited( recipeId ) {
        recipeId = parseInt( recipeId );

        if ( ! recipeId ) {
            return false;
        }

        return window.WPRecipeMaker.favorites.getFavorites().includes( recipeId );
    },
    buildHeaders( hasBody = false ) {
        const headers = {
            'Accept': 'application/json',
        };

        if ( hasBody ) {
            headers['Content-Type'] = 'application/json';
        }

        if ( window.WPRecipeMaker.favorites.isLoggedIn() ) {
            headers['X-WP-Nonce'] = wprm_public.api_nonce;
        }

        return headers;
    },
    request( url, method = 'GET', body = false ) {
        const args = {
            method,
            headers: window.WPRecipeMaker.favorites.buildHeaders( false !== body ),
            credentials: 'same-origin',
        };

        if ( false !== body ) {
            args.body = JSON.stringify( body );
        }

        return fetch( url, args ).then( (response) => {
            if ( response.ok ) {
                return response.json();
            }

            return false;
        }).catch( () => {
            return false;
        } );
    },
    ensureReady() {
        if ( ! window.WPRecipeMaker.favorites.readyPromise ) {
            window.WPRecipeMaker.favorites.readyPromise = window.WPRecipeMaker.favorites.bootstrapFavorites();
        }

        return window.WPRecipeMaker.favorites.readyPromise;
    },
    bootstrapFavorites() {
        return new Promise( async ( resolve ) => {
            let favorites = window.WPRecipeMaker.favorites.getLocalFavorites();
            let mergeFailed = false;

            if ( window.WPRecipeMaker.favorites.isLoggedIn() && favorites.length && ! window.WPRecipeMaker.favorites.hasMergedThisSession() ) {
                const merged = await window.WPRecipeMaker.favorites.request(
                    `${wprmp_public.endpoints.favorites}/merge`,
                    'POST',
                    {
                        favorites,
                    }
                );

                if ( merged && Object.prototype.hasOwnProperty.call( merged, 'favorites' ) ) {
                    favorites = merged.favorites;
                    window.WPRecipeMaker.favorites.markMergedThisSession();
                } else {
                    mergeFailed = true;
                }
            }

            if ( window.WPRecipeMaker.favorites.isLoggedIn() && ! mergeFailed ) {
                const result = await window.WPRecipeMaker.favorites.request( wprmp_public.endpoints.favorites );

                if ( result && Object.prototype.hasOwnProperty.call( result, 'favorites' ) ) {
                    favorites = result.favorites;
                }
            }

            favorites = window.WPRecipeMaker.favorites.normalizeFavorites( favorites );
            window.WPRecipeMaker.favorites.setFavorites( favorites, true );

            resolve( favorites );
        } );
    },
    setFavorites( favorites, silent = false ) {
        window.WPRecipeMaker.favorites.favorites = window.WPRecipeMaker.favorites.normalizeFavorites( favorites );
        window.WPRecipeMaker.favorites.storeFavorites( window.WPRecipeMaker.favorites.favorites );
        window.WPRecipeMaker.favorites.syncLoadedRecipeData();

        if ( ! silent ) {
            document.dispatchEvent( new CustomEvent( 'wprm-favorites-changed', { detail: { favorites: window.WPRecipeMaker.favorites.favorites } } ) );
        }
    },
    syncLoadedRecipeData() {
        if ( ! window.WPRecipeMaker.hasOwnProperty( 'manager' ) || ! window.WPRecipeMaker.manager.hasOwnProperty( 'recipes' ) ) {
            return;
        }

        Object.keys( window.WPRecipeMaker.manager.recipes ).forEach( ( key ) => {
            const recipe = window.WPRecipeMaker.manager.recipes[ key ];

            if ( recipe && recipe.id ) {
                window.WPRecipeMaker.manager.changeRecipeData( recipe.id, {
                    favorite: window.WPRecipeMaker.favorites.isFavorited( recipe.id ),
                } );
            }
        } );
    },
    onClickFavorite( button, e ) {
        e.preventDefault();

        const recipeId = parseInt( button.dataset.recipeId );

        if ( recipeId ) {
            window.WPRecipeMaker.favorites.ensureReady().then( () => {
                window.WPRecipeMaker.favorites.setFavorite( recipeId, ! window.WPRecipeMaker.favorites.isFavorited( recipeId ) );
            } );
        }
    },
    setFavorite( recipeId, favorite ) {
        const previousFavorites = window.WPRecipeMaker.favorites.getFavorites().slice();
        const nextFavorites = previousFavorites.slice();

        if ( favorite ) {
            if ( ! nextFavorites.includes( recipeId ) ) {
                nextFavorites.push( recipeId );
            }
        } else {
            const index = nextFavorites.indexOf( recipeId );

            if ( 0 <= index ) {
                nextFavorites.splice( index, 1 );
            }
        }

        window.WPRecipeMaker.favorites.setFavorites( nextFavorites );

        if ( window.WPRecipeMaker.favorites.isLoggedIn() ) {
            window.WPRecipeMaker.favorites.request(
                `${wprmp_public.endpoints.favorites}/${recipeId}`,
                'POST',
                {
                    favorite,
                }
            ).then( ( result ) => {
                if ( result && Object.prototype.hasOwnProperty.call( result, 'favorites' ) ) {
                    window.WPRecipeMaker.favorites.setFavorites( result.favorites );
                } else {
                    window.WPRecipeMaker.favorites.setFavorites( previousFavorites );
                }
            } );
        }
    },
    refreshButtons( container = document ) {
        const favorites = window.WPRecipeMaker.favorites.getFavorites();
        const buttons = container.querySelectorAll( '.wprm-recipe-favorite-inactive[data-recipe-id], .wprm-recipe-favorite-active[data-recipe-id]' );

        for ( let button of buttons ) {
            const recipeId = parseInt( button.dataset.recipeId );
            const isFavorited = favorites.includes( recipeId );
            const wrapper = button.closest( '.wprm-recipe-favorite-wrapper' );

            if ( button.classList.contains( 'wprm-recipe-favorite-inactive' ) ) {
                button.style.display = isFavorited ? 'none' : '';
            } else if ( button.classList.contains( 'wprm-recipe-favorite-active' ) ) {
                button.style.display = isFavorited ? '' : 'none';
            }

            if ( wrapper ) {
                wrapper.classList.toggle( 'wprm-recipe-favorite-wrapper-active', isFavorited );
            }
        }
    },
    scheduleRenderFavoriteLists() {
        clearTimeout( window.WPRecipeMaker.favorites.renderTimeout );
        window.WPRecipeMaker.favorites.renderTimeout = setTimeout( () => {
            window.WPRecipeMaker.favorites.renderFavoriteLists();
        }, 100 );
    },
    renderFavoriteLists() {
        const containers = document.querySelectorAll( '.wprm-favorite-recipes-container' );

        if ( ! containers.length ) {
            return;
        }

        window.WPRecipeMaker.favorites.ensureReady().then( () => {
            window.WPRecipeMaker.favorites.request(
                `${wprmp_public.endpoints.favorites}/render`,
                'POST',
                {
                    favorites: window.WPRecipeMaker.favorites.getFavorites(),
                }
            ).then( ( result ) => {
                if ( result && Object.prototype.hasOwnProperty.call( result, 'html' ) ) {
                    for ( let container of containers ) {
                        container.innerHTML = result.html;
                        window.WPRecipeMaker.favorites.refreshButtons( container );

                        if ( window.WPRecipeMaker.hasOwnProperty( 'tooltip' ) ) {
                            window.WPRecipeMaker.tooltip.addTooltips( container );
                        }
                    }
                }
            } );
        } );
    },
};

ready(() => {
    window.WPRecipeMaker.favorites.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}
