import React, { useState } from 'react';

import { __wprm } from 'Shared/Translations';

const contextOptions = [
    {
        value: 'popular',
        label: __wprm( 'Most Popular Recipes' ),
        description: __wprm( 'Based on your recipes with the most ratings. Great for creating more of what your audience loves.' ),
    },
    {
        value: 'highest_rated',
        label: __wprm( 'Highest Rated Recipes' ),
        description: __wprm( 'Based on your top-rated recipes. Focus on quality content that resonates with your readers.' ),
    },
    {
        value: 'recent',
        label: __wprm( 'Most Recent Recipes' ),
        description: __wprm( 'Based on your latest recipes. Continue in the direction of your current content.' ),
    },
    {
        value: 'all',
        label: __wprm( 'All of Your Recipes' ),
        description: __wprm( 'Analyzes your full catalog to find gaps and suggest content you haven\'t covered yet.' ),
    },
];

const examplePrompts = {
    recipe: {
        popular: [
            __wprm( 'More recipes similar to my most popular ones' ),
            __wprm( 'Variations and twists on my best performers' ),
            __wprm( 'Recipes that complement my existing popular content' ),
            __wprm( 'Seasonal versions of my top recipes' ),
            __wprm( 'Quick and easy versions inspired by my hits' ),
        ],
        highest_rated: [
            __wprm( 'More of the style my readers rate highly' ),
            __wprm( 'Similar flavor profiles to my best-rated recipes' ),
            __wprm( 'Recipes that match the quality my audience expects' ),
            __wprm( 'New takes on the cuisines my readers love most' ),
        ],
        recent: [
            __wprm( 'Continue the theme of my recent content' ),
            __wprm( 'Fill gaps in my recent recipe coverage' ),
            __wprm( 'Complementary recipes to pair with my latest posts' ),
            __wprm( 'Related recipes my readers might search for next' ),
        ],
        all: [
            __wprm( 'Cuisines or categories I haven\'t covered yet' ),
            __wprm( 'Recipes that fill gaps in my existing content' ),
            __wprm( 'Popular recipe types that are missing from my site' ),
            __wprm( 'Complementary recipes my readers might expect to find' ),
        ],
    },
    list: [
        __wprm( 'Roundup ideas that group my existing recipes in new ways' ),
        __wprm( 'Collection topics I haven\'t created yet' ),
        __wprm( 'Best-of lists covering categories across my site' ),
        __wprm( 'Seasonal recipe collections from my catalog' ),
        __wprm( 'Quick weeknight dinner roundups from my recipes' ),
    ],
};

const ConfigureStep = ( props ) => {
    const [ options, setOptions ] = useState( { ...props.options } );

    const updateField = ( field, value ) => {
        setOptions( ( prev ) => ( { ...prev, [ field ]: value } ) );
    };

    const handleSubmit = ( e ) => {
        e.preventDefault();
        props.onGenerate( options );
    };

    const selectedContext = contextOptions.find( ( o ) => o.value === options.context ) || contextOptions[0];
    const currentExamples = options.type === 'list'
        ? examplePrompts.list
        : ( examplePrompts.recipe[ options.context ] || examplePrompts.recipe.popular );

    return (
        <div className="wprm-ai-generate-ideas-configure">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Generate Ideas' ) }</h2>
                <p>{ __wprm( 'Generate new content ideas based on your existing recipes and what performs best on your site.' ) }</p>
            </div>
            <form onSubmit={ handleSubmit }>
                <div className="wprm-ai-generate-ideas-field">
                    <label>{ __wprm( 'What type of content are you looking for?' ) }</label>
                    <div className="wprm-ai-generate-ideas-pills">
                        <button
                            type="button"
                            className={ `wprm-ai-pill${ 'recipe' === options.type ? ' is-selected' : '' }` }
                            onClick={ () => updateField( 'type', 'recipe' ) }
                        >
                            { __wprm( 'Recipe Ideas' ) }
                        </button>
                        <button
                            type="button"
                            className={ `wprm-ai-pill${ 'list' === options.type ? ' is-selected' : '' }` }
                            onClick={ () => updateField( 'type', 'list' ) }
                        >
                            { __wprm( 'Roundup / List Ideas' ) }
                        </button>
                    </div>
                </div>
                { options.type === 'recipe' && (
                    <div className="wprm-ai-generate-ideas-field">
                        <label>{ __wprm( 'Base suggestions on' ) }</label>
                        <div className="wprm-ai-generate-ideas-context-options">
                            { contextOptions.map( ( option ) => (
                                <label
                                    key={ option.value }
                                    className={ `wprm-ai-context-option${ option.value === options.context ? ' is-selected' : '' }` }
                                >
                                    <input
                                        type="radio"
                                        name="context"
                                        value={ option.value }
                                        checked={ option.value === options.context }
                                        onChange={ () => updateField( 'context', option.value ) }
                                    />
                                    <div className="wprm-ai-context-option-content">
                                        <strong>{ option.label }</strong>
                                        <span>{ option.description }</span>
                                    </div>
                                </label>
                            ) ) }
                        </div>
                    </div>
                ) }
                { options.type === 'list' && (
                    <div className="wprm-ai-generate-ideas-field">
                        <p className="wprm-ai-generate-ideas-list-note">
                            { __wprm( 'List ideas will be based on all of your existing recipes, suggesting ways to group them into compelling roundup posts.' ) }
                        </p>
                    </div>
                ) }
                <div className="wprm-ai-generate-ideas-field">
                    <label>{ __wprm( 'Additional instructions (optional)' ) }</label>
                    <textarea
                        className="wprm-ai-generate-ideas-textarea"
                        value={ options.prompt }
                        onChange={ ( e ) => updateField( 'prompt', e.target.value ) }
                        placeholder={ __wprm( 'Any specific direction or preferences...' ) }
                        rows={ 2 }
                    />
                    <div className="wprm-ai-generate-ideas-examples">
                        { currentExamples.map( ( example ) => (
                            <button
                                key={ example }
                                type="button"
                                className="wprm-ai-example-prompt"
                                onClick={ () => updateField( 'prompt', example ) }
                            >
                                { example }
                            </button>
                        ) ) }
                    </div>
                </div>
                <div className="wprm-ai-generate-ideas-actions">
                    <button type="submit" className="button button-primary button-compact wprm-button-ai">
                        <span className="wprm-ai-assistant-icon" aria-hidden="true"></span>
                        { __wprm( 'Generate Ideas' ) }
                    </button>
                </div>
            </form>
        </div>
    );
};

export default ConfigureStep;
