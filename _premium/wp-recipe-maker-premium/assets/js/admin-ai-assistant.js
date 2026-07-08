import { createRoot } from 'react-dom/client';
import React from 'react';

import App from './admin-ai-assistant/App';

let appContainer = document.getElementById( 'wprm-ai-assistant-tool' );

if (appContainer) {
	const root = createRoot(appContainer);
	root.render(
		<App tool={ appContainer.dataset.tool || 'generate_ideas' } />
	);
}
