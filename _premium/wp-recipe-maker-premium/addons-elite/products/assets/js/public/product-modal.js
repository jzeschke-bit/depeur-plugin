/**
 * Product Modal functionality.
 *
 * @since 10.2.0
 */

import '../../css/public/product-modal.scss';
import { __wprm } from 'Shared/Translations';

const ProductModal = {
	init() {
		this.addModalHandlers();
		this.addModalEventListeners();
	},

	addModalHandlers() {
		// Handle clicks on "Add Products to Cart" buttons
		document.addEventListener('click', (e) => {
			const target = e.target.closest('.wprm-recipe-add-products-to-cart');
			if (target && target.dataset.modalUid) {
				e.preventDefault();
				const modalUid = target.dataset.modalUid;
				const recipeId = target.dataset.recipeId;
				
				// Open the modal using WPRM modal system
				if (window.WPRecipeMaker && window.WPRecipeMaker.modal) {
					window.WPRecipeMaker.modal.open(modalUid, {
						recipe: recipeId
					});
				}
			}
		});
	},

	addModalEventListeners() {
		// Listen for modal open events
		document.addEventListener('wprm-modal-open', (e) => {
			if (e.detail.type === 'products') {
				this.initializeModal(e.detail.modal);
			}
		});

		// Listen for modal close events to clean up
		document.addEventListener('wprm-modal-close', (e) => {
			if (e.detail.type === 'products') {
				this.cleanupModal(e.detail.modal);
			}
		});
	},

	initializeModal(modal) {
		// Store event listener references for cleanup
		modal._wprmpEventListeners = [];
		
		// Update button text with initial count
		this.updateButtonText();
		
		// Add event listeners for checkboxes and quantity inputs
		this.addProductEventListeners(modal);
		
		// Check serving size changes for ingredients
		this.checkServingSizeChanges(modal);
		
		// Add event listener for the add to cart button
		this.addAddToCartListener(modal);
		
		// Ensure the Go to Cart button is reset for a new session
		this.resetGoToCartButton(modal);
		
		// Add cart summary to footer
		this.addCartSummaryToFooter(modal);
		
		// Load cart summary
		this.loadCartSummary(modal);
	},

	cleanupModal(modal) {
		// Remove all stored event listeners
		if (modal._wprmpEventListeners) {
			modal._wprmpEventListeners.forEach(({ element, event, handler }) => {
				element.removeEventListener(event, handler);
			});
			modal._wprmpEventListeners = [];
		}
	},

	addProductEventListeners(modal) {
		const modalContent = modal.querySelector('.wprm-popup-modal__content');
		if (!modalContent) return;

		// Handle checkbox changes
		const checkboxHandler = (e) => {
			if (e.target.classList.contains('wprmp-product-select')) {
				this.handleProductSelection(e.target);
			}
		};
		modalContent.addEventListener('change', checkboxHandler);
		modal._wprmpEventListeners.push({ element: modalContent, event: 'change', handler: checkboxHandler });

		// Handle quantity input changes
		const quantityHandler = (e) => {
			if (e.target.classList.contains('wprmp-quantity-input')) {
				this.handleQuantityChange(e.target);
			}
		};
		modalContent.addEventListener('input', quantityHandler);
		modal._wprmpEventListeners.push({ element: modalContent, event: 'input', handler: quantityHandler });
	},

	handleProductSelection(checkbox) {
		const productItem = checkbox.closest('.wprmp-product-item');
		if (productItem) {
			if (checkbox.checked) {
				productItem.classList.remove('wprmp-product-disabled');
			} else {
				productItem.classList.add('wprmp-product-disabled');
			}
		}
		this.updateButtonText();
	},

	handleQuantityChange(input) {
		// Ensure quantity is at least 1
		if (parseInt(input.value) < 1) {
			input.value = 1;
		}
	},

	addAddToCartListener(modal) {
		const addButton = modal.querySelector('.wprmp-add-products-to-cart-btn');
		if (!addButton) return;

		const clickHandler = () => {
			this.handleAddToCart(modal);
		};
		addButton.addEventListener('click', clickHandler);
		modal._wprmpEventListeners.push({ element: addButton, event: 'click', handler: clickHandler });
	},

	handleAddToCart(modal) {
		const selectedProducts = this.getSelectedProducts(modal);
		
		if (selectedProducts.length === 0) {
			alert(__wprm('Please select at least one product to add to cart.'));
			return;
		}

		// Show loading state
		const button = modal.querySelector('.wprmp-add-products-to-cart-btn');
		const originalText = button.textContent;
		button.textContent = __wprm('Adding to Cart...');
		button.disabled = true;

		// Add products to cart
		this.addProductsToCart(selectedProducts)
			.then(() => {
				// Remove the original event listener to prevent duplicate handlers
				const originalListener = modal._wprmpEventListeners.find(listener => 
					listener.element === button && listener.event === 'click'
				);
				if (originalListener) {
					button.removeEventListener('click', originalListener.handler);
					// Remove from our tracking array
					modal._wprmpEventListeners = modal._wprmpEventListeners.filter(listener => 
						!(listener.element === button && listener.event === 'click')
					);
				}
				
				// Convert main button into a close action
				this.configureButtonAsClose(modal, button);
				
				// Enable the Go to Cart button now that products were added
				this.enableGoToCartButton(modal);
				
				// Update cart summary
				this.loadCartSummary(modal);
			})
			.catch((error) => {
				console.error('Add to cart error:', error);
				alert(__wprm('Failed to add products to cart. Please try again.'));
				button.textContent = originalText;
				button.disabled = false;
			});
	},

	getSelectedProducts(modal) {
		const products = [];
		const productItems = modal.querySelectorAll('.wprmp-product-item');
		
		productItems.forEach((item, index) => {
			const checkbox = item.querySelector('.wprmp-product-select');
			if (checkbox && checkbox.checked) {
				const productId = item.dataset.productId;
				const variationId = item.dataset.variationId;
				const quantityInput = item.querySelector('.wprmp-quantity-input');
				const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
				
				products.push({
					product_id: productId,
					variation_id: variationId,
					quantity: quantity
				});
			}
		});
		
		return products;
	},

	async addProductsToCart(products) {
		// Process products sequentially to avoid race conditions
		for (const product of products) {
			await this.addSingleProductToCart(product);
		}
	},

	async addSingleProductToCart(product) {
		const formData = new FormData();
		formData.append('action', 'wprmp_add_product_to_cart');
		formData.append('product_id', product.product_id);
		formData.append('quantity', product.quantity);
		if (product.variation_id) {
			formData.append('variation_id', product.variation_id);
		}

		// Add nonce if available
		if (typeof wprm_public !== 'undefined' && wprm_public.api_nonce) {
			formData.append('_wpnonce', wprm_public.api_nonce);
		}

		const response = await fetch(wprm_public.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		});

		const data = await response.json();
		
		if (!data.success) {
			throw new Error(data.data.message || 'Failed to add product to cart');
		}

		return data;
	},

	checkServingSizeChanges(modal) {
		// Get recipe ID from the modal or its parent elements
		const recipeId = this.getRecipeIdFromModal(modal);
		
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
						
						// Update quantities for ingredient products only
						const ingredientProducts = modal.querySelectorAll('.wprmp-product-item[data-type="ingredient"]');
						
						ingredientProducts.forEach(productItem => {
							const originalAmount = parseFloat(productItem.dataset.originalAmount);
							
							if (originalAmount && multiplier !== 1) {
								// Calculate new amount and round up to integer
								const newAmount = Math.ceil(originalAmount * multiplier);
								
								// Update the quantity input
								const quantityInput = productItem.querySelector('.wprmp-quantity-input');
								if (quantityInput) {
									quantityInput.value = newAmount;
								}
							}
						});
					}
				}
			});
		}
	},

	getRecipeIdFromModal(modal) {
		// Try to get recipe ID from the modal itself
		let recipeId = modal.dataset.recipeId;
		
		// If not found, look in parent elements
		if (!recipeId) {
			for (let parent = modal.parentNode; parent && parent != document; parent = parent.parentNode) {
				if (parent.matches('.wprm-recipe-container')) {
					recipeId = parent.dataset.recipeId;
					break;
				}
			}
		}
		
		return recipeId;
	},

	updateButtonText() {
		// Find all modals with products
		const modals = document.querySelectorAll('.wprm-popup-modal[data-type="products"]');
		
		modals.forEach(modal => {
			const button = modal.querySelector('.wprmp-add-products-to-cart-btn');
			if (!button) return;
			
			const checkedBoxes = modal.querySelectorAll('.wprmp-product-select:checked');
			const count = checkedBoxes.length;
			
			if (count === 1) {
				button.textContent = __wprm('Add 1 Product to Cart');
			} else {
				button.textContent = __wprm('Add %d Products to Cart').replace('%d', count);
			}
			
			// Disable button if no products are selected
			if (count === 0) {
				button.disabled = true;
			} else {
				button.disabled = false;
			}
		});
	},

	addCartSummaryToFooter(modal) {
		const footer = modal.querySelector('.wprm-popup-modal__footer');
		if (!footer) return;

		// Check if cart summary already exists
		if (footer.querySelector('.wprmp-modal-cart-summary')) return;

		// Create cart summary element
		const cartSummary = document.createElement('div');
		cartSummary.className = 'wprmp-modal-cart-summary';
		cartSummary.innerHTML = `<div class="wprmp-product-tooltip-cart-summary"><span class="wprmp-cart-count">${__wprm('Loading cart...')}</span></div>`;
		
		// Add it to the footer
		footer.appendChild(cartSummary);
	},

	loadCartSummary(modal) {
		const cartCountElement = modal.querySelector('.wprmp-cart-count');
		
		if (!cartCountElement) return Promise.resolve(null);

		// Show loading state
		cartCountElement.textContent = __wprm('Loading cart...');

		// Get fresh cart data
		return this.getCartData().then(cartData => {
			this.displayCartSummary(modal, cartCountElement, cartData);
			return cartData;
		}).catch(error => {
			console.error('Failed to load cart data:', error);
			cartCountElement.textContent = __wprm('Unable to load cart');
			return null;
		});
	},

	displayCartSummary(modal, cartCountElement, cartData) {
		if (cartData && cartData.item_count > 0) {
			const totalText = cartData.total ? ` - ${cartData.total}` : '';
			const cartLinkText = __wprm('your cart');
			const cartUrl = cartData.cart_url || '#';
			const cartText = cartUrl !== '#' 
				? __wprm('%d in %s').replace('%d', cartData.item_count).replace('%s', `<a href="${cartUrl}" class="wprmp-cart-link">${cartLinkText}</a>`)
				: __wprm('%d in %s').replace('%d', cartData.item_count).replace('%s', cartLinkText);
			cartCountElement.innerHTML = cartText + totalText;
			if (modal) {
				modal.dataset.cartUrl = cartUrl;
			}
		} else {
			cartCountElement.textContent = __wprm('Cart is empty');
			if (modal) {
				modal.dataset.cartUrl = '#';
			}
		}
	},

	getCartData() {
		return new Promise((resolve, reject) => {
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
				return response.text();
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

	configureButtonAsClose(modal, button) {
		button.textContent = __wprm('Close Modal');
		button.classList.add('wprmp-success');
		button.disabled = false;
		button.onclick = () => {
			if (window.WPRecipeMaker && window.WPRecipeMaker.modal) {
				window.WPRecipeMaker.modal.close(modal.id.replace('wprm-popup-modal-', ''));
			}
		};
	},

	resetGoToCartButton(modal) {
		const goToCartButton = modal.querySelector('.wprmp-go-to-cart-btn');
		if (!goToCartButton) return;

		goToCartButton.disabled = true;
		goToCartButton.classList.remove('wprmp-success');
		goToCartButton.onclick = null;
		goToCartButton.setAttribute('hidden', 'hidden');
	},

	enableGoToCartButton(modal) {
		const goToCartButton = modal.querySelector('.wprmp-go-to-cart-btn');
		if (!goToCartButton) return;

		const cartUrl = goToCartButton.dataset.cartUrl;

		if (!cartUrl || cartUrl === '#') {
			return;
		}

		goToCartButton.removeAttribute('hidden');
		goToCartButton.disabled = false;
		goToCartButton.classList.add('wprmp-success');
		goToCartButton.onclick = () => {
			window.location.href = cartUrl;
		};
	}
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => ProductModal.init());
} else {
	ProductModal.init();
}

export default ProductModal;
