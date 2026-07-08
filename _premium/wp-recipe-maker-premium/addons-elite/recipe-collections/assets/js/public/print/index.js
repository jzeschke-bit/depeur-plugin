import { createRoot } from 'react-dom/client';
import React from 'react';
import { HashRouter } from 'react-router-dom';

import App from './App';

window.WPRMCollectionPrint = {
	init() {
        // On args change.
        document.addEventListener( 'wprmPrintArgs', () => {
            this.onArgsChange();
        });
    },
    onArgsChange(  ) {
        const args = window.WPRMPrint.args;

        if ( args.hasOwnProperty( 'collection' ) && args.hasOwnProperty( 'recipes' ) ) {
            this.loadCollection( args.collection, args.recipes, args.showingNutrition );
        }
    },
    loadCollection( collection, recipes, showingNutrition ) {
        // Load React app.
        let appContainer = document.getElementById( 'wprm-recipe-collections-print-app' );

        if (appContainer) {
            // Make sure nutrition facts are rendered.
            collection.showNutrition = true;

            const root = createRoot(appContainer);
            root.render(
                <HashRouter
                    hashType="noslash"
                >
                    <App
                        layout={ wprmprc_public.settings.recipe_collections_appearance_layout }
                        collection={ collection }
                        recipes={ recipes }
                    />
                </HashRouter>
            );

            // Toggle checkbox.
            const toggle = document.getElementById( 'wprm-print-toggle-collection-nutrition' );

            if ( toggle ) {
                toggle.checked = showingNutrition;
                window.WPRMPrint.onClickToggle( toggle );
            }
        }
    },
};
document.addEventListener( 'wprmPrintInit', () => {
    window.WPRMCollectionPrint.init();
} );