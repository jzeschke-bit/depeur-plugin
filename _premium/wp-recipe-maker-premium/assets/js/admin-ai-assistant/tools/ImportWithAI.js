import React, { useState } from 'react';

import { __wprm } from 'Shared/Translations';
import { AIRecipeImportContent, useAIRecipeImport } from 'Shared/AiRecipeImport';

const ImportWithAI = () => {
    const [ modalError, setModalError ] = useState( '' );

    const {
        text,
        importing,
        error,
        importedRecipe,
        onTextChange,
        editImportedRecipe,
        importRecipe,
    } = useAIRecipeImport();

    const openRecipeModal = () => {
        if ( ! importedRecipe ) {
            return;
        }

        if ( 'undefined' === typeof window.WPRM_Modal || ! window.WPRM_Modal || ! window.WPRM_Modal.open ) {
            setModalError( __wprm( 'The recipe modal could not be opened. Please refresh the page and try again.' ) );
            return;
        }

        setModalError( '' );

        const defaultRecipe = JSON.parse( JSON.stringify( wprm_admin_modal.recipe || {} ) );
        const normalizedRecipe = {
            ...defaultRecipe,
            ...importedRecipe,
            custom_fields: {
                ...( defaultRecipe.custom_fields || {} ),
                ...( importedRecipe.custom_fields || {} ),
            },
            nutrition: {
                ...( defaultRecipe.nutrition || {} ),
                ...( importedRecipe.nutrition || {} ),
            },
            tags: {
                ...( defaultRecipe.tags || {} ),
                ...( importedRecipe.tags || {} ),
            },
        };

        window.WPRM_Modal.open( 'recipe', {
            recipe: normalizedRecipe,
            isNewRecipe: true,
        } );
    };

    return (
        <div className="wprm-ai-assistant-import-with-ai">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Import with AI' ) }</h2>
                <p>{ __wprm( 'Paste recipe text and let AI extract the recipe fields for you. You can review the imported values before applying them.' ) }</p>
            </div>
            <div className="wprm-ai-assistant-tool-card">
                <AIRecipeImportContent
                    text={ text }
                    importing={ importing }
                    error={ error }
                    importedRecipe={ importedRecipe }
                    onTextChange={ ( value ) => {
                        setModalError( '' );
                        onTextChange( value );
                    } }
                />
                { modalError && (
                    <div className="wprm-admin-modal-ai-text-import-error wprm-ai-assistant-import-error">
                        <p><strong>{ __wprm( 'Error' ) }</strong></p>
                        <p>{ modalError }</p>
                    </div>
                ) }
                <div className="wprm-ai-generate-ideas-actions">
                    <button
                        type="button"
                        className="button button-secondary button-compact"
                        onClick={ () => {
                            setModalError( '' );
                            if ( importedRecipe ) {
                                editImportedRecipe();
                            } else {
                                importRecipe();
                            }
                        } }
                    >
                        { importedRecipe ? __wprm( 'Import Again' ) : __wprm( 'Import with AI' ) }
                    </button>
                    <button
                        type="button"
                        className="button button-primary wprm-button-ai"
                        onClick={ openRecipeModal }
                        disabled={ ! importedRecipe }
                    >
                        <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                        { __wprm( 'Create Recipe' ) }
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ImportWithAI;
