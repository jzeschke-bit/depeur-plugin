import React, { Fragment, useEffect, useMemo, useState } from 'react';

import Api from 'Shared/Api';
import Icon from 'Shared/Icon';
import Loader from 'Shared/Loader';
import Tooltip from 'Shared/Tooltip';
import { __wprm } from 'Shared/Translations';

const STATUS_LABELS = {
    ready_to_apply: __wprm( 'Ready to Apply' ),
    needs_review: __wprm( 'Needs Review' ),
    no_match: __wprm( 'No Match' ),
    no_change: __wprm( 'No Change' ),
    already_handled: __wprm( 'Already Handled' ),
    suggest_update: __wprm( 'Suggested Updates' ),
    error: __wprm( 'Errors' ),
};

const INGREDIENT_STATUS_LABELS = {
    needs_review: __wprm( 'Needs Review' ),
    error: __wprm( 'Error' ),
    approved: __wprm( 'Approved' ),
    excluded: __wprm( 'Excluded' ),
};

const INGREDIENT_STATUS_ORDER = [ 'needs_review', 'error', 'approved', 'excluded' ];

const SUMMARY_NUTRIENTS = [ 'calories', 'carbohydrates', 'fat', 'protein' ];
const SEARCH_MATCH_VALUE = '__wprm_search_match__';
const RESULT_FLAG_LABELS = {
    needs_review: __wprm( 'Some ingredients still need review.' ),
    no_match: __wprm( 'Some ingredients do not have a confirmed nutrition match yet.' ),
    facts_missing: __wprm( 'Some matched ingredients are still missing nutrition facts.' ),
    no_resolved_ingredients: __wprm( 'No ingredients have a confirmed nutrition match yet.' ),
    calories_too_low: __wprm( 'The calorie total looks unusually low.' ),
    calories_too_high: __wprm( 'The calorie total looks unusually high.' ),
    macro_calories_mismatch: __wprm( 'The calories do not line up with the fat, carb, and protein totals.' ),
    incomplete_ingredients: __wprm( 'Not all ingredients are ready for nutrition calculation yet.' ),
    ambiguous_units: __wprm( 'Some ingredients need a clearer amount or unit.' ),
    custom_match_needs_review: __wprm( 'A saved custom ingredient match still needs review.' ),
};

const getInputValue = ( value ) => {
    if ( false === value || null === value || undefined === value ) {
        return '';
    }

    return `${ value }`;
};

const formatNutritionSummary = ( nutrition ) => {
    if ( ! nutrition ) {
        return __wprm( 'No proposed nutrition yet.' );
    }

    const summaryNutrients = wprm_admin_modal.nutrition
        ? Object.keys( wprm_admin_modal.nutrition ).filter( ( nutrient ) => SUMMARY_NUTRIENTS.includes( nutrient ) )
        : SUMMARY_NUTRIENTS;

    const parts = summaryNutrients.map( ( nutrient ) => {
        if ( ! nutrition.hasOwnProperty( nutrient ) || false === nutrition[ nutrient ] || '' === nutrition[ nutrient ] ) {
            return false;
        }

        const label = wprm_admin_modal.nutrition && wprm_admin_modal.nutrition[ nutrient ] ? wprm_admin_modal.nutrition[ nutrient ].label : nutrient;
        const unit = wprm_admin_modal.nutrition && wprm_admin_modal.nutrition[ nutrient ] ? wprm_admin_modal.nutrition[ nutrient ].unit : '';

        return `${ label }: ${ nutrition[ nutrient ] }${ unit }`;
    } ).filter( Boolean );

    return parts.length ? parts.join( ' | ' ) : __wprm( 'No proposed nutrition yet.' );
};

const formatResultFlag = ( flag ) => {
    if ( RESULT_FLAG_LABELS[ flag ] ) {
        return RESULT_FLAG_LABELS[ flag ];
    }

    const label = `${ flag || '' }`
        .replace( /_/g, ' ' )
        .trim();

    if ( ! label ) {
        return '';
    }

    return `${ label.charAt( 0 ).toUpperCase() }${ label.slice( 1 ) }.`;
};

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

const getCandidateOptions = ( ingredient, extraOptions = [] ) => {
    const current = ingredient.selected_candidate ? [ ingredient.selected_candidate ] : [];
    const candidates = ingredient.candidate_options || [];
    const deduped = [];
    const seen = new Set();

    current.concat( candidates ).concat( extraOptions ).forEach( ( candidate ) => {
        if ( ! candidate || ! candidate.id || seen.has( candidate.id ) ) {
            return;
        }

        seen.add( candidate.id );
        deduped.push( candidate );
    } );

    return deduped;
};

const getOriginalValues = ( ingredient ) => ( {
    candidateId: ingredient.selected_candidate ? `${ ingredient.selected_candidate.id }` : '',
    amount: getInputValue( ingredient.resolved_amount ),
    unit: ingredient.resolved_unit || '',
    exclude: !! ingredient.excluded,
    confirmed: !! ingredient.manual_override || ! [ 'needs_review', 'error' ].includes( ingredient.status ),
} );

const isIngredientDirty = ( original, current ) => {
    return original.candidateId !== current.candidateId
        || original.amount !== current.amount
        || original.unit !== current.unit
        || original.exclude !== current.exclude
        || original.confirmed !== current.confirmed;
};

const hasNutritionValue = ( value ) => {
    return false !== value && null !== value && undefined !== value && '' !== value;
};

const getOrderedNutrientFields = () => {
    const fields = wprm_admin_modal.nutrition || {};
    return Object.entries( fields )
        .filter( ( [ key ] ) => 'serving_size' !== key && 'serving_unit' !== key );
};

const getChangedNutrientKeys = ( proposed, existing ) => {
    const changed = new Set();
    if ( ! proposed || ! existing ) {
        return changed;
    }

    const fields = wprm_admin_modal.nutrition || {};
    for ( const key of Object.keys( fields ) ) {
        if ( 'serving_size' === key || 'serving_unit' === key ) {
            continue;
        }

        const pVal = proposed && hasNutritionValue( proposed[ key ] ) ? parseFloat( proposed[ key ] ) : null;
        const eVal = existing && hasNutritionValue( existing[ key ] ) ? parseFloat( existing[ key ] ) : null;

        if ( null === pVal && null === eVal ) {
            continue;
        }
        if ( null === pVal || null === eVal || Math.abs( pVal - eVal ) > 0.05 ) {
            changed.add( key );
        }
    }

    return changed;
};

const hasAnyNutritionValues = ( nutrition ) => {
    if ( ! nutrition || 'object' !== typeof nutrition ) {
        return false;
    }

    for ( const [ key, value ] of Object.entries( nutrition ) ) {
        if ( 'serving_size' === key || 'serving_unit' === key ) {
            continue;
        }
        if ( hasNutritionValue( value ) && 0 !== parseFloat( value ) ) {
            return true;
        }
    }

    return false;
};

const formatNutrientValue = ( value ) => {
    const num = parseFloat( value );
    if ( isNaN( num ) ) {
        return value;
    }
    return Math.round( num * 10 ) / 10;
};

const NutritionLabel = ( props ) => {
    const { nutrition, title, highlightKeys, visibleKeys } = props;
    const fields = useMemo( () => getOrderedNutrientFields(), [] );

    // If visibleKeys is provided (comparison mode), show those rows; otherwise show rows with values.
    const rows = visibleKeys
        ? fields.filter( ( [ key ] ) => visibleKeys.has( key ) )
        : fields.filter( ( [ key ] ) => hasNutritionValue( nutrition[ key ] ) );

    if ( 0 === rows.length ) {
        return null;
    }

    return (
        <div className="wprm-ai-review-label">
            <div className="wprm-ai-review-label-title">{ title }</div>
            <table>
                <tbody>
                    {
                        rows.map( ( [ key, fieldDef ] ) => {
                            const isChanged = highlightKeys && highlightKeys.has( key );
                            const hasValue = hasNutritionValue( nutrition[ key ] );
                            const value = hasValue ? formatNutrientValue( nutrition[ key ] ) : '\u2014';
                            const daily = hasValue && fieldDef.daily && value ? Math.round( parseFloat( nutrition[ key ] ) / fieldDef.daily * 100 ) : false;

                            return (
                                <tr key={ key } className={ isChanged ? 'is-changed' : '' }>
                                    <td className="wprm-ai-review-label-name">{ fieldDef.label }</td>
                                    <td className="wprm-ai-review-label-value">{ hasValue ? `${ value }${ fieldDef.unit }` : value }</td>
                                    {
                                        false !== daily
                                        &&
                                        <td className="wprm-ai-review-label-daily">{ daily }%</td>
                                    }
                                </tr>
                            );
                        } )
                    }
                </tbody>
            </table>
        </div>
    );
};

const NutritionComparison = ( props ) => {
    const { proposedNutrition, existingNutrition } = props;
    const showExisting = hasAnyNutritionValues( existingNutrition );
    const changedKeys = useMemo( () => {
        return showExisting ? getChangedNutrientKeys( proposedNutrition, existingNutrition ) : new Set();
    }, [ proposedNutrition, existingNutrition, showExisting ] );

    // In comparison mode, compute the union of keys that have a value in either nutrition set.
    const visibleKeys = useMemo( () => {
        if ( ! showExisting ) {
            return null;
        }

        const keys = new Set();
        const fields = wprm_admin_modal.nutrition || {};
        for ( const key of Object.keys( fields ) ) {
            if ( 'serving_size' === key || 'serving_unit' === key ) {
                continue;
            }
            if ( ( proposedNutrition && hasNutritionValue( proposedNutrition[ key ] ) ) || ( existingNutrition && hasNutritionValue( existingNutrition[ key ] ) ) ) {
                keys.add( key );
            }
        }
        return keys;
    }, [ proposedNutrition, existingNutrition, showExisting ] );

    if ( ! hasAnyNutritionValues( proposedNutrition ) && ! showExisting ) {
        return null;
    }

    return (
        <div className={ `wprm-ai-review-comparison${ showExisting ? '' : ' is-single' }` }>
            {
                showExisting
                &&
                <NutritionLabel
                    nutrition={ existingNutrition }
                    title={ __wprm( 'Current' ) }
                    highlightKeys={ changedKeys }
                    visibleKeys={ visibleKeys }
                />
            }
            <NutritionLabel
                nutrition={ proposedNutrition || {} }
                title={ showExisting ? __wprm( 'Proposed' ) : __wprm( 'Proposed Nutrition' ) }
                highlightKeys={ changedKeys }
                visibleKeys={ visibleKeys }
            />
        </div>
    );
};

const IngredientReview = ( props ) => {
    const { ingredient, edits, onChange, saving } = props;
    const [ candidateOptions, setCandidateOptions ] = useState( getCandidateOptions( ingredient ) );
    const [ modalError, setModalError ] = useState( '' );
    const [ expanded, setExpanded ] = useState( 'needs_review' === ingredient.status );

    const originalValues = useMemo( () => getOriginalValues( ingredient ), [ ingredient ] );
    const candidateId = edits ? edits.candidateId : originalValues.candidateId;
    const amount = edits ? edits.amount : originalValues.amount;
    const unit = edits ? edits.unit : originalValues.unit;
    const exclude = edits ? edits.exclude : originalValues.exclude;
    const confirmed = edits ? edits.confirmed : originalValues.confirmed;
    const dirty = edits ? isIngredientDirty( originalValues, edits ) : false;
    const needsConfirmation = 'needs_review' === ingredient.status && ! confirmed;

    useEffect( () => {
        setCandidateOptions( getCandidateOptions( ingredient ) );
        setModalError( '' );
    }, [ ingredient ] );

    const options = useMemo( () => getCandidateOptions( ingredient, candidateOptions ), [ ingredient, candidateOptions ] );
    const selectedCandidate = useMemo( () => options.find( ( candidate ) => `${ candidate.id }` === candidateId ) || false, [ options, candidateId ] );
    const ingredientDisplayName = useMemo( () => getIngredientDisplayName( ingredient ), [ ingredient ] );

    const updateField = ( field, value ) => {
        onChange( ingredient.index, {
            candidateId,
            candidate: selectedCandidate || false,
            amount,
            unit,
            exclude,
            confirmed,
            [ field ]: value,
        } );
    };

    const openSearchModal = () => {
        if ( 'undefined' === typeof window.WPRM_Modal || ! window.WPRM_Modal || ! window.WPRM_Modal.open ) {
            setModalError( __wprm( 'The nutrition search modal could not be opened. Please refresh the page and try again.' ) );
            return;
        }

        setModalError( '' );

        window.WPRM_Modal.open( 'nutrition-search', {
            ingredient: {
                display: ingredientDisplayName,
                name: ingredient.original && ingredient.original.name ? ingredient.original.name : '',
            },
            initialSearch: ingredient.original && ingredient.original.name ? ingredient.original.name : ingredientDisplayName,
            saveCallback: ( candidate, _search, searchOptions ) => {
                setCandidateOptions( ( previousOptions ) => getCandidateOptions( ingredient, searchOptions && searchOptions.length ? searchOptions : previousOptions.concat( candidate ) ) );
                onChange( ingredient.index, {
                    candidateId: `${ candidate.id }`,
                    candidate,
                    amount,
                    unit,
                    exclude,
                    confirmed,
                } );
            },
        } );
    };

    const hasDetails = ( ingredient.ai_review && ingredient.ai_review.reason )
        || ( ingredient.unit_analysis && ingredient.unit_analysis.reasons && ingredient.unit_analysis.reasons.length > 0 )
        || ( selectedCandidate && selectedCandidate.possibleUnits && selectedCandidate.possibleUnits.length > 0 )
        || modalError;

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
                    className="wprm-ai-review-inline-match"
                    value={ candidateId }
                    onChange={ (e) => {
                        if ( SEARCH_MATCH_VALUE === e.target.value ) {
                            openSearchModal();
                            return;
                        }

                        updateField( 'candidateId', e.target.value );
                    } }
                    disabled={ saving || exclude }
                    title={ __wprm( 'Match' ) }
                >
                    <option value="">{ __wprm( 'No match' ) }</option>
                    {
                        options.map( ( candidate ) => (
                            <option key={ candidate.id } value={ candidate.id }>
                                { candidate.name }{ candidate.aisle ? ` (${ candidate.aisle })` : '' }
                            </option>
                        ) )
                    }
                    <option value={ SEARCH_MATCH_VALUE }>{ __wprm( 'Search...' ) }</option>
                </select>
                <input
                    className="wprm-ai-review-inline-amount"
                    type="text"
                    value={ amount }
                    onChange={ (e) => updateField( 'amount', e.target.value ) }
                    disabled={ saving || exclude }
                    title={ __wprm( 'Amount' ) }
                    placeholder={ __wprm( 'Amt' ) }
                />
                <input
                    className="wprm-ai-review-inline-unit"
                    type="text"
                    value={ unit }
                    onChange={ (e) => updateField( 'unit', e.target.value ) }
                    disabled={ saving || exclude }
                    title={ __wprm( 'Unit' ) }
                    placeholder={ __wprm( 'Unit' ) }
                />
                <Tooltip content={ exclude ? __wprm( 'This ingredient is excluded from the nutrition calculation. Click to include it again.' ) : __wprm( 'Click to exclude this ingredient from the nutrition calculation.' ) }>
                    <button
                        type="button"
                        className={ `wprm-ai-review-inline-exclude${ exclude ? ' is-excluded' : '' }` }
                        onClick={ () => { if ( ! saving ) updateField( 'exclude', ! exclude ); } }
                        disabled={ saving }
                    >
                        <Icon type={ exclude ? 'close' : 'eye' } />
                    </button>
                </Tooltip>
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
                    {
                        ingredient.ai_review && ingredient.ai_review.reason
                        &&
                        <p className="wprm-ai-review-help">{ ingredient.ai_review.reason }</p>
                    }
                    {
                        ingredient.unit_analysis && ingredient.unit_analysis.reasons && ingredient.unit_analysis.reasons.length > 0
                        &&
                        <p className="wprm-ai-review-help">
                            { ingredient.unit_analysis.reasons.join( ' ' ) }
                        </p>
                    }
                    {
                        selectedCandidate && selectedCandidate.possibleUnits && selectedCandidate.possibleUnits.length > 0
                        &&
                        <p className="wprm-ai-review-help">
                            { __wprm( 'Possible Spoonacular units:' ) } { selectedCandidate.possibleUnits.join( ', ' ) }
                        </p>
                    }
                    {
                        modalError
                        &&
                        <p className="wprm-ai-review-help">{ modalError }</p>
                    }
                </div>
            }
        </div>
    );
};

const RecipeCard = ( props ) => {
    const { batchId, result, onUpdated, onApplied } = props;
    const recipe = result.recipe || {};
    const canApply = [ 'ready_to_apply', 'suggest_update' ].includes( result.status ) && result.proposed_nutrition;

    const [ edits, setEdits ] = useState( {} );
    const [ saving, setSaving ] = useState( false );
    const [ applying, setApplying ] = useState( false );
    const [ requestError, setRequestError ] = useState( '' );
    const [ detailsExpanded, setDetailsExpanded ] = useState( result.status !== 'already_handled' );

    // Collapse details when result transitions to already_handled (e.g. after applying).
    useEffect( () => {
        if ( 'already_handled' === result.status ) {
            setDetailsExpanded( false );
        }
    }, [ result.status ] );

    // Clear saved edits when the result updates, but keep any remaining local changes.
    useEffect( () => {
        setRequestError( '' );
        setEdits( ( previousEdits ) => {
            const nextEdits = {};

            Object.keys( previousEdits ).forEach( ( index ) => {
                const ingredient = result.ingredients.find( ( ing ) => `${ ing.index }` === `${ index }` );

                if ( ingredient && isIngredientDirty( getOriginalValues( ingredient ), previousEdits[ index ] ) ) {
                    nextEdits[ index ] = previousEdits[ index ];
                }
            } );

            return nextEdits;
        } );
    }, [ result ] );

    const dirtyIndices = useMemo( () => {
        return Object.keys( edits ).filter( ( index ) => {
            const ingredient = result.ingredients.find( ( ing ) => `${ ing.index }` === `${ index }` );

            if ( ! ingredient ) {
                return false;
            }

            return isIngredientDirty( getOriginalValues( ingredient ), edits[ index ] );
        } );
    }, [ edits, result.ingredients ] );

    const hasDirtyIngredients = dirtyIndices.length > 0;

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
                const ingredient = result.ingredients.find( ( ing ) => `${ ing.index }` === `${ index }` );

                if ( ! ingredient ) {
                    continue;
                }

                // Resolve the candidate object for this edit.
                const options = getCandidateOptions( ingredient );
                const candidate = options.find( ( c ) => `${ c.id }` === edit.candidateId ) || edit.candidate || false;

                const response = await Api.aiAssistant.saveNutritionReviewDecision( batchId, recipe.id, ingredient.index, {
                    candidateId: edit.candidateId,
                    candidate,
                    amount: edit.amount,
                    unit: edit.unit,
                    exclude: edit.exclude,
                    confirmed: edit.confirmed,
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
                    <p>{ formatNutritionSummary( result.proposed_nutrition ) }</p>
                </div>
                <span className={ `wprm-ai-review-badge is-${ result.status }` }>{ STATUS_LABELS[ result.status ] || result.status }</span>
            </div>
            {
                result.comparison && result.comparison.status
                &&
                <p className="wprm-ai-review-help">
                    {
                        'already_handled' === result.comparison.status
                            ? __wprm( 'This recipe has already been handled in this batch.' )
                            : 'no_change' === result.comparison.status
                                ? __wprm( 'The proposed nutrition is effectively the same as the currently stored nutrition.' )
                                : __wprm( 'The proposed nutrition differs from the currently stored nutrition.' )
                    }
                </p>
            }
            {
                result.totals_review && result.totals_review.reason
                &&
                <p className="wprm-ai-review-help">
                    { result.totals_review.reason }
                </p>
            }
            {
                result.flags && result.flags.length > 0
                &&
                <p className="wprm-ai-review-help">
                    { __wprm( 'Review notes:' ) } { result.flags.map( formatResultFlag ).filter( Boolean ).join( ' ' ) }
                </p>
            }
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
                        const grouped = result.ingredients.filter( ( ingredient ) => ingredient.status === status );

                        if ( 0 === grouped.length ) {
                            return null;
                        }

                        return (
                            <div key={ status } className="wprm-ai-review-ingredient-group">
                                <div className={ `wprm-ai-review-group-header is-${ status }` }>
                                    <span className={ `wprm-ai-review-status-dot is-${ status }` }></span>
                                    { INGREDIENT_STATUS_LABELS[ status ] || status } ({ grouped.length })
                                </div>
                                { renderIngredientGroup( grouped ) }
                            </div>
                        );
                    } )
                }
                {
                    // Catch any ingredients with unexpected statuses.
                    result.ingredients.filter( ( ingredient ) => ! INGREDIENT_STATUS_ORDER.includes( ingredient.status ) ).length > 0
                    &&
                    <div className="wprm-ai-review-ingredient-group">
                        <div className="wprm-ai-review-group-header">
                            { __wprm( 'Other' ) } ({ result.ingredients.filter( ( ingredient ) => ! INGREDIENT_STATUS_ORDER.includes( ingredient.status ) ).length })
                        </div>
                        { renderIngredientGroup( result.ingredients.filter( ( ingredient ) => ! INGREDIENT_STATUS_ORDER.includes( ingredient.status ) ) ) }
                    </div>
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
            <NutritionComparison
                proposedNutrition={ result.proposed_nutrition }
                existingNutrition={ result.existing_nutrition }
            />
            <div className="wprm-ai-review-apply-actions">
                <button
                    type="button"
                    className="button button-primary button-compact wprm-button-ai"
                    disabled={ ! canApply || saving || applying }
                    onClick={ async () => {
                        setApplying( true );
                        setRequestError( '' );

                        try {
                            const response = await Api.aiAssistant.applyNutritionReviewRecipe( batchId, recipe.id );

                            if ( response && response.result ) {
                                onApplied( response.result );
                            } else {
                                throw new Error( __wprm( 'Could not apply the proposed nutrition to this recipe.' ) );
                            }
                        } catch ( error ) {
                            setRequestError( getApiErrorMessage( error, __wprm( 'Could not apply the proposed nutrition to this recipe. Please try again.' ) ) );
                        } finally {
                            setApplying( false );
                        }
                    } }
                >
                    <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                    { applying ? __wprm( 'Applying...' ) : __wprm( 'Apply to Recipe' ) }
                </button>
            </div>
            </> }
        </div>
    );
};

const NutritionReview = () => {
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
            const request = id ? Api.aiAssistant.getNutritionReviewBatch( id ) : Api.aiAssistant.getCurrentNutritionReviewBatch();
            const response = await request;

            setBatch( response && response.batch ? response.batch : null );
            setError( '' );
        } catch ( loadError ) {
            setError( getApiErrorMessage( loadError, __wprm( 'Could not load the nutrition review batch. Please refresh and try again.' ) ) );
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
    const visibleResults = 'all' === filter ? results : results.filter( (result) => result.status === filter );

    const updateOneResult = ( updatedResult ) => {
        setBatch( ( currentBatch ) => {
            if ( ! currentBatch ) {
                return currentBatch;
            }

            const nextResults = currentBatch.results.map( (result) => result.recipe.id === updatedResult.recipe.id ? updatedResult : result );

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
            const response = await Api.aiAssistant.startNutritionReviewBatch( options );

            if ( response && response.batch ) {
                setBatch( response.batch );
                window.history.replaceState( {}, '', response.url || window.location.href );
            } else {
                throw new Error( __wprm( 'Could not start the nutrition review batch.' ) );
            }
        } catch ( startError ) {
            setError( getApiErrorMessage( startError, __wprm( 'Could not start the nutrition review batch. Please try again.' ) ) );
        } finally {
            setStarting( false );
        }
    };

    return (
        <div className="wprm-ai-review">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Nutrition Review' ) }</h2>
                <p>{ __wprm( 'Review ingredient matches and units with AI, then let Spoonacular calculate the nutrition facts. Recipes with uncertain matches or suspicious totals stay in the review queue.' ) }</p>
            </div>
            <div className="wprm-ai-generate-ideas-configure">
                <div className="wprm-ai-generate-ideas-field">
                    <div className="wprm-ai-generate-ideas-field-header">
                        <h3>{ __wprm( 'Start a review batch' ) }</h3>
                        <p>
                            { __wprm( 'Use these options to review recipes with missing nutrition or recheck everything. For a custom selection, launch it using the "Bulk Edit" column on the ' ) }
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
                            onClick={ () => startBatch( { scope: 'all_recipes', force_review: true } ) }
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
                                        Object.keys( STATUS_LABELS ).map( (status) => (
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
                                        disabled={ applyingReady || ! batch || 0 === ( batch.counts.ready_to_apply + batch.counts.suggest_update ) }
                                        onClick={ async () => {
                                            setApplyingReady( true );
                                            setError( '' );

                                            try {
                                                const response = await Api.aiAssistant.applyNutritionReviewReady( batch.id );

                                                if ( response && response.batch ) {
                                                    setBatch( response.batch );
                                                } else {
                                                    throw new Error( __wprm( 'Could not apply the ready recipes in this batch.' ) );
                                                }
                                            } catch ( applyError ) {
                                                setError( getApiErrorMessage( applyError, __wprm( 'Could not apply the ready recipes in this batch. Please try again.' ) ) );
                                            } finally {
                                                setApplyingReady( false );
                                            }
                                        } }
                                    >
                                        <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                                        { applyingReady ? __wprm( 'Applying...' ) : __wprm( 'Apply Ready Recipes' ) }
                                    </button>
                                </div>
                            </div>
                            {
                                visibleResults.map( (result) => (
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
                            <p>{ __wprm( 'No nutrition review batch has been started yet.' ) }</p>
                        </div>
                    }
                </Fragment>
            }
        </div>
    );
};

export default NutritionReview;
