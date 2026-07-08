import React, { useState } from 'react';

import '../../css/admin/ai-assistant/app.scss';

import ConfigureStep from './steps/ConfigureStep';
import GeneratingStep from './steps/GeneratingStep';
import ReviewStep from './steps/ReviewStep';
import ImportedStep from './steps/ImportedStep';
import ImportWithAI from './tools/ImportWithAI';
import NutritionReview from './tools/NutritionReview';
import UnitConversionReview from './tools/UnitConversionReview';

const App = ( props ) => {
    if ( 'import_with_ai' === props.tool ) {
        return <ImportWithAI />;
    }

    if ( 'nutrition_review' === props.tool ) {
        return <NutritionReview />;
    }

    if ( 'unit_conversion_review' === props.tool ) {
        return <UnitConversionReview />;
    }

    const [ step, setStep ] = useState( 'configure' );
    const [ options, setOptions ] = useState( {
        type: 'recipe',
        context: 'popular',
        prompt: '',
    } );
    const [ generatedIdeas, setGeneratedIdeas ] = useState( [] );
    const [ contextRecipes, setContextRecipes ] = useState( [] );
    const [ importResult, setImportResult ] = useState( null );

    const onGenerate = ( formOptions ) => {
        setOptions( formOptions );
        setStep( 'generating' );
    };

    const onIdeasGenerated = ( ideas, contextRecipeNames ) => {
        setGeneratedIdeas( ideas );
        setContextRecipes( contextRecipeNames || [] );
        setStep( 'review' );
    };

    const onReconfigure = () => {
        setStep( 'configure' );
    };

    const onIdeasAppended = ( newIdeas ) => {
        setGeneratedIdeas( ( prev ) => [ ...prev, ...newIdeas ] );
    };

    const onGenerateError = () => {
        setStep( 'configure' );
    };

    const onImported = ( result ) => {
        setImportResult( result );
        setStep( 'imported' );
    };

    const onGenerateMore = () => {
        setGeneratedIdeas( [] );
        setImportResult( null );
        setStep( 'configure' );
    };

    return (
        <div className="wprm-ai-generate-ideas">
            { 'configure' === step && (
                <ConfigureStep
                    options={ options }
                    onGenerate={ onGenerate }
                />
            ) }
            { 'generating' === step && (
                <GeneratingStep
                    options={ options }
                    onIdeasGenerated={ onIdeasGenerated }
                    onError={ onGenerateError }
                />
            ) }
            { 'review' === step && (
                <ReviewStep
                    ideas={ generatedIdeas }
                    options={ options }
                    contextRecipes={ contextRecipes }
                    onImported={ onImported }
                    onIdeasAppended={ onIdeasAppended }
                    onReconfigure={ onReconfigure }
                />
            ) }
            { 'imported' === step && (
                <ImportedStep
                    result={ importResult }
                    onGenerateMore={ onGenerateMore }
                />
            ) }
        </div>
    );
};

export default App;
