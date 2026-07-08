import { createRoot } from 'react-dom/client';
import React from 'react';
import { HashRouter } from 'react-router-dom';

import App from './App';

let appContainer = document.getElementById( 'wprm-recipe-collections-manage-app' );

if (appContainer) {
	const modalUid = appContainer.dataset.modalUid ? appContainer.dataset.modalUid : false;
	const root = createRoot(appContainer);
	root.render(
		<HashRouter
			hashType="noslash"
		>
    	    <App
				modal={ modalUid }
			/>
  	    </HashRouter>
	);
}