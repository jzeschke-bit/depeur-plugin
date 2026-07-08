window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.privateNotes = {
    load: () => {
        // Listen for clicks on container.
		document.addEventListener( 'click', function(e) {
			for ( var target = e.target; target && target != this; target = target.parentNode ) {
				if ( target.matches( '.wprm-private-notes-container:not(.wprm-private-notes-container-disabled)' ) ) {
					window.WPRecipeMaker.privateNotes.click( target, e );
					break;
				}
			}
        }, false );

        // Init.
        window.WPRecipeMaker.privateNotes.init();
    },
    init: () => {
        // Show notes if there are any.
        const containers = document.querySelectorAll( '.wprm-private-notes-container' );

        for ( let container of containers ) {
            window.WPRecipeMaker.privateNotes.showNotes( container );
        }
    },
    currentlyEditing: false,
    click: ( el, e ) => {
        e.preventDefault();
        window.WPRecipeMaker.privateNotes.startEditing( el );
    },
    startEditing: ( container ) => {
        const recipeId = parseInt( container.dataset.recipe );

        if ( recipeId && ! window.WPRecipeMaker.privateNotes.currentlyEditing ) {
            window.WPRecipeMaker.privateNotes.currentlyEditing = true;
            container.classList.add( 'wprm-private-notes-editing' );

            const input = container.querySelector( 'textarea' );

            // Set initial height.
            const height = input.scrollHeight < 100 ? 100 : input.scrollHeight;
            input.style.height = height + 'px';
            
            // Save if closing window during edit.
            window.onbeforeunload = () => {
                window.WPRecipeMaker.privateNotes.stopEditing( container );
            }

            // Add event listeners.
            input.addEventListener( 'blur', () => {
                window.WPRecipeMaker.privateNotes.stopEditing( container );
            }, false );
            input.addEventListener( 'input', () => {            
                const height = input.scrollHeight < 100 ? 100 : input.scrollHeight;
                input.style.height = height + 'px';
            }, false );
            
            // Put cursor at end of textarea.
            input.focus();
            input.setSelectionRange( input.value.length, input.value.length );
        }
    },
    stopEditing: ( container ) => {
        window.WPRecipeMaker.privateNotes.currentlyEditing = false;

        const input = container.querySelector( 'textarea' );
        const notes = input.value.trim();

        // Clone to make sure eventListener is removed.
        const inputClone = input.cloneNode( true );
        input.parentNode.replaceChild( inputClone, input );

        // Stop editing container.
        container.classList.remove( 'wprm-private-notes-editing' );

        // Save notes.
        const recipeId = parseInt( container.dataset.recipe );
        window.WPRecipeMaker.privateNotes.saveNotes( recipeId, notes );
        
        // Display notes.
        window.WPRecipeMaker.privateNotes.showNotes( container );
    },
    showNotes: ( container ) => {
        const recipeId = parseInt( container.dataset.recipe );

        if ( recipeId ) {
            const notes = window.WPRecipeMaker.privateNotes.getNotes( recipeId, container );

            // Make sure input has the same value.
            const input = container.querySelector( 'textarea' );
            if ( input ) {
                input.value = notes;
            }

            // Set correct class for container.
            if ( notes ) {
                container.classList.add( 'wprm-private-notes-has-notes' );
            } else {
                container.classList.remove( 'wprm-private-notes-has-notes' );
            }
    
            // Display notes.
            const userNotes = container.querySelector( '.wprm-private-notes-user' );
            if ( userNotes ) {
                userNotes.innerHTML = notes;
            }
        }
    },
    recipeNotes: {},
    getNotes: ( recipeId, container = false ) => {
        let notes = '';

        // Already loaded these? Return value.
        if ( window.WPRecipeMaker.privateNotes.recipeNotes.hasOwnProperty( `recipe-${ recipeId }` ) ) {
            notes = window.WPRecipeMaker.privateNotes.recipeNotes[ `recipe-${ recipeId }` ];
            return notes;
        }

        // Check first if value already set.
        if ( container ) {
            const input = container.querySelector( 'textarea' );

            if ( input ) {
                notes = input.value;
            }
        }
 
        // Still no notes found? Check localStorage.
        if ( ! notes ) {
            const localRecipeNotes = localStorage.getItem( `wprm-recipe-private-notes-${ recipeId }` );

            if ( localRecipeNotes ) {
                notes = localRecipeNotes;
            }
        }

        return notes;
    },
    saveNotes: ( recipeId, notes ) => {
        if ( ! recipeId ) {
            return;
        }

        // Store for this session.
        window.WPRecipeMaker.privateNotes.recipeNotes[ `recipe-${ recipeId }` ] = notes;

        // If logged in, save in database.
        if ( 0 < parseInt( wprmp_public.user ) ) {
            fetch(`${wprmp_public.endpoints.private_notes}/${recipeId}`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': wprm_public.api_nonce,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    notes,
                }),
            }).then( (response) => {
                if ( response.ok ) {
                    return response.json();
                }
                return false;
            }).then( ( result ) => {
                if ( false === result ) {
                    // Store in localStorage if saving failed.
                    window.WPRecipeMaker.privateNotes.saveNotesInLocalStorage( recipeId, notes );
                } else {
                    // Saved in DB, force a removal of localStorage to prevent conflict.
                    window.WPRecipeMaker.privateNotes.saveNotesInLocalStorage( recipeId, false );
                }
            });
        } else {
            // Not logged in, save in localStorage.
            window.WPRecipeMaker.privateNotes.saveNotesInLocalStorage( recipeId, notes );
        }
    },
    saveNotesInLocalStorage: ( recipeId, notes ) => {
        if ( notes ) {
            localStorage.setItem( `wprm-recipe-private-notes-${ recipeId }`, notes );
        } else {
            localStorage.removeItem( `wprm-recipe-private-notes-${ recipeId }` );
        }
    },
    hideEmpty: () => {
        const containers = document.querySelectorAll( '.wprm-private-notes-container' );

        for ( let container of containers ) {
            const recipeId = parseInt( container.dataset.recipe );

            if ( recipeId ) {
                const notes = window.WPRecipeMaker.privateNotes.getNotes( recipeId, container );

                if ( ! notes ) {
                    const potentialHeader = container.previousElementSibling;

                    if ( potentialHeader && potentialHeader.classList.contains( 'wprm-recipe-private-notes-header' ) ) {
                        potentialHeader.remove();
                    }
                    container.remove();
                }
            }
        }
    },
};

ready(() => {
    window.WPRecipeMaker.privateNotes.load();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}