import React, { useEffect } from 'react';

import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';
import Loader from 'Shared/Loader';

const GeneratingStep = ( props ) => {
    useEffect( () => {
        let cancelled = false;

        Api.aiAssistant.generateIdeas( props.options ).then( ( response ) => {
            if ( cancelled ) {
                return;
            }

            if ( response && response.success && response.ideas ) {
                props.onIdeasGenerated( response.ideas, response.context_recipes || [] );
            } else {
                const message = response && response.message ? response.message : __wprm( 'Failed to generate ideas. Please try again.' );
                alert( message );
                props.onError();
            }
        } ).catch( () => {
            if ( ! cancelled ) {
                alert( __wprm( 'Failed to generate ideas. Please try again.' ) );
                props.onError();
            }
        } );

        return () => {
            cancelled = true;
        };
    }, [] );

    return (
        <div className="wprm-ai-generate-ideas-generating">
            <Loader />
            <p>{ __wprm( 'Generating ideas...' ) }</p>
            <p className="wprm-ai-generate-ideas-generating-note">{ __wprm( 'This may take a moment.' ) }</p>
        </div>
    );
};

export default GeneratingStep;
