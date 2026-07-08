// import * as FilePond from 'filepond';
// import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
// import 'filepond/dist/filepond.min.css';
// import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
// import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
// import FilePondPluginFileEncode from 'filepond-plugin-file-encode';

// FilePond.registerPlugin(FilePondPluginFileValidateSize);
// FilePond.registerPlugin(FilePondPluginImagePreview);
// FilePond.registerPlugin(FilePondPluginFileEncode);

import '../../css/public/form.scss';
import '../../css/public/blocks.scss';

window.WPRecipeMaker = typeof window.WPRecipeMaker === "undefined" ? {} : window.WPRecipeMaker;

window.WPRecipeMaker.submission = {
	init() {
        const submitButton = document.querySelector( '#wprmprs_submit' );

        // On click submit button.
        if ( submitButton ) {
            submitButton.addEventListener( 'click', (e) => {
                this.onClickSubmit( e.target );
            });
        }

        // Check size of file input fields.
        const fileInputs = document.querySelectorAll( '.wprm-recipe-submission input[type="file"]' );

        for ( let fileInput of fileInputs ) {
            fileInput.addEventListener( 'change', (e) => {
                this.checkFileSize( e.target );
            });
        } 

        // TODO. Problem with actually uploading the files. Use regular HTML5 form for now.
        // const images = document.querySelectorAll( '.wprmprs-layout-block-recipe_image' );
        // const maxFileSize = window.WPRecipeMaker.submission.formatBytes( wprmp_public.recipe_submission.max_file_size );

        // for ( let image of images ) {
        //     const input = image.querySelector( 'input' );
        //     const placeholder = input.dataset.placeholder;

        //     FilePond.create( input, {            
        //         labelIdle: placeholder,
        //         maxFiles: 1,
        //         maxFileSize: maxFileSize,
        //         labelMaxFileSizeExceeded: wprmp_public.recipe_submission.text.image_size,
        //         labelMaxFileSize: `> {filesize}`,
        //     } );
        // }
    },
    onClickSubmit( button ) {
        // If form is valid, indicate as submitting.
        if ( button.form.reportValidity() ) {
            button.style.width = `${button.offsetWidth}px`;
            button.style.height = `${button.offsetHeight}px`;
            if ( 'inline' === getComputedStyle( button ).display ) {
                button.style.display = 'inline-block';
            }
            button.style.opacity = '0.5';
            button.value = '...';
        }
    },
    onSubmit( form ) {
        // Already submitting, prevent double.
        if (  form.classList.contains('is-submitting') ) {
            return false;
        }

        // Prevent double submission
        form.classList.add('is-submitting');

        return true;
    },
    checkFileSize(input) {
        const files = input.files;
        
        if ( files.length ) {
            const file = files[0];

            // Check with the max file size is.
            let maxSize = wprmp_public.recipe_submission.max_file_size;

            if ( input.dataset.hasOwnProperty( 'maxSize' ) && input.dataset.maxSize ) {
                maxSize = parseInt( input.dataset.maxSize );
            }

            if ( maxSize < file.size ) {
                input.value = '';

                const maxSizeFormatted = this.formatBytes( maxSize );
                alert( wprmp_public.recipe_submission.text.image_size + ' ' + maxSizeFormatted );
            }
        }
    },
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
    
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    
        const i = Math.floor(Math.log(bytes) / Math.log(k));
    
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + '' + sizes[i];
    },
};

ready(() => {
	window.WPRecipeMaker.submission.init();
});

function ready( fn ) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}