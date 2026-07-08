import { useState, useEffect } from "react";
import { useLocation } from "react-router-dom";

export default function ScrollToTop( props ) {
    // Only if setting enabled.
    if ( ! wprmp_public.settings.recipe_collections_scroll_to_top ) {
        return null;
    }

    const [ firstLoad, setFirstLoad ] = useState( true );
    const { pathname } = useLocation();
    const { element, ignoreIds } = props;

    const offset = wprmp_public.settings.recipe_collections_scroll_to_top_offset;
    const y = element.getBoundingClientRect().top + window.scrollY - offset;

    useEffect(() => {
        if ( ! firstLoad ) {
            if ( ignoreIds ) {
                for ( let id of ignoreIds ) {
                    const pathStart = `/collection-${id}`;

                    // If path starts with this it's for another saved collection, so we should not scroll (the other collection will do that).
                    if ( pathStart === pathname.substr( 0, pathStart.length ) ) {
                        return;
                    }
                }
            }
            
            window.scrollTo(0, y);
        } else {
            setFirstLoad( false );
        }
    }, [ pathname ]);

    return null;
}