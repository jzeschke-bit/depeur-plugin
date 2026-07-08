import React from 'react';

import { __wprm } from 'Shared/Translations';

const ImportedStep = ( props ) => {
    const result = props.result || {};
    const created = result.created || [];
    const duplicates = result.duplicates || [];
    const manageUrl = wprm_admin.manage_url + '#/idea';

    return (
        <div className="wprm-ai-generate-ideas-imported">
            <div className="wprm-ai-generate-ideas-header">
                <h2>{ __wprm( 'Ideas Imported' ) }</h2>
            </div>
            <div className="wprm-ai-generate-ideas-imported-summary">
                <p>
                    { created.length === 1
                        ? __wprm( '1 idea was successfully imported.' )
                        : created.length + ' ' + __wprm( 'ideas were successfully imported.' )
                    }
                </p>
                { duplicates.length > 0 && (
                    <div className="wprm-ai-generate-ideas-duplicates">
                        <p><strong>{ __wprm( 'Possible duplicates detected:' ) }</strong></p>
                        <ul>
                            { duplicates.map( ( dup, i ) => (
                                <li key={ i }>{ dup.name }</li>
                            ) ) }
                        </ul>
                        <p className="wprm-ai-generate-ideas-duplicates-note">
                            { __wprm( 'The ideas were still imported. You can review and remove duplicates on the Manage page.' ) }
                        </p>
                    </div>
                ) }
            </div>
            <div className="wprm-ai-generate-ideas-actions">
                <a href={ manageUrl } className="button button-primary button-compact">
                    { __wprm( 'View Ideas on Manage Page' ) }
                </a>
                <button type="button" className="button button-secondary button-compact" onClick={ props.onGenerateMore }>
                    { __wprm( 'Generate More Ideas' ) }
                </button>
            </div>
        </div>
    );
};

export default ImportedStep;
