(function() {
	function init() {
		const button = document.getElementById('wprm-activate-license');
		if ( ! button ) {
			return;
		}

		button.addEventListener('click', function(e) {
			e.preventDefault();

			const container = button.closest('#wprm-activate-license-container');
			if ( ! container ) {
				return;
			}

			const input = container.querySelector('input.wprm-license');
			if ( ! input ) {
				return;
			}

			// Store original container HTML to restore on failure
			const originalContainerHTML = container.innerHTML;

			// Disable button
			button.disabled = true;
			
			// Create and insert spinner after button
			const spinner = document.createElement('span');
			spinner.className = 'wprm-admin-loader';
			spinner.style.marginLeft = '10px';
			button.parentNode.insertBefore(spinner, button.nextSibling);

			const setting = input.id;
			const license = input.value;
			
			// Extract product ID from setting (e.g., "license_premium" -> "premium")
			const productId = setting.replace('license_', '');

			// Include tracking preference if checkbox is present and checked.
			const trackingCheckbox = container.querySelector('input[name="license_allow_tracking"]');
			const allowTracking = trackingCheckbox && trackingCheckbox.checked ? true : null;

			const data = {
				product_id: productId,
				license_key: license,
			};

			// Only include allow_tracking if checkbox is present
			if ( null !== allowTracking ) {
				data.allow_tracking = allowTracking;
			}

			// Use the license API endpoint
			const licenseEndpoint = wprm_admin.endpoints.setting.replace('/setting', '/license/update');
			
			return fetch(licenseEndpoint, {
				method: 'POST',
				headers: {
					'X-WP-Nonce': wprm_admin.api_nonce,
					'Accept': 'application/json',
					'Content-Type': 'application/json',
				},
				credentials: 'same-origin',
				body: JSON.stringify(data),
			}).then(response => {
				// Remove spinner
				if ( spinner && spinner.parentNode ) {
					spinner.parentNode.removeChild(spinner);
				}
				
				if ( response.ok ) {
					return response.json().then(data => {
						// Status is returned immediately from the API
						if ( data.status !== undefined ) {
							displayLicenseStatusMessage(data.status, container, originalContainerHTML);
						} else {
							// Unknown status - restore form
							restoreForm(container, originalContainerHTML, '<p>License key saved. Please refresh the page to see the activation status.</p>');
						}
					});
				} else {
					// API error - restore form with error message
					restoreForm(container, originalContainerHTML, '<p>Something went wrong. Please try again later.</p>');
				}
			}).catch(error => {
				// Remove spinner and restore form on error
				if ( spinner && spinner.parentNode ) {
					spinner.parentNode.removeChild(spinner);
				}
				restoreForm(container, originalContainerHTML, '<p>Something went wrong. Please try again later.</p>');
			});
		});
	}

	function displayLicenseStatusMessage(status, container, originalContainerHTML) {
		let message = '';
		let className = 'updated';
		let isSuccess = false;
		
		if ( in_array(status, ['active', 'valid']) ) {
			message = '<p><strong>Success!</strong> Your license key has been activated successfully.</p>';
			className = 'updated';
			isSuccess = true;
		} else if ( 'expired' === status ) {
			message = '<p><strong>License Expired</strong> Your license key has expired. <a href="https://bootstrapped.ventures/account/" target="_blank">Renew your license</a> to keep receiving updates.</p>';
			className = 'error';
		} else if ( 'invalid_item_id' === status ) {
			message = '<p><strong>Invalid License</strong> The license key you activated is for a different WP Recipe Maker Bundle. <a href="https://help.bootstrapped.ventures/article/63-installing-wp-recipe-maker" target="_blank">Make sure the correct plugin file is installed</a>.</p>';
			className = 'error';
		} else if ( in_array(status, ['inactive', 'invalid']) ) {
			message = '<p><strong>License Inactive</strong> The license key could not be activated. <a href="https://help.bootstrapped.ventures/article/93-activating-your-license-key" target="_blank">Need help activating?</a></p>';
			className = 'error';
		} else {
			// Status is empty or unknown - activation might still be processing
			message = '<p>License key saved. Please refresh the page to see the activation status.</p>';
			className = 'updated';
		}
		
		if ( isSuccess ) {
			// Success - show message only
			container.className = className;
			container.innerHTML = message;
		} else {
			// Failure - restore form with error message
			restoreForm(container, originalContainerHTML, message);
		}
	}

	function restoreForm(container, originalContainerHTML, errorMessage) {
		// Restore the original form HTML
		container.innerHTML = originalContainerHTML;
		container.className = 'error';
		
		// Prepend error message before the form
		// Use insertAdjacentHTML to safely add the error message at the beginning
		const errorDiv = document.createElement('div');
		errorDiv.className = 'notice notice-error inline';
		errorDiv.innerHTML = errorMessage;
		
		// Insert at the beginning of the container
		if ( container.firstChild ) {
			container.insertBefore(errorDiv, container.firstChild);
		} else {
			container.appendChild(errorDiv);
		}
		
		// Re-initialize the button event listener
		init();
	}

	function in_array(needle, haystack) {
		return haystack.indexOf(needle) !== -1;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

