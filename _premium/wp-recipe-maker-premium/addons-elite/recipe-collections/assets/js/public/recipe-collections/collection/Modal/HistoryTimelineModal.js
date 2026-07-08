import React from 'react';

import { __wprm } from 'Shared/Translations';

const formatTimestamp = ( timestamp ) => {
    if ( ! timestamp ) {
        return false;
    }

    const date = new Date( timestamp );
    if ( isNaN( date.getTime() ) ) {
        return false;
    }

    const hours = `${ date.getHours() }`.padStart( 2, '0' );
    const minutes = `${ date.getMinutes() }`.padStart( 2, '0' );
    const seconds = `${ date.getSeconds() }`.padStart( 2, '0' );

    return `${ hours }:${ minutes }:${ seconds }`;
};

const HistoryTimelineModal = ( props ) => {
    const entries = props.historyEntries ? props.historyEntries : [];
    const cursor = undefined !== props.historyCursor ? props.historyCursor : -1;
    const initialTimestamp = formatTimestamp( props.historyInitialTimestamp );

    const actionEntries = entries.map( ( entry, index ) => {
        const fallbackTimestamp = entry.uid && 'string' === typeof entry.uid
            ? parseInt( entry.uid.split( '-' )[0], 10 )
            : false;

        return {
            index,
            label: entry.label,
            timestamp: formatTimestamp( entry.timestamp ? entry.timestamp : fallbackTimestamp ),
        };
    } );

    const timelineEntries = [
        ...actionEntries.reverse(),
        {
            index: -1,
            label: __wprm( 'Initial state' ),
            timestamp: initialTimestamp,
        },
    ];

    return (
        <div className="wprm-recipe-collections-history-modal-content">
            <div className="wprm-recipe-collections-history-modal-description">
                { __wprm( 'Select a point in time to revert this collection to that state.' ) }
            </div>
            <div className="wprm-recipe-collections-history-modal-list">
                {
                    timelineEntries.map( ( entry ) => {
                        const isActive = entry.index === cursor;

                        return (
                            <button
                                type="button"
                                key={ `history-timeline-${ entry.index }` }
                                className={ `wprm-recipe-collections-history-modal-item${ isActive ? ' wprm-recipe-collections-history-modal-item-active' : '' }` }
                                disabled={ isActive }
                                onClick={ () => props.onHistoryJump( entry.index ) }
                            >
                                <span className="wprm-recipe-collections-history-modal-item-time">
                                    { entry.timestamp ? entry.timestamp : '--:--:--' }
                                </span>
                                <span className="wprm-recipe-collections-history-modal-item-label">
                                    { entry.label }
                                </span>
                            </button>
                        );
                    } )
                }
            </div>
        </div>
    );
};

export default HistoryTimelineModal;
