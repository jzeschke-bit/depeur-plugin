window.WPRMPremiumPrint = {
	init() {
        // Hide empty private notes.
        window.WPRecipeMaker.privateNotes.hideEmpty();

        // Handle adjustable servings and unit conversion.
        this.servingsInput = document.querySelector( '#wprm-print-servings' );
        this.initServingsInput();

        this.unitConversionChanger = document.querySelector( '#wprm-print-unit-conversion-container' );
        this.initUnitConversionChanger();
        
        // Check if initial servings passed along.
        if ( window.hasOwnProperty( 'wprmp_print_recipes' ) ) {
            this.setInitialServings( wprmp_print_recipes );
        }

        // On args change.
        document.addEventListener( 'wprmPrintArgs', () => {
            this.onArgsChange();
        });
    },
    onArgsChange() {
        const args = window.WPRMPrint.args;

        // Decouple to make sure everything is loaded. Do regular servings first, then advanced.
        setTimeout( () => {
            if ( args.hasOwnProperty( 'servings' ) ) {
                this.setServings( args.servings );
            }
        }, 100 );
        setTimeout( () => {
            if (  args.hasOwnProperty( 'system' ) ) {
                this.setSystem( args.system, true );
            }
        }, 200);
        setTimeout( () => {

            if ( args.hasOwnProperty( 'advancedServings' ) ) {
                this.setAdvancedServings( args.advancedServings );
            }
        }, 300);
    },
    servingsInput: false,
    initServingsInput() {
        if ( this.servingsInput ) {
            // On input change.
            this.servingsInput.addEventListener( 'change', () => {
                this.setServings( this.servingsInput.value );
            });

            // On click servings change.
            const servingsChangers = [ ...document.querySelectorAll( '.wprm-print-servings-change' )];

            for ( let servingsChanger of servingsChangers ) {
                // Event listener.
                servingsChanger.addEventListener( 'click', () => {
                    this.onClickServingsChange( servingsChanger );
                });
            }

            // Find servings unit in recipe.
            const recipeServingsUnitElem = document.querySelector( '.wprm-recipe-servings-unit' );

            if ( recipeServingsUnitElem ) {
                const recipeServingsUnit = recipeServingsUnitElem.innerText.trim();
                
                if ( recipeServingsUnit ) {
                    document.querySelector( '#wprm-print-servings-unit' ).innerText = recipeServingsUnit;
                }
            }
        }
    },
    onClickServingsChange( button ) {
        if ( this.servingsInput ) {
            let servingsValue = parseFloat( this.servingsInput.value );

            if ( button.classList.contains( 'wprm-print-servings-increment' ) ) {
                servingsValue++;
            } else {
                servingsValue--;
            }
            this.setServings( servingsValue );
        }
    },
    setServings( servings ) {
        // Make sure it's valid.
        servings = parseFloat( servings );
        servings = isNaN( servings ) || servings <= 0 ? false : servings;

        if ( false !== servings ) {
            if ( this.servingsInput ) {
                this.servingsInput.value = servings;
            }

            const recipes = window.WPRecipeMaker.manager.findRecipesOnPage();

            for ( let id of recipes ) {
                window.WPRecipeMaker.manager.getRecipe( id ).then( ( recipe ) => {
                    recipe.setServings( servings );
                } );
            }
        }
    },
    setAdvancedServings( servings ) {
        if ( false !== servings && window.WPRecipeMaker.hasOwnProperty( 'advancedServings' )) {
            const recipes = window.WPRecipeMaker.manager.findRecipesOnPage();

            for ( let id of recipes ) {
                window.WPRecipeMaker.manager.getRecipe( id ).then( ( recipe ) => {
                    recipe.setAdvancedServings( servings );
                } );
            }
        }
    },
    unitConversionChanger: false,
    initUnitConversionChanger() {
        if ( this.unitConversionChanger ) {
            const unitSystems = this.unitConversionChanger.querySelectorAll( '.wprm-unit-conversion' );

            // On click.
            for ( let unitSystem of unitSystems ) {
                unitSystem.addEventListener( 'click', () => {
                    this.setSystem( unitSystem.dataset.system );
                });
            }
        }
    },
    setSystem( system, initial = false ) {
        // Make sure it's valid.
        system = parseInt( system );
        system = isNaN( system ) || system < 0 ? false : system;

        if ( false !== system && window.WPRecipeMaker.hasOwnProperty( 'conversion' ) ) {
            const recipes = window.WPRecipeMaker.manager.findRecipesOnPage();

            for ( let id of recipes ) {
                const printContainer = document.querySelector( '#wprm-print-recipe-' + id );

                let originalRecipeId = false;
                if ( printContainer ) {
                    originalRecipeId = printContainer.dataset.hasOwnProperty( 'originalRecipeId' ) ? printContainer.dataset.originalRecipeId : false;
                }

                window.WPRecipeMaker.manager.getRecipe( id, originalRecipeId ).then( ( recipe ) => {
                    if ( recipe ) {
                        if ( system !== recipe.data.currentSystem ) {
                            recipe.setUnitSystem( system );
                        }
                    }
                } );
            }

            if ( this.unitConversionChanger ) {
                const unitSystems = this.unitConversionChanger.querySelectorAll( '.wprm-unit-conversion' );
                for ( let unitSystem of unitSystems ) {
                    unitSystem.classList.remove( 'wprmpuc-active');

                    if ( system === parseInt( unitSystem.dataset.system ) ) {
                        unitSystem.classList.add( 'wprmpuc-active' );
                    }
                }
            }
        }
    },
    setInitialServings( recipes ) {
        // Need to do after timeout to make sure the servings have been initialized.
        for ( let i = 0; i < recipes.length; i++ ) {
            let recipe = recipes[i];

            if ( recipe.servings && recipe.original_servings && recipe.servings !== recipe.original_servings ) {
                // Need to do after timeout to make sure the servings have been initialized. Need multiple timeouts and reset if same recipe is used multiple times with different servings.
                setTimeout( () => {
                    window.WPRecipeMaker.manager.resetRecipe( recipe.id );
                    window.WPRecipeMaker.manager.getRecipe( recipe.id, recipe.recipe_id ).then( ( recipeObj ) => {
                        recipeObj.setServings( recipe.servings );
                    } );
                }, 100 * i );
            }
        }
    },
};
document.addEventListener( 'wprmPrintInit', () => {
    window.WPRMPremiumPrint.init();
} );

const wprmpPdfPrintMethods = {
    pdfGenerator: false,
    pdfGenerating: false,
    autoPdfTriggered: false,
    autoPdfTimeout: false,
    pdfButtonOriginalText: false,
    setPdfGenerator( pdfGenerator ) {
        this.pdfGenerator = pdfGenerator;
    },
    getPdfGenerator() {
        let generator = this.pdfGenerator;
        if ( generator && 'function' !== typeof generator && generator.default ) {
            generator = generator.default;
        }

        return 'function' === typeof generator ? generator : false;
    },
    isPdfDownloadPage() {
        const body = document.querySelector( 'body' );
        return !! body && body.classList.contains( 'wprm-print-pdf-download' );
    },
    async onClickPdf() {
        if ( this.isPdfDownloadPage() ) {
            await this.downloadPdfOnPage();
            return;
        }

        await this.openPdfDownloadPage();
    },
    async openPdfDownloadPage() {
        if ( this.pdfGenerating ) {
            return;
        }

        const recipeId = this.getCurrentRecipeId();
        if ( ! recipeId ) {
            return;
        }

        const target = window.hasOwnProperty( 'wprm_print_settings' ) && window.wprm_print_settings.print_new_tab ? '_blank' : '_self';
        const liveArgs = await this.getCurrentRecipePrintArgs( recipeId );
        const printArgs = {
            id: recipeId,
            system: 1,
            servings: false,
            advancedServings: false,
            ...this.args,
            ...liveArgs,
            output: 'pdf',
        };
        localStorage.setItem( 'wprmPrintArgs', JSON.stringify( printArgs ) );

        const pdfWindow = this.openPdfDownloadPlaceholder( target );

        this.pdfGenerating = true;
        this.setPdfLoadingState( true );

        try {
            const url = await this.getPdfDownloadUrl( recipeId, this.getCurrentPdfTemplate() );
            if ( ! url ) {
                this.showPdfDownloadError( pdfWindow );
                return;
            }

            if ( '_blank' === target && pdfWindow && ! pdfWindow.closed ) {
                pdfWindow.location = url;
                pdfWindow.focus();
            } else if ( '_blank' === target ) {
                const openedWindow = window.open( url, '_blank' );

                if ( ! openedWindow ) {
                    window.location = url;
                }
            } else {
                window.open( url, '_self' );
            }
        } finally {
            this.pdfGenerating = false;
            this.setPdfLoadingState( false );
        }
    },
    async getCurrentRecipePrintArgs( recipeId ) {
        const args = {
            system: 1,
            servings: false,
            advancedServings: false,
        };

        if ( ! recipeId || ! window.WPRecipeMaker || ! window.WPRecipeMaker.manager || 'function' !== typeof window.WPRecipeMaker.manager.getRecipe ) {
            return args;
        }

        try {
            const recipe = await window.WPRecipeMaker.manager.getRecipe( recipeId );

            if ( ! recipe || ! recipe.data ) {
                return args;
            }

            if ( recipe.data.hasOwnProperty( 'currentSystem' ) ) {
                const system = parseInt( recipe.data.currentSystem );
                if ( ! isNaN( system ) && 0 <= system ) {
                    args.system = system;
                }
            }

            if (
                recipe.data.hasOwnProperty( 'currentServingsParsed' )
                && recipe.data.hasOwnProperty( 'originalServingsParsed' )
                && recipe.data.currentServingsParsed !== recipe.data.originalServingsParsed
            ) {
                args.servings = recipe.data.currentServingsParsed;
            }

            if ( recipe.data.hasOwnProperty( 'currentAdvancedServings' ) ) {
                args.advancedServings = recipe.data.currentAdvancedServings;
            }

            return args;
        } catch ( e ) {
            return args;
        }
    },
    getCurrentRecipeId() {
        if ( this.args && this.args.hasOwnProperty( 'id' ) ) {
            const recipeId = parseInt( this.args.id );
            if ( recipeId ) {
                return recipeId;
            }
        }

        const firstRecipe = document.querySelector( '#wprm-print-recipe-0' );
        if ( firstRecipe && firstRecipe.dataset && firstRecipe.dataset.hasOwnProperty( 'recipeId' ) ) {
            const recipeId = parseInt( firstRecipe.dataset.recipeId );
            if ( recipeId ) {
                return recipeId;
            }
        }

        return false;
    },
    getCurrentPdfTemplate() {
        const pdfButton = document.querySelector( '#wprm-print-button-pdf' );
        if ( pdfButton && pdfButton.dataset && pdfButton.dataset.hasOwnProperty( 'template' ) ) {
            return pdfButton.dataset.template;
        }

        return '';
    },
    async getPdfDownloadUrl( recipeId, template = '' ) {
        if ( ! window.hasOwnProperty( 'wprm_print_settings' ) || ! window.wprm_print_settings.pdf_download_enabled ) {
            return false;
        }

        if ( ! window.wprm_print_settings.utilities_endpoint || ! window.wprm_print_settings.nonce ) {
            return false;
        }

        const body = {
            recipeId,
            nonce: window.wprm_print_settings.nonce,
        };

        if ( template ) {
            body.template = template;
        }

        try {
            const response = await fetch( `${window.wprm_print_settings.utilities_endpoint}/pdf-download-url`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify( body ),
            } );

            if ( ! response.ok ) {
                return false;
            }

            const result = await response.json();
            return result && result.url ? result.url : false;
        } catch ( e ) {
            return false;
        }
    },
    showPdfDownloadError( pdfWindow = false ) {
        if ( pdfWindow && ! pdfWindow.closed ) {
            try {
                pdfWindow.document.title = 'PDF Download';
                pdfWindow.document.body.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;padding:20px;text-align:center;">Could not generate the PDF download URL. Please try again.</div>';
            } catch ( e ) {}
        }
    },
    openPdfDownloadPlaceholder( target = '_blank' ) {
        if ( '_blank' !== target ) {
            return false;
        }

        const pdfWindow = window.open( '', '_blank' );
        if ( ! pdfWindow || pdfWindow.closed ) {
            return false;
        }

        try {
            pdfWindow.document.title = 'Preparing PDF';
            pdfWindow.document.body.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;padding:20px;text-align:center;">Preparing PDF Download...</div>';
        } catch ( e ) {}

        return pdfWindow;
    },
    maybeClosePdfDownloadWindow() {
        if ( ! this.isPdfDownloadPage() ) {
            return;
        }

        setTimeout( () => {
            try {
                window.close();
            } catch ( e ) {}
        }, 1000 );
    },
    async downloadPdfOnPage() {
        if ( this.pdfGenerating ) {
            return;
        }

        const printContent = document.getElementById( 'wprm-print-content' );
        if ( ! printContent ) {
            return;
        }

        const generator = this.getPdfGenerator();
        if ( ! generator ) {
            return;
        }

        this.pdfGenerating = true;
        this.setPdfLoadingState( true );
        let pdfGenerated = false;

        try {
            await this.waitForPdfAssets( printContent );
            await this.generatePdf( generator, printContent );
            pdfGenerated = true;
        } catch ( error ) {
            // Keep this silent for visitors, but expose detail for debugging.
            // eslint-disable-next-line no-console
            console.error( 'WPRM PDF generation failed', error );
        } finally {
            this.pdfGenerating = false;
            this.setPdfLoadingState( false );
        }

        if ( pdfGenerated ) {
            this.maybeClosePdfDownloadWindow();
        }
    },
    setPdfLoadingState( loading ) {
        const body = document.querySelector( 'body' );
        if ( body ) {
            body.classList.toggle( 'wprm-print-pdf-generating', loading );
        }

        const pdfButton = document.querySelector( '#wprm-print-button-pdf' );
        if ( ! pdfButton ) {
            return;
        }

        if ( false === this.pdfButtonOriginalText ) {
            this.pdfButtonOriginalText = pdfButton.textContent;
        }

        pdfButton.classList.toggle( 'wprm-print-button-loading', loading );
        pdfButton.setAttribute( 'aria-disabled', loading ? 'true' : 'false' );
        pdfButton.setAttribute( 'aria-busy', loading ? 'true' : 'false' );

        if ( loading ) {
            pdfButton.textContent = 'Generating PDF...';
        } else if ( false !== this.pdfButtonOriginalText ) {
            pdfButton.textContent = this.pdfButtonOriginalText;
        }
    },
    async generatePdf( generator, printContent ) {
        const getBaseOptions = () => {
            return {
                margin: [ 10, 10, 10, 10 ],
                filename: this.getPdfFilename(),
                image: {
                    type: 'jpeg',
                    quality: 0.98,
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    scrollY: 0,
                    imageTimeout: 15000,
                    backgroundColor: '#ffffff',
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait',
                },
                pagebreak: {
                    mode: [ 'css', 'legacy' ],
                    avoid: [
                        'img',
                        'svg',
                        'figure',
                        '.wprm-recipe-summary',
                        '.wprm-recipe-notes-container',
                        '.wprm-recipe-ingredient',
                        '.wprm-recipe-ingredient-details',
                        '.wprm-recipe-ingredient-details-container',
                        '.wprm-recipe-equipment-item',
                        '.wprm-recipe-equipment-name',
                        '.wprm-recipe-equipment-image',
                        '.wprm-recipe-equipment-affiliate-html',
                        '.wprm-recipe-instruction',
                        '.wprm-recipe-instructions li',
                        '.wprm-recipe-instruction-text',
                        '.wprm-recipe-instruction-image',
                        '.wprm-recipe-image',
                        '.wprm-recipe-ingredient-image',
                        '.wprm-recipe-instruction-media',
                        '.wprm-recipe-video-container',
                        '.wprm-recipe-tip',
                        '.wprm-recipe-instruction-tip',
                    ],
                },
            };
        };

        try {
            await generator().set( getBaseOptions() ).from( printContent ).save();
        } catch ( error ) {
            if ( ! this.isUnsupportedColorError( error ) ) {
                throw error;
            }

            // eslint-disable-next-line no-console
            console.warn( 'WPRM PDF: retrying with stylesheet sanitization fallback' );

            const fallbackOptions = getBaseOptions();
            fallbackOptions.html2canvas = {
                ...fallbackOptions.html2canvas,
                foreignObjectRendering: true,
                onclone: ( clonedDoc ) => {
                    this.inlineComputedStylesAndStripStylesheets( clonedDoc );
                    this.sanitizeUnsupportedColorFunctions( clonedDoc );
                },
            };

            try {
                await generator().set( fallbackOptions ).from( printContent ).save();
            } catch ( fallbackError ) {
                if ( ! this.isUnsupportedColorError( fallbackError ) ) {
                    throw fallbackError;
                }

                // eslint-disable-next-line no-console
                console.warn( 'WPRM PDF: retrying with hard inline-style fallback' );

                const hardFallbackOptions = getBaseOptions();
                hardFallbackOptions.html2canvas = {
                    ...hardFallbackOptions.html2canvas,
                    foreignObjectRendering: true,
                    onclone: ( clonedDoc ) => {
                        this.forceSanitizeUnsupportedColorFunctions( clonedDoc );
                        this.sanitizeUnsupportedColorFunctions( clonedDoc );
                    },
                };

                await generator().set( hardFallbackOptions ).from( printContent ).save();
            }
        }
    },
    maybeAutoDownloadPdf() {
        if ( this.autoPdfTriggered ) {
            return;
        }

        const outputRequestsPdf = this.args && this.args.hasOwnProperty( 'output' ) && 'pdf' === this.args.output;
        const pageRequiresAutoPdf = window.hasOwnProperty( 'wprm_print_settings' ) && window.wprm_print_settings.auto_pdf_download;

        if ( this.isPdfDownloadPage() && ( outputRequestsPdf || pageRequiresAutoPdf ) ) {
            this.autoPdfTriggered = true;
            clearTimeout( this.autoPdfTimeout );
            this.autoPdfTimeout = setTimeout( () => {
                this.downloadPdfOnPage();
            }, 700 );
        }
    },
    async waitForPdfAssets( printContent ) {
        const fontPromise = document.fonts && document.fonts.ready ? document.fonts.ready.catch( () => {} ) : Promise.resolve();
        const images = [ ...printContent.querySelectorAll( 'img' ) ];

        // Lazy-loaded images might not start loading until user scrolls.
        // Force eager loading so PDF generation can start immediately.
        for ( const img of images ) {
            try {
                img.loading = 'eager';
            } catch ( e ) {}

            const dataSrc = img.getAttribute( 'data-src' );
            const dataSrcSet = img.getAttribute( 'data-srcset' );
            const dataSizes = img.getAttribute( 'data-sizes' );

            if ( dataSrc && img.getAttribute( 'src' ) !== dataSrc ) {
                img.setAttribute( 'src', dataSrc );
            }
            if ( dataSrcSet && img.getAttribute( 'srcset' ) !== dataSrcSet ) {
                img.setAttribute( 'srcset', dataSrcSet );
            }
            if ( dataSizes && img.getAttribute( 'sizes' ) !== dataSizes ) {
                img.setAttribute( 'sizes', dataSizes );
            }
        }

        const imagePromises = images.map( ( img ) => {
            return new Promise( ( resolve ) => {
                if ( img.complete ) {
                    resolve();
                    return;
                }

                img.addEventListener( 'load', resolve, { once: true } );
                img.addEventListener( 'error', resolve, { once: true } );
            });
        });

        await Promise.all( [ fontPromise, ...imagePromises ] );
    },
    getPdfFilename() {
        let title = document.title ? document.title : 'recipe';
        title = title.replace( /\s+/g, ' ' ).trim();
        title = title.replace( /[\\/:*?"<>|]+/g, '-' );

        return `${ title || 'recipe' }.pdf`;
    },
    isUnsupportedColorError( error ) {
        const message = error && error.message ? error.message : '';
        return -1 !== message.indexOf( 'unsupported color function "color"' );
    },
    hasCssColorFunction( cssText ) {
        if ( ! cssText ) {
            return false;
        }

        return /\bcolor\s*\(/i.test( cssText );
    },
    sanitizeCssColorFunctions( cssText ) {
        if ( ! this.hasCssColorFunction( cssText ) ) {
            return cssText;
        }

        let output = '';
        let cursor = 0;
        const lowerCssText = cssText.toLowerCase();

        while ( cursor < cssText.length ) {
            const match = /\bcolor\s*\(/ig;
            match.lastIndex = cursor;
            const nextMatch = match.exec( cssText );
            const start = nextMatch ? nextMatch.index : -1;

            if ( -1 === start ) {
                output += cssText.substring( cursor );
                break;
            }

            output += cssText.substring( cursor, start );

            const openParen = lowerCssText.indexOf( '(', start );
            if ( -1 === openParen ) {
                output += cssText.substring( start );
                break;
            }

            let depth = 0;
            let end = openParen;

            for ( ; end < cssText.length; end++ ) {
                const char = cssText[ end ];
                if ( '(' === char ) {
                    depth++;
                } else if ( ')' === char ) {
                    depth--;
                    if ( 0 === depth ) {
                        end++;
                        break;
                    }
                }
            }

            // Replace unsupported color() function with a safe fallback.
            output += '#000000';
            cursor = end > openParen ? end : openParen + 1;
        }

        return output;
    },
    getFallbackValueForColorProperty( property ) {
        if ( -1 !== property.indexOf( 'image' ) ) {
            return 'none';
        }

        if ( -1 !== property.indexOf( 'shadow' ) ) {
            return 'none';
        }

        if ( -1 !== property.indexOf( 'background' ) ) {
            return 'transparent';
        }

        return '#000000';
    },
    sanitizeUnsupportedColorFunctions( clonedDoc ) {
        const createColorConverter = () => {
            try {
                const canvas = clonedDoc.createElement( 'canvas' );
                const context = canvas.getContext( '2d' );

                if ( context ) {
                    return ( colorValue ) => {
                        if ( ! colorValue ) {
                            return '#000000';
                        }

                        try {
                            context.fillStyle = '#000000';
                            context.fillStyle = colorValue;
                            const converted = context.fillStyle;

                            if ( converted && -1 === converted.indexOf( 'color(' ) ) {
                                return converted;
                            }
                        } catch ( e ) {}

                        return '#000000';
                    };
                }
            } catch ( e ) {}

            return () => '#000000';
        };
        const toSafeColor = createColorConverter();
        const replaceColorFunctions = ( value ) => {
            if ( ! this.hasCssColorFunction( value ) ) {
                return value;
            }

            return value.replace( /color\s*\([^)]*\)/gi, ( match ) => toSafeColor( match ) );
        };

        const elements = [ ...clonedDoc.querySelectorAll( '*' ) ];
        for ( const elem of elements ) {
            const styles = clonedDoc.defaultView.getComputedStyle( elem );

            for ( let i = 0; i < styles.length; i++ ) {
                const property = styles[ i ];
                const value = styles.getPropertyValue( property );

                if ( this.hasCssColorFunction( value ) ) {
                    let safeValue = replaceColorFunctions( value );

                    // Background images with advanced color functions can still break parsing.
                    if ( 'background-image' === property && safeValue && this.hasCssColorFunction( safeValue ) ) {
                        safeValue = 'none';
                    }

                    if ( safeValue && ! this.hasCssColorFunction( safeValue ) ) {
                        elem.style.setProperty( property, safeValue, 'important' );
                    }
                }
            }
        }
    },
    forceSanitizeUnsupportedColorFunctions( clonedDoc ) {
        const styles = [ ...clonedDoc.querySelectorAll( 'style' ) ];
        for ( const style of styles ) {
            if ( this.hasCssColorFunction( style.textContent ) ) {
                style.textContent = this.sanitizeCssColorFunctions( style.textContent );
            }
        }

        const styledElements = [ ...clonedDoc.querySelectorAll( '[style]' ) ];
        for ( const elem of styledElements ) {
            const styleAttr = elem.getAttribute( 'style' );

            if ( this.hasCssColorFunction( styleAttr ) ) {
                elem.setAttribute( 'style', this.sanitizeCssColorFunctions( styleAttr ) );
            }
        }
        const sanitizeRule = ( rule ) => {
            if ( ! rule ) {
                return;
            }

            if ( rule.style ) {
                for ( let i = 0; i < rule.style.length; i++ ) {
                    const property = rule.style[ i ];
                    const value = rule.style.getPropertyValue( property );

                    if ( this.hasCssColorFunction( value ) ) {
                        let safeValue = this.sanitizeCssColorFunctions( value );
                        if ( safeValue && this.hasCssColorFunction( safeValue ) ) {
                            safeValue = this.getFallbackValueForColorProperty( property );
                        }

                        if ( safeValue ) {
                            const priority = rule.style.getPropertyPriority( property );
                            rule.style.setProperty( property, safeValue, priority );
                        }
                    }
                }
            }

            if ( rule.cssRules && rule.cssRules.length ) {
                for ( const childRule of [ ...rule.cssRules ] ) {
                    sanitizeRule( childRule );
                }
            }
        };

        for ( const stylesheet of [ ...clonedDoc.styleSheets ] ) {
            let rules = false;

            try {
                rules = stylesheet.cssRules;
            } catch ( e ) {
                continue;
            }

            if ( rules && rules.length ) {
                for ( const rule of [ ...rules ] ) {
                    sanitizeRule( rule );
                }
            }
        }
    },
    inlineComputedStylesAndStripStylesheets( clonedDoc ) {
        if ( ! clonedDoc.defaultView ) {
            return;
        }

        const elements = [ ...clonedDoc.querySelectorAll( '*' ) ];
        for ( const elem of elements ) {
            const styles = clonedDoc.defaultView.getComputedStyle( elem );

            // Remove all inline declarations first so unsupported color() values
            // from inline custom properties cannot survive this hard fallback.
            if ( elem.hasAttribute( 'style' ) ) {
                elem.removeAttribute( 'style' );
            }

            for ( let i = 0; i < styles.length; i++ ) {
                const property = styles[ i ];
                const value = styles.getPropertyValue( property );

                if ( ! value ) {
                    continue;
                }

                let safeValue = this.hasCssColorFunction( value )
                    ? this.sanitizeCssColorFunctions( value )
                    : value;

                if ( safeValue && this.hasCssColorFunction( safeValue ) ) {
                    safeValue = this.getFallbackValueForColorProperty( property );
                }

                if ( safeValue ) {
                    try {
                        elem.style.setProperty( property, safeValue, 'important' );
                    } catch ( e ) {}
                }
            }
        }

        const stylesheetNodes = [ ...clonedDoc.querySelectorAll( 'style,link[rel="stylesheet"]' ) ];
        for ( const stylesheetNode of stylesheetNodes ) {
            if ( stylesheetNode.parentNode ) {
                stylesheetNode.parentNode.removeChild( stylesheetNode );
            }
        }
    },
};

const wprmpExtendPrintForPdf = () => {
    if ( ! window.hasOwnProperty( 'WPRMPrint' ) ) {
        return;
    }

    if ( window.WPRMPrint.hasOwnProperty( '_wprmpPdfExtended' ) ) {
        return;
    }

    const originalSetArgs = window.WPRMPrint.setArgs ? window.WPRMPrint.setArgs.bind( window.WPRMPrint ) : false;
    Object.assign( window.WPRMPrint, wprmpPdfPrintMethods );

    if ( originalSetArgs ) {
        window.WPRMPrint.setArgs = function( args ) {
            originalSetArgs( args );
            this.maybeAutoDownloadPdf();
        };
    }

    window.WPRMPrint._wprmpPdfExtended = true;
};

wprmpExtendPrintForPdf();

document.addEventListener( 'wprmPrintInit', () => {
    if ( window.WPRMPrint && 'function' === typeof window.WPRMPrint.maybeAutoDownloadPdf ) {
        window.WPRMPrint.maybeAutoDownloadPdf();
    }
} );
