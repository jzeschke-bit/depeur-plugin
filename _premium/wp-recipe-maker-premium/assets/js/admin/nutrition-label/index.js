import { createRoot } from 'react-dom/client';
import React from 'react';

import App from './App';

let layoutContainer = document.getElementById( 'wprmp-nutrition-label-layout' );

if (layoutContainer) {
	const root = createRoot(layoutContainer);
	root.render(<App/>);
}