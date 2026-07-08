import { createRoot } from 'react-dom/client';
import React from 'react';
import { HashRouter } from 'react-router-dom';

import ScrollToTop from '../../shared/ScrollToTop';
import App from './App';

let appContainer = document.getElementById( 'wprm-recipe-collections-app' );

if (appContainer) {
	const modalUid = appContainer.dataset.modalUid ? appContainer.dataset.modalUid : false;
	const root = createRoot(appContainer);
	root.render(
		<HashRouter
			hashType="noslash"
		>
			<ScrollToTop
				pathStart='/'
				element={ appContainer }
			/>
			<App
				modal={ modalUid }
			/>
		</HashRouter>
	);
}