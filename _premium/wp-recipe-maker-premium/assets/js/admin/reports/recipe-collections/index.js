
import { createRoot } from 'react-dom/client';
import React from 'react';

import App from './App';

let container = document.getElementById( 'wprm-reports-recipe-collections' );

if ( container ) {
	const root = createRoot( container );
    root.render(
        <App />
    );
}
