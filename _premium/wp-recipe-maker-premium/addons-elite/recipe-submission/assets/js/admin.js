import { createRoot } from 'react-dom/client';
import React from 'react';

import Layout from './admin/Layout';

let layoutContainer = document.getElementById( 'wprmprs-layout' );

if (layoutContainer) {
	const root = createRoot(layoutContainer);
	root.render(<Layout/>);
}