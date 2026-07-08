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

registerBlockType( 'wp-recipe-maker/shopping-list', {
    apiVersion: 3,
    title: __( 'Shopping List', 'wp-recipe-maker-premium' ),
    description: __( 'Quick Access Shopping List.', 'wp-recipe-maker-premium' ),
    icon: 'cart',
    keywords: [],
    category: 'wp-recipe-maker',
    supports: {
		html: false,
    },
    transforms: {
        from: [
            {
                type: 'shortcode',
                tag: 'wprm-shopping-list',
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
                    <PanelBody title={ __( 'Shopping List Details', 'wp-recipe-maker-premium' ) }>
                        <TextControl
                            label={ __( 'Saved Collection ID (optional)', 'wp-recipe-maker-premium' ) }
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
                    block="wp-recipe-maker/shopping-list"
                    attributes={ attributes }
                />
            </div>
        )
    },
    save: (props) => {
        return null;
    },
} );
