import React, { useState } from 'react';

import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';
import Loader from 'Shared/Loader';

const contextLabels = {
    popular: __wprm( 'Most popular recipes' ),
    highest_rated: __wprm( 'Highest rated recipes' ),
    recent: __wprm( 'Most recent recipes' ),
    all: __wprm( 'All of your recipes' ),
};

const escapeHtml = ( value = '' ) => `${ value }`
    .replace( /&/g, '&amp;' )
    .replace( /</g, '&lt;' )
    .replace( />/g, '&gt;' )
    .replace( /"/g, '&quot;' )
    .replace( /'/g, '&#39;' );

const getRecipeName = ( recipe ) => {
    if ( 'object' === typeof recipe && recipe ) {
        return recipe.name || '';
    }

    return recipe || '';
};

const ReviewStep = ( props ) => {
    const [ selected, setSelected ] = useState( () => props.ideas.map( ( _, i ) => i ) );
    const [ importing, setImporting ] = useState( false );
    const [ loadingMore, setLoadingMore ] = useState( false );
    const [ contextExpanded, setContextExpanded ] = useState( false );

    const toggleIdea = ( index ) => {
        setSelected( ( prev ) =>
            prev.includes( index )
                ? prev.filter( i => i !== index )
                : [ ...prev, index ]
        );
    };

    const selectAll = () => {
        setSelected( props.ideas.map( ( _, i ) => i ) );
    };

    const deselectAll = () => {
        setSelected( [] );
    };

    const buildPromptSummary = () => {
        const parts = [];

        parts.push( contextLabels[ props.options.context ] || contextLabels.popular );

        if ( props.options.prompt ) {
            parts.push( props.options.prompt );
        }

        return parts.join( ' — ' );
    };

    const buildIdeaNotes = ( idea ) => {
        const notes = [];

        notes.push(
            `<p><strong>${ escapeHtml( __wprm( 'Prompt used:' ) ) }</strong> ${ escapeHtml( contextLabels[ props.options.context ] || contextLabels.popular ) }</p>`
        );

        if ( props.options.prompt ) {
            notes.push(
                `<p>${ escapeHtml( props.options.prompt ) }</p>`
            );
        }

        if ( idea.reason ) {
            notes.push(
                `<p><strong>${ escapeHtml( __wprm( 'Why this idea was chosen:' ) ) }</strong> ${ escapeHtml( idea.reason ) }</p>`
            );
        }

        if ( 'list' === idea.type && idea.existing_recipes && idea.existing_recipes.length > 0 ) {
            notes.push(
                `<p><strong>${ escapeHtml( __wprm( 'Your recipes:' ) ) }</strong> ${ escapeHtml( idea.existing_recipes.map( getRecipeName ).filter( ( name ) => name ).join( ', ' ) ) }</p>`
            );
        }

        if ( 'list' === idea.type && idea.suggested_recipes && idea.suggested_recipes.length > 0 ) {
            notes.push(
                `<p><strong>${ escapeHtml( __wprm( 'Suggested new recipes:' ) ) }</strong> ${ escapeHtml( idea.suggested_recipes.join( ', ' ) ) }</p>`
            );
        }

        return notes.join( '' );
    };

    const handleImport = () => {
        const ideasToImport = selected.map( ( index ) => {
            const idea = props.ideas[ index ];
            return {
                name: idea.name,
                summary: idea.summary || '',
                notes: buildIdeaNotes( idea ),
                type: idea.type || 'recipe',
                ai_prompt_summary: buildPromptSummary(),
                ai_generated_at: new Date().toISOString().replace( 'T', ' ' ).substring( 0, 19 ),
            };
        } );

        if ( ! ideasToImport.length ) {
            return;
        }

        setImporting( true );

        Api.idea.import( ideasToImport ).then( ( response ) => {
            setImporting( false );
            props.onImported( response );
        } ).catch( () => {
            setImporting( false );
            alert( __wprm( 'Failed to import ideas. Please try again.' ) );
        } );
    };

    const handleLoadMore = () => {
        setLoadingMore( true );

        Api.aiAssistant.generateIdeas( props.options ).then( ( response ) => {
            setLoadingMore( false );

            if ( response && response.success && response.ideas ) {
                const currentCount = props.ideas.length;
                const newIdeas = response.ideas;

                props.onIdeasAppended( newIdeas );

                // Auto-select newly loaded ideas.
                setSelected( ( prev ) => [
                    ...prev,
                    ...newIdeas.map( ( _, i ) => currentCount + i ),
                ] );
            } else {
                alert( __wprm( 'Failed to load more ideas. Please try again.' ) );
            }
        } ).catch( () => {
            setLoadingMore( false );
            alert( __wprm( 'Failed to load more ideas. Please try again.' ) );
        } );
    };

    const contextRecipes = props.contextRecipes || [];
    const hasContextRecipes = contextRecipes.length > 0 && props.options.context !== 'all';

    return (
        <div className="wprm-ai-generate-ideas-review">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Review Generated Ideas' ) }</h2>
                <p>{ __wprm( 'Select the ideas you want to import. They will be added to your Ideas list on the Manage page.' ) }</p>
            </div>
            <div className="wprm-ai-generate-ideas-context-summary">
                <div className="wprm-ai-generate-ideas-context-summary-row">
                    <div className="wprm-ai-generate-ideas-context-summary-info">
                        <span className="wprm-ai-generate-ideas-context-summary-label">{ __wprm( 'Based on:' ) }</span>
                        <span>{ contextLabels[ props.options.context ] || contextLabels.popular }</span>
                        { props.options.prompt && (
                            <>
                                <span className="wprm-ai-generate-ideas-context-summary-separator">·</span>
                                <span className="wprm-ai-generate-ideas-context-summary-prompt">{ props.options.prompt }</span>
                            </>
                        ) }
                    </div>
                    <div className="wprm-ai-generate-ideas-context-summary-actions">
                        { hasContextRecipes && (
                            <button
                                type="button"
                                className="button button-small"
                                onClick={ () => setContextExpanded( ! contextExpanded ) }
                            >
                                { contextExpanded ? __wprm( 'Hide recipes used' ) : __wprm( 'Show recipes used' ) }
                            </button>
                        ) }
                        <button
                            type="button"
                            className="button button-small"
                            onClick={ props.onReconfigure }
                        >
                            { __wprm( 'Change prompt' ) }
                        </button>
                    </div>
                </div>
                { contextExpanded && hasContextRecipes && (
                    <div className="wprm-ai-generate-ideas-context-recipes">
                        <p>{ __wprm( 'The following recipes were analyzed to generate these ideas:' ) }</p>
                        <ul>
                            { contextRecipes.map( ( name, i ) => (
                                <li key={ i }>{ name }</li>
                            ) ) }
                        </ul>
                    </div>
                ) }
            </div>
            <div className="wprm-ai-generate-ideas-review-actions">
                <span>
                    { selected.length } / { props.ideas.length } { __wprm( 'selected' ) }
                </span>
                <button type="button" className="button button-small" onClick={ selectAll }>
                    { __wprm( 'Select All' ) }
                </button>
                <button type="button" className="button button-small" onClick={ deselectAll }>
                    { __wprm( 'Deselect All' ) }
                </button>
            </div>
            <div className="wprm-ai-generate-ideas-list">
                { props.ideas.map( ( idea, index ) => (
                    <div
                        key={ index }
                        className={ `wprm-ai-generate-ideas-card${ selected.includes( index ) ? ' is-selected' : '' }` }
                        onClick={ () => toggleIdea( index ) }
                    >
                        <div className="wprm-ai-generate-ideas-card-check">
                            <input
                                type="checkbox"
                                checked={ selected.includes( index ) }
                                onChange={ () => toggleIdea( index ) }
                                onClick={ ( e ) => e.stopPropagation() }
                            />
                        </div>
                        <div className="wprm-ai-generate-ideas-card-content">
                            <strong>{ idea.name }</strong>
                            { idea.summary && (
                                <p className="wprm-ai-generate-ideas-card-summary">{ idea.summary }</p>
                            ) }
                            { idea.reason && (
                                <p className="wprm-ai-generate-ideas-card-reason">{ idea.reason }</p>
                            ) }
                            { idea.type === 'list' && idea.existing_recipes && idea.existing_recipes.length > 0 && (
                                <div className="wprm-ai-generate-ideas-card-recipes">
                                    <span className="wprm-ai-generate-ideas-card-recipes-label">{ __wprm( 'Your recipes:' ) }</span>
                                    <span className="wprm-ai-generate-ideas-card-recipes-list">
                                        { idea.existing_recipes.map( ( r ) => typeof r === 'object' ? r.name : r ).join( ', ' ) }
                                    </span>
                                </div>
                            ) }
                            { idea.type === 'list' && idea.suggested_recipes && idea.suggested_recipes.length > 0 && (
                                <div className="wprm-ai-generate-ideas-card-recipes wprm-ai-generate-ideas-card-suggestions">
                                    <span className="wprm-ai-generate-ideas-card-recipes-label">{ __wprm( 'Suggested new recipes:' ) }</span>
                                    <span className="wprm-ai-generate-ideas-card-recipes-list">
                                        { idea.suggested_recipes.join( ', ' ) }
                                    </span>
                                </div>
                            ) }
                        </div>
                    </div>
                ) ) }
            </div>
            { loadingMore && (
                <div className="wprm-ai-generate-ideas-loading-more">
                    <Loader />
                    <span>{ __wprm( 'Loading more ideas...' ) }</span>
                </div>
            ) }
            <div className="wprm-ai-generate-ideas-actions">
                <button
                    type="button"
                    className="button button-primary wprm-button-ai"
                    onClick={ handleImport }
                    disabled={ importing || loadingMore || ! selected.length }
                >
                    <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                    { importing ? __wprm( 'Importing...' ) : __wprm( 'Import Selected' ) }
                </button>
                <button
                    type="button"
                    className="button button-secondary button-compact"
                    onClick={ handleLoadMore }
                    disabled={ importing || loadingMore }
                >
                    { __wprm( 'Load More Ideas' ) }
                </button>
            </div>
        </div>
    );
};

export default ReviewStep;
