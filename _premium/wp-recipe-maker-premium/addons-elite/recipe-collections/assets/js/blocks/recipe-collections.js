const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

// Backwards compatibility.
let ServerSideRender;
let useBlockProps;
if ( wp.hasOwnProperty( 'serverSideRender' ) ) {
    ServerSideRender = wp.serverSideRender;
} else {
    ServerSideRender = wp.components.ServerSideRender;
}
if ( wp.hasOwnProperty( 'blockEditor' ) ) {
	useBlockProps = wp.blockEditor.useBlockProps;
} else {
	useBlockProps = wp.blockEditor ? wp.blockEditor.useBlockProps : ( () => ( { className: '' } ) );
}

registerBlockType( 'wp-recipe-maker/recipe-collections', {
    apiVersion: 3,
    title: __( 'Recipe Collections', 'wp-recipe-maker-premium' ),
    description: __( 'Display the Recipe Collections feature.', 'wp-recipe-maker-premium' ),
    icon: 'book-alt',
    keywords: [ 'meal-planner' ],
    category: 'wp-recipe-maker',
    supports: {
		html: false,
    },
    transforms: {
        from: [
            {
                type: 'shortcode',
                tag: 'wprm-recipe-collections',
                attributes: {},
            },
        ]
    },
    edit: (props) => {
        const { attributes } = props;
        const blockProps = useBlockProps( {
            style: {
                border: '1px dashed #444',
                borderRadius: '5px',
                padding: '10px',
            }
        } );

        return (
            <div { ...blockProps }>
                <ServerSideRender
                    block="wp-recipe-maker/recipe-collections"
                    attributes={ attributes }
                />
            </div>
        )
    },
    save: (props) => {
        return null;
    },
} );