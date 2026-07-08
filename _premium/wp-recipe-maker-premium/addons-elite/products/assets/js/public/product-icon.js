/**
 * Product Icon Tooltip functionality.
 *
 * @since 8.6.0
 */

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

import '../../css/public/product-icon.scss';
import { __wprm } from 'Shared/Translations';

const ProductIcon = {
	// Global cart cache
	cartCache: null,
	cartCacheTime: null,
	cartCacheTimeout: 30000, // 30 seconds

	init() {
		this.addTooltips();
	},

	addTooltips() {
		const containers = document.querySelectorAll('.wprm-recipe-product-icon-container');

		for (let container of containers) {
			// Remove any existing tippy
			if (container._tippy) {
				container._tippy.destroy();
			}

			// Create tooltip content
			const tooltipContent = this.createTooltipContent(container);
			
			// Initialize tippy
			tippy(container, {
				content: tooltipContent,
				allowHTML: true,
				interactive: true,
				trigger: 'mouseenter focus',
				theme: 'wprm-products',
				placement: 'right',
				arrow: true,
				onShow(instance) {
					// Clean up any existing event listeners first
					ProductIcon.cleanupEventListeners(instance.popper);
					// Check serving size changes and adjust quantity
					ProductIcon.checkServingSizeChanges(instance.popper, container);
					// Initialize quantity controls
					ProductIcon.initQuantityControls(instance.popper);
					// Load cart summary
					ProductIcon.loadCartSummary(instance.popper);
				},
				onHide(instance) {
					// Clean up event listeners when tooltip is hidden
					ProductIcon.cleanupEventListeners(instance.popper);
				}
			});
		}
	},

	createTooltipContent(container) {
		const productId = container.dataset.productId;
		const productName = container.dataset.productName;
		const productPrice = container.dataset.productPrice;
		const productImage = container.dataset.productImage;
		const productUrl = container.dataset.productUrl;
		const productAmount = this.getRoundedQuantity(container.dataset.productAmount);
		const variationId = container.dataset.variationId;
		const variationName = container.dataset.variationName;

		let tooltipHtml = '<div class="wprmp-product-tooltip">';
		
		// Product image (clickable if URL available)
		if (productImage) {
			tooltipHtml += `<div class="wprmp-product-tooltip-image"><img src="${productImage}" alt="${productName}" /></div>`;
		}

		// Product info
		tooltipHtml += '<div class="wprmp-product-tooltip-content">';
		
		// Product name (clickable if URL available)
		if (productUrl) {
			tooltipHtml += `<div class="wprmp-product-tooltip-name"><a href="${productUrl}" target="_blank">${productName} <span class="wprmp-external-link-icon">↗</span></a></div>`;
		} else {
			tooltipHtml += `<div class="wprmp-product-tooltip-name">${productName}</div>`;
		}
		
		// Show variation if available
		if (variationName) {
			tooltipHtml += `<div class="wprmp-product-tooltip-variation">${variationName}</div>`;
		}
		
		if (productPrice) {
			tooltipHtml += `<div class="wprmp-product-tooltip-price">${productPrice}</div>`;
		}

		// Quantity and Add to Cart on same line
		tooltipHtml += '<div class="wprmp-product-tooltip-quantity-cart">';
		tooltipHtml += `<input type="number" class="wprmp-quantity-input" value="${productAmount}" min="1" />`;
		tooltipHtml += `<button type="button" class="wprmp-add-to-cart-btn" data-product-id="${productId}" data-variation-id="${variationId || ''}">${__wprm('Add to Cart')}</button>`;
		tooltipHtml += '</div>';

		// Cart summary section
		tooltipHtml += '<div class="wprmp-product-tooltip-cart-summary">';
		tooltipHtml += `<span class="wprmp-cart-count">${__wprm('Loading cart...')}</span>`;
		tooltipHtml += '</div>';

		tooltipHtml += '</div>';
		tooltipHtml += '</div>';

		return tooltipHtml;
	},

	getRoundedQuantity(amount) {
		const parsedAmount = parseFloat(amount);

		if (Number.isNaN(parsedAmount)) {
			return 1;
		}

		return Math.max(1, Math.ceil(parsedAmount));
	},

	initQuantityControls(tooltipElement) {
		const quantityInput = tooltipElement.querySelector('.wprmp-quantity-input');
		const addToCartBtn = tooltipElement.querySelector('.wprmp-add-to-cart-btn');

		// Add to cart functionality
		if (addToCartBtn) {
			const addToCartHandler = (e) => {
				e.preventDefault();
				// Get the original icon container from the tippy instance
				const originalIconContainer = tooltipElement._tippy.reference;
				this.handleAddToCart(addToCartBtn, quantityInput, originalIconContainer);
			};
			
			// Store the handler reference for cleanup
			addToCartBtn._addToCartHandler = addToCartHandler;
			addToCartBtn.addEventListener('click', addToCartHandler);
		}
	},

	cleanupEventListeners(tooltipElement) {
		const addToCartBtn = tooltipElement.querySelector('.wprmp-add-to-cart-btn');
		
		if (addToCartBtn && addToCartBtn._addToCartHandler) {
			addToCartBtn.removeEventListener('click', addToCartBtn._addToCartHandler);
			delete addToCartBtn._addToCartHandler;
		}
	},

	checkServingSizeChanges(tooltipElement, container) {
		// Only apply serving size changes to ingredients, not equipment
		const productType = container.dataset.productType;
		if (productType === 'equipment') {
			return;
		}

		// Get recipe ID from the container or its parent elements
		const recipeId = this.getRecipeIdFromElem(container);
		
		if (!recipeId) {
			return;
		}

		// Get the recipe data
		if (window.WPRecipeMaker && window.WPRecipeMaker.manager) {
			window.WPRecipeMaker.manager.getRecipe(recipeId).then(recipe => {
				if (recipe && recipe.data) {
					// Check if serving sizes have changed
					const currentServings = recipe.data.currentServingsParsed;
					const originalServings = recipe.data.originalServingsParsed;
					
					if (currentServings && originalServings && currentServings !== originalServings) {
						// Calculate multiplier
						const multiplier = currentServings / originalServings;
						
						// Get the original product amount from the container
						const originalAmount = parseFloat(container.dataset.productAmount);
						
						if (originalAmount && multiplier !== 1) {
						// Calculate new amount and ensure rounding matches everywhere else
						const newAmount = this.getRoundedQuantity(originalAmount * multiplier);
							
							// Update the quantity input in the tooltip
							const quantityInput = tooltipElement.querySelector('.wprmp-quantity-input');
							if (quantityInput) {
								quantityInput.value = newAmount;
							}
						}
					}
				}
			});
		}
	},

	getRecipeIdFromElem(elem) {
		let recipeId = elem.dataset.recipe;

		// Backwards compatibility - look for recipe container in parent elements
		if (!recipeId) {
			for (let parent = elem.parentNode; parent && parent != document; parent = parent.parentNode) {
				if (parent.matches('.wprm-recipe-container')) {
					recipeId = parent.dataset.recipeId;
					break;
				}
			}
		}

		return recipeId;
	},

	loadCartSummary(tooltipElement) {
		const cartCountElement = tooltipElement.querySelector('.wprmp-cart-count');
		
		if (!cartCountElement) return;

		// Check if we have valid cached data
		if (this.cartCache && this.cartCacheTime && (Date.now() - this.cartCacheTime) < this.cartCacheTimeout) {
			this.displayCartSummary(cartCountElement, this.cartCache);
			return;
		}

		// Show loading state
		cartCountElement.textContent = __wprm('Loading cart...');

		// Get fresh cart data
		this.getCartData().then(cartData => {
			// Cache the data
			this.cartCache = cartData;
			this.cartCacheTime = Date.now();
			
			// Display the summary
			this.displayCartSummary(cartCountElement, cartData);
		}).catch(error => {
			console.error('Failed to load cart data:', error);
			cartCountElement.textContent = __wprm('Unable to load cart');
		});
	},

	displayCartSummary(cartCountElement, cartData) {
		if (cartData && cartData.item_count > 0) {
			const totalText = cartData.total ? ` - ${cartData.total}` : '';
			const cartLinkText = __wprm('your cart');
			const cartUrl = cartData.cart_url || '#';
			const cartText = cartUrl !== '#' 
				? __wprm('%d in %s').replace('%d', cartData.item_count).replace('%s', `<a href="${cartUrl}" class="wprmp-cart-link">${cartLinkText}</a>`)
				: __wprm('%d in %s').replace('%d', cartData.item_count).replace('%s', cartLinkText);
			cartCountElement.innerHTML = cartText + totalText;
		} else {
			cartCountElement.textContent = __wprm('Cart is empty');
		}
	},

	invalidateCartCache() {
		this.cartCache = null;
		this.cartCacheTime = null;
	},

	refreshAllCartSummaries() {
		// Find all visible tooltip cart summaries and refresh them
		const allTooltips = document.querySelectorAll('.wprmp-product-tooltip-cart-summary .wprmp-cart-count');
		allTooltips.forEach(cartCountElement => {
			this.loadCartSummary(cartCountElement.closest('.wprmp-product-tooltip'));
		});
	},

	getCartData() {
		return new Promise((resolve, reject) => {
			// Try our own API first (more reliable)
			const formData = new FormData();
			formData.append('action', 'wprmp_get_cart_data');
			
			if (typeof wprm_public !== 'undefined' && wprm_public.api_nonce) {
				formData.append('_wpnonce', wprm_public.api_nonce);
			}

			fetch(wprm_public.ajax_url, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			})
			.then(response => {
				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`);
				}
				return response.text(); // Get as text first to debug
			})
			.then(text => {
				try {
					const data = JSON.parse(text);
					if (data.success) {
						resolve(data.data);
					} else {
						reject(new Error(data.data.message || 'Failed to get cart data'));
					}
				} catch (e) {
					console.error('JSON parse error:', e);
					console.error('Response text:', text);
					reject(new Error('Invalid JSON response'));
				}
			})
			.catch(error => {
				console.error('Cart data fetch error:', error);
				// Fallback: return empty cart data
				resolve({
					item_count: 0,
					total: '',
					cart_url: '#'
				});
			});
		});
	},

	handleAddToCart(button, quantityInput, originalIconContainer) {
		const productId = button.dataset.productId;
		const variationId = button.dataset.variationId;
		const quantity = quantityInput.value;

		// Show loading state
		const originalText = button.textContent;
		button.style.minWidth = button.offsetWidth + 'px';
		button.textContent = __wprm('Adding...');
		button.disabled = true;

		// Prepare form data
		const formData = new FormData();
		formData.append('action', 'wprmp_add_product_to_cart');
		formData.append('product_id', productId);
		formData.append('quantity', quantity);
		if (variationId) {
			formData.append('variation_id', variationId);
		}

		// Add nonce if available
		if (typeof wprm_public !== 'undefined' && wprm_public.api_nonce) {
			formData.append('_wpnonce', wprm_public.api_nonce);
		}

		// Make AJAX request
		fetch(wprm_public.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Show success feedback
				button.textContent = __wprm('Added!');
				button.classList.add('wprmp-success');
				
				// Change icon to checkmark temporarily
				this.showSuccessIcon(button, originalIconContainer);
				
				// Invalidate cart cache and refresh all visible tooltips
				this.invalidateCartCache();
				this.refreshAllCartSummaries();
				
				// Reset button after 2 seconds
				setTimeout(() => {
					button.textContent = originalText;
					button.disabled = false;
					button.classList.remove('wprmp-success');
				}, 2000);
			} else {
				// Show error
				alert(data.data.message || 'Failed to add product to cart');
				button.textContent = originalText;
				button.disabled = false;
			}
		})
		.catch(error => {
			console.error('Add to cart error:', error);
			alert(__wprm('Something went wrong. Please try again.'));
			button.textContent = originalText;
			button.disabled = false;
		});
	},

	showSuccessIcon(button, iconContainer) {
		if (iconContainer) {
			const originalIcon = iconContainer.innerHTML;
			
			// Change to checkmark icon
			iconContainer.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"></polyline></svg>';
			iconContainer.classList.add('wprmp-success-icon');
			
			// Reset after 2 seconds
			setTimeout(() => {
				iconContainer.innerHTML = originalIcon;
				iconContainer.classList.remove('wprmp-success-icon');
			}, 2000);
		}
	}
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => ProductIcon.init());
} else {
	ProductIcon.init();
}

export default ProductIcon;
