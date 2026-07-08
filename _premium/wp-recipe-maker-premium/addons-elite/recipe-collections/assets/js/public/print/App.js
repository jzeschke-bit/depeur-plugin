import React, { Component } from 'react';
import Collection from './Collection';


class App extends Component {

    constructor(props) {
        super(props);

        this.state = {
            recipes: JSON.parse(JSON.stringify( props.recipes ) ),
        } 
    }

    onUpdateRecipes( recipes ) {
        let newRecipes = JSON.parse(JSON.stringify(this.state.recipes));

        for ( let recipeId in recipes ) {
            if ( recipes.hasOwnProperty( recipeId ) ) {
                const oldRecipe = newRecipes.hasOwnProperty(recipeId) ? newRecipes[recipeId] : {};
                newRecipes[recipeId] = {
                    ...oldRecipe,
                    ...recipes[recipeId],
                }
            }
        }

        this.setState({
            recipes: newRecipes,
        });
    }

    render() {
        return (
            <Collection
                layout={ this.props.layout }
                collection={ this.props.collection }
                recipes={ this.state.recipes }
                onUpdateRecipes={ this.onUpdateRecipes.bind(this) }
            />
        );
    }
}
export default App;