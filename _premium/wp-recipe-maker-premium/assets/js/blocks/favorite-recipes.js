const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

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

registerBlockType( 'wp-recipe-maker/favorite-recipes', {
    apiVersion: 3,
    title: __( 'Favorite Recipes', 'wp-recipe-maker-premium' ),
    description: __( 'Display the current visitor favorite recipes.', 'wp-recipe-maker-premium' ),
    icon: 'star-filled',
    keywords: [],
    category: 'wp-recipe-maker',
    supports: {
        html: false,
    },
    transforms: {
        from: [
            {
                type: 'shortcode',
                tag: 'wprm-favorite-recipes',
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
                    block="wp-recipe-maker/favorite-recipes"
                    attributes={ attributes }
                />
            </div>
        )
    },
    save: () => {
        return null;
    },
} );
