function import_json( action, page, total ) {
	var data = {
		action: action,
		security: wprm_admin.nonce,
		page: page,
	};

	jQuery.post(wprm_admin.ajax_url, data, function(out) {
		if (out.success) {
			page++;
			update_progress_bar( page, total );

			if ( page < total ) {
				import_json( action, page, total );
			} else {
				jQuery('#wprm-tools-finished').show();
			}
		} else {
			// alert( 'Something went wrong. Please contact support.' );
		}
	}, 'json');
}

function getContrastClass(color) {
	var ctx = document.createElement('canvas').getContext('2d');
	ctx.fillStyle = color;
	var hex = ctx.fillStyle;
	var r = parseInt(hex.slice(1, 3), 16);
	var g = parseInt(hex.slice(3, 5), 16);
	var b = parseInt(hex.slice(5, 7), 16);
	var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
	return luminance > 0.5 ? 'wprm-progress-percentage-dark' : 'wprm-progress-percentage-light';
}

function update_progress_bar( page, total ) {
	var percentage = page / total * 100;
	var isComplete = percentage >= 100;
	jQuery('#wprm-tools-progress-bar').css('width', percentage + '%').toggleClass('wprm-progress-complete', isComplete);

	var label = jQuery('#wprm-tools-progress-container .wprm-progress-percentage');
	label.text(isComplete ? '100%' : percentage.toFixed(1) + '%');
	label.removeClass('wprm-progress-percentage-light wprm-progress-percentage-dark wprm-progress-percentage-complete');

	if (isComplete) {
		label.addClass('wprm-progress-percentage-complete');
	} else if (percentage >= 50) {
		var color = getComputedStyle(document.documentElement).getPropertyValue('--wp-admin-theme-color').trim() || '#3858e9';
		label.addClass(getContrastClass(color));
	}
};

jQuery(document).ready(function($) {
	// Import recipes.
	if( typeof window.wprm_import_json === 'object' && wprm_import_json.hasOwnProperty( 'pages' ) ) {
		import_json( 'wprm_import_json', 0, parseInt( wprm_import_json.pages ) );
	}

	// Import taxonomy terms.
	if( typeof window.wprm_import_taxonomies === 'object' && wprm_import_taxonomies.hasOwnProperty( 'pages' ) ) {
		import_json( 'wprm_import_taxonomies', 0, parseInt( wprm_import_taxonomies.pages ) );
	}

	// Import from Paprika.
	if( typeof window.wprm_import_paprika === 'object' && wprm_import_paprika.hasOwnProperty( 'pages' ) ) {
		import_json( 'wprm_import_paprika', 0, parseInt( wprm_import_paprika.pages ) );
	}

	// Import from SlickStream.
	if( typeof window.wprm_import_slickstream === 'object' && wprm_import_slickstream.hasOwnProperty( 'pages' ) ) {
		import_json( 'wprm_import_slickstream', 0, parseInt( wprm_import_slickstream.pages ) );
	}
});
