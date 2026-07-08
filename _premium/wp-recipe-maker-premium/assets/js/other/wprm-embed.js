/**
 * WP Recipe Maker - Minimal Embed Script
 * 
 * This script allows anyone to embed recipes from your site with a simple script tag.
 * Usage: <script src="https://yoursite.com/wp-content/plugins/wp-recipe-maker-premium/assets/js/other/wprm-embed.js" data-recipe-id="123"></script>
 */

(function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        // Default settings
        defaultWidth: '100%',
        defaultHeight: 'auto',
        defaultTemplate: '',
        defaultStyle: '',
        
        // API endpoint (will be set dynamically)
        apiBase: '',
        
        // Error messages
        errors: {
            noRecipeId: 'Recipe ID is required',
            loadFailed: 'Failed to load recipe',
            invalidResponse: 'Invalid response from server'
        }
    };
    
    /**
     * Get the current script element to extract configuration
     */
    function getCurrentScript() {
        const scripts = document.getElementsByTagName('script');
        return scripts[scripts.length - 1];
    }
    
    /**
     * Extract configuration from script tag data attributes
     */
    function getConfig() {
        const script = getCurrentScript();
        if (!script) return null;
        
        // Get the base URL from the script src
        const scriptSrc = script.src;
        let baseUrl;
        
        // Handle different URL patterns
        try {
            if (scriptSrc.includes('?wprm_embed_script=1')) {
                // Script served from /?wprm_embed_script=1
                const url = new URL(scriptSrc);
                baseUrl = url.origin;
            } else if (scriptSrc.includes('/assets/js/other/wprm-embed.js')) {
                // Direct file access
                baseUrl = scriptSrc.replace('/assets/js/other/wprm-embed.js', '');
            } else {
                // Fallback: extract origin from URL
                const url = new URL(scriptSrc);
                baseUrl = url.origin;
            }
        } catch (e) {
            console.error('WPRM Embed URL parsing error:', e);
            // Fallback: try to extract origin manually
            const match = scriptSrc.match(/^(https?:\/\/[^\/]+)/);
            baseUrl = match ? match[1] : window.location.origin;
        }
        
        CONFIG.apiBase = baseUrl + '/wp-json/wp-recipe-maker/v1/embed';
        
        return {
            recipeId: script.getAttribute('data-recipe-id'),
            width: script.getAttribute('data-width') || CONFIG.defaultWidth,
            height: script.getAttribute('data-height') || CONFIG.defaultHeight,
            template: script.getAttribute('data-template') || CONFIG.defaultTemplate,
            style: script.getAttribute('data-style') || CONFIG.defaultStyle,
            container: script.getAttribute('data-container') || 'wprm-embed-' + Date.now(),
            passkey: script.getAttribute('data-passkey'),
            signature: script.getAttribute('data-signature'),
            timestamp: script.getAttribute('data-timestamp')
        };
    }
    
    /**
     * Create a container element for the recipe
     */
    function createContainer(config) {
        let container;
        
        if (config.container.startsWith('#')) {
            // Use existing element by ID
            container = document.getElementById(config.container.substring(1));
        } else if (config.container.startsWith('.')) {
            // Use existing element by class (first match)
            container = document.querySelector(config.container);
        } else {
            // Create new element
            container = document.createElement('div');
            container.id = config.container;
        }
        
        if (!container) {
            return null;
        }
        
        // Set container styles
        container.style.width = config.width;
        container.style.height = config.height;
        container.style.maxWidth = '100%';
        container.style.margin = '0 auto';
        container.className = 'wprm-embed-container';
        
        // If this is a new container, insert it into the DOM
        if (!config.container.startsWith('#') && !config.container.startsWith('.')) {
            // Try to find the script tag and insert after it
            const script = document.querySelector(`script[data-recipe-id="${config.recipeId}"]`);
            
            if (script && script.parentNode) {
                script.parentNode.insertBefore(container, script.nextSibling);
            } else {
                document.body.appendChild(container);
            }
        }
        
        return container;
    }
    
    /**
     * Build the API URL
     */
    function buildApiUrl(config) {
        const url = new URL(CONFIG.apiBase + '/' + config.recipeId);
        
        // Add optional parameters
        if (config.template) {
            url.searchParams.set('template', config.template);
        }
        if (config.style) {
            url.searchParams.set('style', config.style);
        }
        
        // Add authentication parameters if provided
        if (config.passkey) {
            url.searchParams.set('passkey', config.passkey);
        }
        if (config.signature) {
            url.searchParams.set('signature', config.signature);
        }
        if (config.timestamp) {
            url.searchParams.set('timestamp', config.timestamp);
        }
        
        // Request JSON format for better control
        url.searchParams.set('format', 'json');
        
        return url.toString();
    }
    
    /**
     * Load and inject CSS
     */
    function injectCSS(css) {
        if (!css) return;
        
        // Check if we already have WPRM styles
        let styleElement = document.getElementById('wprm-embed-styles');
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'wprm-embed-styles';
            document.head.appendChild(styleElement);
        }
        
        styleElement.textContent = css;
    }
    
    /**
     * Load and execute JavaScript
     */
    function injectJS(js) {
        if (!js) return;
        
        // Check if we already have WPRM scripts
        let scriptElement = document.getElementById('wprm-embed-script');
        if (!scriptElement) {
            scriptElement = document.createElement('script');
            scriptElement.id = 'wprm-embed-script';
            document.head.appendChild(scriptElement);
        }
        
        scriptElement.textContent = js;
    }
    
    /**
     * Handle errors
     */
    function handleError(error, config) {
        
        const container = document.getElementById(config.container);
        if (container) {
            container.innerHTML = `
                <div style="
                    padding: 20px;
                    border: 1px solid #e0e0e0;
                    border-radius: 4px;
                    background: #f9f9f9;
                    color: #666;
                    text-align: center;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                ">
                    <p>Recipe could not be loaded</p>
                    <small>Error: ${error}</small>
                    <br><small>API Base: ${CONFIG.apiBase}</small>
                </div>
            `;
        }
    }
    
    /**
     * Load the recipe
     */
    function loadRecipe(config) {
        const container = createContainer(config);
        if (!container) {
            handleError('Container not found', config);
            return;
        }
        
        // Show loading state
        container.innerHTML = `
            <div style="
                padding: 20px;
                text-align: center;
                color: #666;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                Loading recipe...
            </div>
        `;
        
        // Make API request
        const apiUrl = buildApiUrl(config);
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    // Handle specific authentication errors
                    if (response.status === 401) {
                        throw new Error('Authentication required. Please check your embed API settings or provide authentication parameters.');
                    } else if (response.status === 403) {
                        throw new Error('Embed API is disabled. Please enable it in WP Recipe Maker settings.');
                    } else if (response.status === 404) {
                        throw new Error('Recipe not found. Please check the recipe ID.');
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data || !data.html) {
                    throw new Error(CONFIG.errors.invalidResponse);
                }
                
                // Inject CSS and JS
                injectCSS(data.css);
                injectJS(data.js);
                
                // Set the recipe HTML
                container.innerHTML = data.html;
                
                // Trigger any initialization that might be needed
                if (window.wprm_public && window.wprm_public.init) {
                    window.wprm_public.init();
                }
            })
            .catch(error => {
                handleError(error.message, config);
            });
    }
    
    /**
     * Initialize the embed
     */
    function init() {
        const config = getConfig();
        if (!config) {
            return;
        }
        
        if (!config.recipeId) {
            handleError(CONFIG.errors.noRecipeId, config);
            return;
        }
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => loadRecipe(config));
        } else {
            loadRecipe(config);
        }
    }
    
    // Start the embed
    init();
    
})();
