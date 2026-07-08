import React, { Fragment, useEffect, useMemo, useState } from 'react';

import Api from 'Shared/Api';
import Icon from 'Shared/Icon';
import Loader from 'Shared/Loader';
import Tooltip from 'Shared/Tooltip';
import { __wprm } from 'Shared/Translations';

const STATUS_LABELS = {
    ready_to_apply: __wprm( 'Ready to Apply' ),
    needs_review: __wprm( 'Needs Review' ),
    no_change: __wprm( 'No Change' ),
    already_handled: __wprm( 'Already Handled' ),
    error: __wprm( 'Errors' ),
};

const INGREDIENT_STATUS_LABELS = {
    approved: __wprm( 'Approved' ),
    needs_review: __wprm( 'Needs Review' ),
    skipped: __wprm( 'Skipped' ),
    error: __wprm( 'Error' ),
};

const INGREDIENT_STATUS_ORDER = [ 'needs_review', 'error', 'approved', 'skipped' ];

const getApiErrorMessage = ( error, fallback ) => {
    if ( error ) {
        const message = error.responseJSON && error.responseJSON.data && error.responseJSON.data.message
            ? error.responseJSON.data.message
            : error.responseJSON && error.responseJSON.message
                ? error.responseJSON.message
                : error.data && error.data.message
                    ? error.data.message
                    : error.message
                        ? error.message
                        : false;

        if ( message ) {
            return message;
        }
    }

    return fallback;
};

const decodeHtmlEntities = ( value ) => {
    if ( ! value || 'string' !== typeof value || 'undefined' === typeof document ) {
        return value || '';
    }

    const textarea = document.createElement( 'textarea' );
    textarea.innerHTML = value;
    return textarea.value;
};

const getIngredientDisplayName = ( ingredient ) => {
    return decodeHtmlEntities( ingredient && ingredient.display ? ingredient.display : '' );
};

const getSystemLabel = ( system ) => {
    const settings = typeof wprm_admin_modal !== 'undefined' && wprm_admin_modal && wprm_admin_modal.unit_conversion && wprm_admin_modal.unit_conversion.systems
        ? wprm_admin_modal.unit_conversion.systems
        : {};

    return settings[ system ] ? settings[ system ].label : `${ system }`;
};

const formatConfidence = ( confidence ) => {
    const numericConfidence = Number( confidence );

    if ( ! Number.isFinite( numericConfidence ) || numericConfidence <= 0 ) {
        return '';
    }

    return `${ Math.round( numericConfidence * 100 ) }%`;
};

const formatConversion = ( conversion ) => {
    if ( ! conversion ) {
        return __wprm( 'None' );
    }

    const amount = conversion.amount || '';
    const unit = conversion.unit || '';
    const display = `${ amount } ${ unit }`.trim();

    return display || __wprm( 'None' );
};

const getOriginalValues = ( ingredient ) => ( {
    decision: ingredient.decision_source || 'proposed',
    amount: ingredient.resolved_amount === false || ingredient.resolved_amount === null || undefined === ingredient.resolved_amount ? '' : `${ ingredient.resolved_amount }`,
    unit: ingredient.resolved_unit || '',
    confirmed: !! ingredient.manual_override || ! [ 'needs_review', 'error' ].includes( ingredient.status ),
} );

const isIngredientDirty = ( original, current ) => {
    return original.decision !== current.decision
        || original.amount !== current.amount
        || original.unit !== current.unit
        || original.confirmed !== current.confirmed;
};

const IngredientReview = ( props ) => {
    const { ingredient, edits, onChange, saving } = props;
    const [ expanded, setExpanded ] = useState( 'needs_review' === ingredient.status );

    const originalValues = useMemo( () => getOriginalValues( ingredient ), [ ingredient ] );
    const decision = edits ? edits.decision : originalValues.decision;
    const amount = edits ? edits.amount : originalValues.amount;
    const unit = edits ? edits.unit : originalValues.unit;
    const confirmed = edits ? edits.confirmed : originalValues.confirmed;
    const dirty = edits ? isIngredientDirty( originalValues, edits ) : false;
    const ingredientDisplayName = useMemo( () => getIngredientDisplayName( ingredient ), [ ingredient ] );

    const hasExisting = ingredient.existing_conversion && ingredient.existing_conversion.has_conversion;
    const mismatch = ingredient.flags && ingredient.flags.includes( 'existing_mismatch' );
    const needsConfirmation = 'needs_review' === ingredient.status && ! confirmed;

    const updateField = ( field, value ) => {
        onChange( ingredient.index, {
            decision,
            amount,
            unit,
            confirmed,
            [ field ]: value,
        } );
    };

    const hasDetails = ( ingredient.ai_review && ingredient.ai_review.reason )
        || ( ingredient.existing_conversion && ingredient.existing_conversion.has_conversion )
        || ingredient.rule_conversion
        || mismatch;

    return (
        <div className={ `wprm-ai-review-ingredient${ expanded ? ' is-expanded' : '' }${ dirty ? ' is-dirty' : '' }` }>
            <div className="wprm-ai-review-ingredient-row">
                {
                    hasDetails
                    &&
                    <button
                        type="button"
                        className="wprm-ai-review-inline-toggle"
                        onClick={ () => setExpanded( ! expanded ) }
                        title={ expanded ? __wprm( 'Collapse' ) : __wprm( 'Expand' ) }
                    >
                        { expanded ? '\u25B2' : '\u25BC' }
                    </button>
                }
                <span
                    className={ `wprm-ai-review-ingredient-name${ hasDetails ? ' has-details' : '' }` }
                    title={ ingredientDisplayName }
                    onClick={ hasDetails ? () => setExpanded( ! expanded ) : undefined }
                >
                    { ingredientDisplayName }
                </span>
                <select
                    className="wprm-ai-unit-conversion-review-inline-decision"
                    value={ decision }
                    onChange={ (e) => updateField( 'decision', e.target.value ) }
                    disabled={ saving }
                    title={ __wprm( 'Decision' ) }
                >
                    <option value="proposed">{ __wprm( 'Use Proposal' ) }</option>
                    {
                        hasExisting
                        &&
                        <option value="existing">{ __wprm( 'Keep Existing' ) }</option>
                    }
                    <option value="skip">{ __wprm( 'Skip' ) }</option>
                </select>
                <input
                    className="wprm-ai-review-inline-amount"
                    type="text"
                    value={ amount }
                    onChange={ (e) => updateField( 'amount', e.target.value ) }
                    disabled={ saving || 'skip' === decision || 'existing' === decision }
                    title={ __wprm( 'Amount' ) }
                    placeholder={ __wprm( 'Amt' ) }
                />
                <input
                    className="wprm-ai-review-inline-unit"
                    type="text"
                    value={ unit }
                    onChange={ (e) => updateField( 'unit', e.target.value ) }
                    disabled={ saving || 'skip' === decision || 'existing' === decision }
                    title={ __wprm( 'Unit' ) }
                    placeholder={ __wprm( 'Unit' ) }
                />
                {
                    needsConfirmation || ( edits && ! confirmed && originalValues.confirmed === false )
                    ?
                    <Tooltip content={ confirmed ? __wprm( 'Confirmed. Toggle off to undo.' ) : __wprm( 'Toggle to confirm this ingredient.' ) }>
                        <label className="wprm-ai-unit-conversion-review-toggle">
                            <input
                                type="checkbox"
                                checked={ confirmed }
                                onChange={ () => { if ( ! saving ) updateField( 'confirmed', ! confirmed ); } }
                                disabled={ saving }
                            />
                            <span className="wprm-ai-unit-conversion-review-toggle-slider"></span>
                        </label>
                    </Tooltip>
                    : null
                }
            </div>
            {
                expanded
                &&
                <div className="wprm-ai-review-ingredient-details">
                    <div className="wprm-ai-unit-conversion-review-meta">
                        <span><strong>{ __wprm( 'Existing' ) }:</strong> { formatConversion( ingredient.existing_conversion ) }</span>
                        <span><strong>{ __wprm( 'Proposed' ) }:</strong> { formatConversion( { amount: ingredient.resolved_amount, unit: ingredient.resolved_unit } ) }</span>
                        {
                            ingredient.rule_conversion
                            &&
                            <span><strong>{ __wprm( 'Rule Result' ) }:</strong> { formatConversion( ingredient.rule_conversion ) }</span>
                        }
                    </div>
                    {
                        mismatch
                        &&
                        <Tooltip content={ __wprm( 'This recipe already had a converted value. Confirm whether the proposal should replace it, whether the existing value should stay, or whether this ingredient should be skipped.' ) }>
                            <p className="wprm-ai-review-help wprm-ai-unit-conversion-review-warning">{ __wprm( 'Differs from existing conversion — needs confirmation.' ) }</p>
                        </Tooltip>
                    }
                    {
                        ingredient.ai_review && ingredient.ai_review.reason
                        &&
                        <p className="wprm-ai-review-help">
                            { ingredient.ai_review.reason }
                            {
                                ingredient.ai_review && formatConfidence( ingredient.ai_review.confidence )
                                ?
                                ` (${ __wprm( 'Confidence' ) }: ${ formatConfidence( ingredient.ai_review.confidence ) })`
                                : ''
                            }
                        </p>
                    }
                </div>
            }
        </div>
    );
};

const ConversionComparison = ( { ingredients, edits } ) => {
    const rows = useMemo( () => {
        return ( ingredients || [] )
            .map( ( ingredient ) => {
                const edit = edits[ ingredient.index ];
                const decision = edit ? edit.decision : ( ingredient.decision_source || 'proposed' );
                const existingDisplay = formatConversion( ingredient.existing_conversion );

                let proposedDisplay;
                if ( 'skip' === decision || 'existing' === decision ) {
                    proposedDisplay = existingDisplay;
                } else {
                    const proposedAmount = edit ? edit.amount : ( false === ingredient.resolved_amount || null === ingredient.resolved_amount || undefined === ingredient.resolved_amount ? '' : `${ ingredient.resolved_amount }` );
                    const proposedUnit = edit ? edit.unit : ( ingredient.resolved_unit || '' );
                    proposedDisplay = `${ proposedAmount } ${ proposedUnit }`.trim() || __wprm( 'None' );
                }

                const isChanged = proposedDisplay !== existingDisplay;

                return {
                    key: ingredient.index,
                    name: getIngredientDisplayName( ingredient ),
                    existing: existingDisplay,
                    proposed: proposedDisplay,
                    isChanged,
                };
            } );
    }, [ ingredients, edits ] );

    if ( ! rows.length ) {
        return null;
    }

    return (
        <div className="wprm-ai-review-comparison wprm-ai-conversion-comparison">
            <div className="wprm-ai-review-label">
                <div className="wprm-ai-review-label-title">{ __wprm( 'Conversion Summary' ) }</div>
                <table>
                    <thead>
                        <tr>
                            <td className="wprm-ai-review-label-name">{ __wprm( 'Ingredient' ) }</td>
                            <td className="wprm-ai-review-label-value">{ __wprm( 'Current' ) }</td>
                            <td className="wprm-ai-review-label-value">{ __wprm( 'Proposed' ) }</td>
                        </tr>
                    </thead>
                    <tbody>
                        {
                            rows.map( ( row ) => (
                                <tr key={ row.key } className={ row.isChanged ? 'is-changed' : '' }>
                                    <td className="wprm-ai-review-label-name">{ row.name }</td>
                                    <td className="wprm-ai-review-label-value">{ row.existing }</td>
                                    <td className="wprm-ai-review-label-value">{ row.proposed }</td>
                                </tr>
                            ) )
                        }
                    </tbody>
                </table>
            </div>
        </div>
    );
};

const RecipeCard = ( {
    batchId,
    result,
    onUpdated,
    onApplied,
} ) => {
    const [ edits, setEdits ] = useState( {} );
    const [ saving, setSaving ] = useState( false );
    const [ applying, setApplying ] = useState( false );
    const [ requestError, setRequestError ] = useState( '' );
    const [ detailsExpanded, setDetailsExpanded ] = useState( result.status !== 'already_handled' );
    const recipe = result.recipe || {};
    const canApply = 'ready_to_apply' === result.status;

    // Collapse details when result transitions to already_handled (e.g. after applying).
    useEffect( () => {
        if ( 'already_handled' === result.status ) {
            setDetailsExpanded( false );
        }
    }, [ result.status ] );

    useEffect( () => {
        setRequestError( '' );
        setEdits( ( previousEdits ) => {
            const nextEdits = {};

            Object.keys( previousEdits ).forEach( ( index ) => {
                const ingredient = ( result.ingredients || [] ).find( ( ing ) => `${ ing.index }` === `${ index }` );

                if ( ingredient && isIngredientDirty( getOriginalValues( ingredient ), previousEdits[ index ] ) ) {
                    nextEdits[ index ] = previousEdits[ index ];
                }
            } );

            return nextEdits;
        } );
    }, [ result ] );

    const dirtyIndices = useMemo( () => {
        return Object.keys( edits ).filter( ( index ) => {
            const ingredient = ( result.ingredients || [] ).find( ( ing ) => `${ ing.index }` === `${ index }` );

            if ( ! ingredient ) {
                return false;
            }

            return isIngredientDirty( getOriginalValues( ingredient ), edits[ index ] );
        } );
    }, [ edits, result.ingredients ] );

    const hasDirtyIngredients = dirtyIndices.length > 0;

    const applyDisabledReason = useMemo( () => {
        if ( canApply && ! saving && ! applying ) {
            return '';
        }

        if ( saving ) {
            return __wprm( 'Please wait for the current save to complete.' );
        }

        if ( applying ) {
            return __wprm( 'Applying changes to the recipe...' );
        }

        if ( 'already_handled' === result.status ) {
            return __wprm( 'This recipe has already been applied.' );
        }

        if ( 'no_change' === result.status ) {
            return __wprm( 'No changes to apply — the conversions match the current recipe values.' );
        }

        const counts = result.counts || {};
        const needsReview = counts.needs_review || 0;
        const errors = counts.error || 0;
        const parts = [];

        if ( needsReview > 0 ) {
            parts.push( needsReview + ' ' + ( 1 === needsReview ? __wprm( 'ingredient still needs review' ) : __wprm( 'ingredients still need review' ) ) );
        }

        if ( errors > 0 ) {
            parts.push( errors + ' ' + ( 1 === errors ? __wprm( 'ingredient has an error' ) : __wprm( 'ingredients have errors' ) ) );
        }

        if ( hasDirtyIngredients ) {
            parts.push( __wprm( 'save your changes first' ) );
        }

        if ( parts.length > 0 ) {
            return parts.join( '; ' ) + '.';
        }

        return __wprm( 'This recipe is not ready to apply yet.' );
    }, [ canApply, saving, applying, result.status, result.counts, hasDirtyIngredients ] );

    const handleIngredientChange = ( index, values ) => {
        setEdits( ( prev ) => ( { ...prev, [ index ]: values } ) );
    };

    const saveAllChanges = async () => {
        setSaving( true );
        setRequestError( '' );
        let latestResult = null;

        try {
            for ( const index of dirtyIndices ) {
                const edit = edits[ index ];
                const ingredient = ( result.ingredients || [] ).find( ( ing ) => `${ ing.index }` === `${ index }` );

                if ( ! ingredient ) {
                    continue;
                }

                const response = await Api.aiAssistant.saveUnitConversionReviewDecision( batchId, recipe.id, ingredient.index, {
                    decision: edit.decision,
                    amount: edit.amount,
                    unit: edit.unit,
                } );

                if ( response && response.result ) {
                    latestResult = response.result;
                } else {
                    throw new Error( __wprm( 'Could not save the ingredient review changes.' ) );
                }
            }

            if ( latestResult ) {
                onUpdated( latestResult );
            }
        } catch ( error ) {
            setRequestError( getApiErrorMessage( error, __wprm( 'Could not save the ingredient review changes. Please try again.' ) ) );

            if ( latestResult ) {
                onUpdated( latestResult );
            }
        } finally {
            setSaving( false );
        }
    };

    const groupedIngredients = useMemo( () => {
        const groups = {};

        ( result.ingredients || [] ).forEach( ( ingredient ) => {
            const status = ingredient.status || 'error';
            if ( ! groups[ status ] ) {
                groups[ status ] = [];
            }

            groups[ status ].push( ingredient );
        } );

        return groups;
    }, [ result.ingredients ] );

    const applyRecipe = async () => {
        setApplying( true );
        setRequestError( '' );

        try {
            const response = await Api.aiAssistant.applyUnitConversionReviewRecipe( batchId, recipe.id );

            if ( response && response.result ) {
                onApplied( response.result );
            } else {
                throw new Error( __wprm( 'Could not apply the reviewed conversions to this recipe.' ) );
            }
        } catch ( error ) {
            setRequestError( getApiErrorMessage( error, __wprm( 'Could not apply the reviewed conversions to this recipe. Please try again.' ) ) );
        } finally {
            setApplying( false );
        }
    };

    const renderIngredientGroup = ( ingredients ) => {
        return ingredients.map( ( ingredient ) => (
            <IngredientReview
                key={ ingredient.index }
                ingredient={ ingredient }
                edits={ edits[ ingredient.index ] || null }
                onChange={ handleIngredientChange }
                saving={ saving || applying }
            />
        ) );
    };

    return (
        <div className="wprm-ai-assistant-tool-card wprm-ai-review-card">
            <div className="wprm-ai-review-card-header">
                <div>
                    <h3>{ recipe.name } <Icon type="pencil" title={ __wprm( 'Edit Recipe' ) } onClick={ () => { window.WPRM_Modal.open( 'recipe', { recipeId: recipe.id } ); } } /></h3>
                    <p>
                        { __wprm( 'Convert from' ) } { getSystemLabel( recipe.original_system ) } { __wprm( 'to' ) } { getSystemLabel( recipe.target_system ) }
                        { result.proposed_changes && 'already_handled' !== result.status ? ` | ${ __wprm( 'Pending changes' ) }: ${ result.proposed_changes }` : '' }
                    </p>
                </div>
                <span className={ `wprm-ai-review-badge is-${ result.status }` }>{ STATUS_LABELS[ result.status ] || result.status }</span>
            </div>
            {
                'already_handled' === result.status
                &&
                <button
                    type="button"
                    className="wprm-ai-review-details-toggle"
                    onClick={ () => setDetailsExpanded( ! detailsExpanded ) }
                >
                    { detailsExpanded ? __wprm( 'Hide Details' ) : __wprm( 'View Details' ) }
                </button>
            }
            { detailsExpanded && <>
            <div className="wprm-ai-review-ingredients">
                {
                    INGREDIENT_STATUS_ORDER.map( ( status ) => {
                        const ingredients = groupedIngredients[ status ] || [];
                        if ( ! ingredients.length ) {
                            return null;
                        }

                        return (
                            <div key={ status } className="wprm-ai-review-ingredient-group">
                                <div className={ `wprm-ai-review-group-header is-${ status }` }>
                                    <span className={ `wprm-ai-review-status-dot is-${ status }` }></span>
                                    { INGREDIENT_STATUS_LABELS[ status ] || status } ({ ingredients.length })
                                </div>
                                { renderIngredientGroup( ingredients ) }
                            </div>
                        );
                    } )
                }
            </div>
            <div className="wprm-ai-review-save-actions">
                <button
                    type="button"
                    className="button button-primary button-compact"
                    disabled={ ! hasDirtyIngredients || saving || applying }
                    onClick={ saveAllChanges }
                >
                    { saving ? __wprm( 'Saving...' ) : __wprm( 'Save Changes to Proposal' ) + ( hasDirtyIngredients ? ` (${ dirtyIndices.length })` : '' ) }
                </button>
            </div>
            {
                requestError
                &&
                <p className="wprm-ai-review-help">{ requestError }</p>
            }
            <ConversionComparison
                ingredients={ result.ingredients }
                edits={ edits }
            />
            <div className="wprm-ai-review-apply-actions">
                <Tooltip content={ applyDisabledReason }>
                    <span>
                        <button
                            type="button"
                            className="button button-primary button-compact wprm-button-ai"
                            disabled={ ! canApply || saving || applying }
                            onClick={ applyRecipe }
                        >
                            <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                            { applying ? __wprm( 'Applying...' ) : __wprm( 'Apply to Recipe' ) }
                        </button>
                    </span>
                </Tooltip>
            </div>
            </> }
        </div>
    );
};

const UnitConversionReview = () => {
    const query = new URLSearchParams( window.location.search );
    const batchIdFromUrl = query.get( 'batch' );
    const manageUrl = wprm_admin.manage_url;

    const [ batch, setBatch ] = useState( null );
    const [ loading, setLoading ] = useState( true );
    const [ starting, setStarting ] = useState( false );
    const [ applyingReady, setApplyingReady ] = useState( false );
    const [ error, setError ] = useState( '' );
    const [ filter, setFilter ] = useState( 'all' );

    const loadBatch = async ( id = batchIdFromUrl, showSpinner = true ) => {
        if ( showSpinner ) {
            setLoading( true );
        }

        try {
            const request = id ? Api.aiAssistant.getUnitConversionReviewBatch( id ) : Api.aiAssistant.getCurrentUnitConversionReviewBatch();
            const response = await request;

            setBatch( response && response.batch ? response.batch : null );
            setError( '' );
        } catch ( loadError ) {
            setError( getApiErrorMessage( loadError, __wprm( 'Could not load the unit conversion review batch. Please refresh and try again.' ) ) );
        } finally {
            setLoading( false );
        }
    };

    useEffect( () => {
        loadBatch();
    }, [] );

    useEffect( () => {
        if ( ! batch || ! [ 'queued', 'processing' ].includes( batch.status ) ) {
            return undefined;
        }

        const interval = window.setInterval( () => loadBatch( batch.id, false ), 5000 );

        return () => window.clearInterval( interval );
    }, [ batch && batch.id, batch && batch.status ] );

    const results = batch && batch.results ? batch.results : [];
    const visibleResults = 'all' === filter ? results : results.filter( ( result ) => result.status === filter );

    const updateOneResult = ( updatedResult ) => {
        setBatch( ( currentBatch ) => {
            if ( ! currentBatch ) {
                return currentBatch;
            }

            const nextResults = currentBatch.results.map( ( result ) => result.recipe.id === updatedResult.recipe.id ? updatedResult : result );

            return {
                ...currentBatch,
                results: nextResults,
            };
        } );

        if ( batch && batch.id ) {
            loadBatch( batch.id, false );
        }
    };

    const startBatch = async ( options ) => {
        setStarting( true );
        setError( '' );

        try {
            const response = await Api.aiAssistant.startUnitConversionReviewBatch( options );

            if ( response && response.batch ) {
                setBatch( response.batch );
                window.history.replaceState( {}, '', response.url || window.location.href );
            } else {
                throw new Error( __wprm( 'Could not start the unit conversion review batch.' ) );
            }
        } catch ( startError ) {
            setError( getApiErrorMessage( startError, __wprm( 'Could not start the unit conversion review batch. Please try again.' ) ) );
        } finally {
            setStarting( false );
        }
    };

    const applyReady = async () => {
        if ( ! batch ) {
            return;
        }

        setApplyingReady( true );
        setError( '' );

        try {
            const response = await Api.aiAssistant.applyUnitConversionReviewReady( batch.id );

            if ( response && response.batch ) {
                setBatch( response.batch );
            } else {
                throw new Error( __wprm( 'Could not apply the ready recipes.' ) );
            }
        } catch ( applyError ) {
            setError( getApiErrorMessage( applyError, __wprm( 'Could not apply the ready recipes. Please try again.' ) ) );
        } finally {
            setApplyingReady( false );
        }
    };

    return (
        <div className="wprm-ai-review wprm-ai-unit-conversion-review">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Unit Conversion Review' ) }</h2>
                <p>{ __wprm( 'Review rule-based and AI-assisted second unit systems in batches. Recipes with uncertain conversions or differences from stored converted values stay in the queue until confirmed.' ) }</p>
            </div>
            <div className="wprm-ai-generate-ideas-configure">
                <div className="wprm-ai-generate-ideas-field">
                    <div className="wprm-ai-generate-ideas-field-header">
                        <h3>{ __wprm( 'Start a review batch' ) }</h3>
                        <p>
                            { __wprm( 'Use these options to review recipes that are missing a second unit system or to recheck everything. For a custom selection, launch it using the "Bulk Edit" column on the ' ) }
                            <a href={ manageUrl }>{ __wprm( 'Manage page' ) }</a>
                            { __wprm( '.' ) }
                        </p>
                    </div>
                    <div className="wprm-ai-generate-ideas-actions">
                        <button
                            type="button"
                            className="button button-primary button-compact wprm-button-ai"
                            disabled={ starting }
                            onClick={ () => startBatch( { scope: 'missing_only' } ) }
                        >
                            <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                            { __wprm( 'Run Missing Only' ) }
                        </button>
                        <button
                            type="button"
                            className="button button-secondary button-compact"
                            disabled={ starting }
                            onClick={ () => startBatch( { scope: 'all_recipes' } ) }
                        >
                            { __wprm( 'Run All Recipes' ) }
                        </button>
                        {
                            batch
                            &&
                            <button
                                type="button"
                                className="button button-secondary button-compact"
                                disabled={ loading }
                                onClick={ () => loadBatch( batch.id ) }
                            >
                                { __wprm( 'Refresh' ) }
                            </button>
                        }
                    </div>
                </div>
                {
                    error
                    &&
                    <p className="wprm-ai-review-help">{ error }</p>
                }
            </div>
            {
                loading
                ?
                <div className="wprm-ai-generate-ideas-generating">
                    <Loader />
                    <p>{ __wprm( 'Loading batch data...' ) }</p>
                </div>
                :
                <Fragment>
                    {
                        batch
                        ?
                        <Fragment>
                            <div className="wprm-ai-assistant-tool-card wprm-ai-review-overview">
                                <div className="wprm-ai-review-card-header">
                                    <div>
                                        <h3>{ __wprm( 'Batch Overview' ) }</h3>
                                        <p>{ __wprm( 'Scope:' ) } { batch.scope } | { __wprm( 'Progress:' ) } { batch.processed } / { batch.total }</p>
                                    </div>
                                    <span className={ `wprm-ai-review-badge is-${ batch.status }` }>{ batch.status }</span>
                                </div>
                                <div className="wprm-ai-generate-ideas-pills">
                                    <button type="button" className={ `wprm-ai-pill${ 'all' === filter ? ' is-selected' : '' }` } onClick={ () => setFilter( 'all' ) }>{ __wprm( 'All' ) }</button>
                                    {
                                        Object.keys( STATUS_LABELS ).map( ( status ) => (
                                            <button key={ status } type="button" className={ `wprm-ai-pill${ status === filter ? ' is-selected' : '' }` } onClick={ () => setFilter( status ) }>
                                                { STATUS_LABELS[ status ] } ({ batch.counts && batch.counts[ status ] ? batch.counts[ status ] : 0 })
                                            </button>
                                        ) )
                                    }
                                </div>
                                <div className="wprm-ai-generate-ideas-actions">
                                    <button
                                        type="button"
                                        className="button button-primary button-compact wprm-button-ai"
                                        disabled={ applyingReady || ! batch || 0 === ( batch.counts.ready_to_apply || 0 ) }
                                        onClick={ applyReady }
                                    >
                                        <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                                        { applyingReady ? __wprm( 'Applying...' ) : __wprm( 'Apply Ready Recipes' ) }
                                    </button>
                                </div>
                            </div>
                            {
                                visibleResults.map( ( result ) => (
                                    <RecipeCard
                                        key={ result.recipe.id }
                                        batchId={ batch.id }
                                        result={ result }
                                        onUpdated={ updateOneResult }
                                        onApplied={ updateOneResult }
                                    />
                                ) )
                            }
                        </Fragment>
                        :
                        <div className="wprm-ai-assistant-tool-card wprm-ai-review-empty">
                            <p>{ __wprm( 'No unit conversion review batch has been started yet.' ) }</p>
                        </div>
                    }
                </Fragment>
            }
        </div>
    );
};

export default UnitConversionReview;
