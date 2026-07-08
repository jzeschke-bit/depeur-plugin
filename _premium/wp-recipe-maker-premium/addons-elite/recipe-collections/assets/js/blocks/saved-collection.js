const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const {
    PanelBody,
    TextControl,
} = wp.components;

// Backwards compatibility.
let InspectorControls;
let useBlockProps;
if ( wp.hasOwnProperty( 'blockEditor' ) ) {
	InspectorControls = wp.blockEditor.InspectorControls;
	useBlockProps = wp.blockEditor.useBlockProps;
} else {
	InspectorControls = wp.editor.InspectorControls;
	useBlockProps = wp.blockEditor ? wp.blockEditor.useBlockProps : ( () => ( { className: '' } ) );
}

let ServerSideRender;
if ( wp.hasOwnProperty( 'serverSideRender' ) ) {
    ServerSideRender = wp.serverSideRender;
} else {
    ServerSideRender = wp.components.ServerSideRender;
}

import PostSelect from '../../../../../../wp-recipe-maker/assets/js/blocks/shared/PostSelect';

registerBlockType( 'wp-recipe-maker/saved-collection', {
    apiVersion: 3,
    title: __( 'Saved Collection', 'wp-recipe-maker-premium' ),
    description: __( 'Display a Saved Recipe Collection.', 'wp-recipe-maker-premium' ),
    icon: 'book-alt',
    keywords: [],
    category: 'wp-recipe-maker',
    supports: {
		html: false,
    },
    transforms: {
        from: [
            {
                type: 'shortcode',
                tag: 'wprm-saved-collection',
                attributes: {
                    id: {
                        type: 'number',
                        shortcode: ( { named: { id = '' } } ) => {
                            return parseInt( id.replace( 'id', '' ) );
                        },
                    },
                },
            },
        ]
    },
    edit: (props) => {
        const { setAttributes } = props;
        const blockProps = useBlockProps( {
            style: {
                border: '1px dashed #444',
                borderRadius: '5px',
                padding: '10px',
            }
        } );

        // Empty ID breaks block.
        let attributes = props.attributes;

        if ( '' === attributes.id ) {
            attributes.id = 0;
        }

        return (
            <div { ...blockProps }>
                <InspectorControls>
                    <PanelBody title={ __( 'Saved Collection Details', 'wp-recipe-maker-premium' ) }>
                        <TextControl
                            label={ __( 'Saved Collection ID', 'wp-recipe-maker-premium' ) }
                            help={ __( 'Find the ID on the WP Recipe Maker > Manage > Saved Collections page.', 'wp-recipe-maker-premium' ) }
                            value={ attributes.id ? attributes.id : '' }
                            onChange={(id) => {
                                id = parseInt(id);
                                if ( isNaN( id ) ) {
                                    id = 0;
                                }

                                setAttributes({
                                    id,
                                });
                            }}
                        />
                    </PanelBody>
                </InspectorControls>
                <ServerSideRender
                    block="wp-recipe-maker/saved-collection"
                    attributes={ attributes }
                />
            </div>
        )
    },
    save: (props) => {
        return null;
    },
} );
