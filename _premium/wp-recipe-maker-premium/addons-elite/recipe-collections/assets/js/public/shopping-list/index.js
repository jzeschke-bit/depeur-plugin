import { createRoot } from 'react-dom/client';
import React from 'react';
import { HashRouter } from 'react-router-dom';

import ScrollToTop from '../../shared/ScrollToTop';
import App from './App';

let appContainers = document.getElementsByClassName( 'wprm-shopping-list-app' );
let existingAppContainer = document.getElementById( 'wprm-recipe-collections-app' );

if (  ! existingAppContainer ) {
	// Get all container IDs first.
	let allContainerIds = [];
	for ( let appContainer of appContainers ) {
		const id = appContainer.dataset.id;
		allContainerIds.push( id );
	}

	// Init all containers.
	for ( let appContainer of appContainers ) {
		const id = appContainer.dataset.id;

		const allContainerIdsWithoutOwn = allContainerIds.filter( a => a !== id );
		const root = createRoot(appContainer);
		root.render(
			<HashRouter
				basename={ `/shopping-list-${id}` }
				hashType="noslash"
			>
				<ScrollToTop
					ignoreIds={ allContainerIdsWithoutOwn }
					element={ appContainer }
				/>
				<App
					id={ id }
				/>
			</HashRouter>
		);
	}
}