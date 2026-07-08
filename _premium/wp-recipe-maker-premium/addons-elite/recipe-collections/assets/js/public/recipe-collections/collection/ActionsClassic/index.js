import React, { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';

import Button from '../../../../shared/Button';
import { __wprm } from 'Shared/Translations';

import AddItem from './AddItem';
import ColumnsGroups from './ColumnsGroups';
import SaveCollection from './SaveCollection';

class Actions extends Component {
    render() {        
        return (
            <div className={`wprmprc-collection-column-width wprmprc-collection-actions wprmprc-collection-actions-${this.props.mode}`}>
                {
                    'overview' === this.props.mode
                    &&
                    <Fragment>
                        {
                            ( 'saved' === this.props.type || 'shared' === this.props.type )
                            ?
                            <SaveCollection
                                type={this.props.type}
                                collection={this.props.collection}
                            />
                            :
                            <Fragment>
                                {
                                    ( ! this.props.collection.fixed || 'admin' === this.props.type )
                                    &&
                                    <Fragment>
                                        <Button className="wprmprc-collection-action wprmprc-collection-action-add" onClick={ () => this.props.onChangeMode('add-item' ) }>{ __wprm( 'Add Item' ) }</Button>
                                        <Button className="wprmprc-collection-action wprmprc-collection-action-remove" onClick={ () => this.props.onChangeMode('remove-items' ) }>{ __wprm( 'Remove Items' ) }</Button>
                                        {
                                            'inbox' !== this.props.type
                                            && <Button className="wprmprc-collection-action wprmprc-collection-action-columns-groups" onClick={ () => this.props.onChangeMode('columns-groups' ) }>{ __wprm( 'Columns & Groups' ) }</Button>
                                        }
                                    </Fragment>
                                }
                            </Fragment>
                        }
                        {
                            wprmprc_public.settings.recipe_collections_nutrition_facts && 0 < wprmprc_public.settings.recipe_collections_nutrition_facts_fields.length
                            &&
                            <Button
                                className="wprmprc-collection-action wprmprc-collection-action-nutrition"
                                onClick={() => {
                                    this.props.onChangeShowNutrition( ! this.props.showNutrition );
                                }}
                            >{ this.props.showNutrition ? __wprm( 'Hide Nutrition Facts' ) : __wprm( 'Show Nutrition Facts' ) }</Button>
                        }
                        {
                            wprmprc_public.settings.recipe_collections_print
                            &&
                            <Button
                                className="wprmprc-collection-action wprmprc-collection-action-print-collection"
                                onClick={() => {
                                    this.props.onPrint();
                                }}
                            >{ __wprm( 'Print Collection' ) }</Button>
                        }
                        {
                            wprmprc_public.settings.recipe_collections_print_recipes
                            &&
                            <Button
                                className="wprmprc-collection-action wprmprc-collection-action-print-recipes"
                                onClick={() => {
                                    this.props.onPrintRecipes();
                                }}
                            >{ __wprm( 'Print Recipes' ) }</Button>
                        }
                        {
                            wprmprc_public.settings.recipe_collections_shopping_list
                            &&
                            'admin' !== this.props.type
                            &&
                            <Button
                                className="wprmprc-collection-action wprmprc-collection-action-shopping-list"
                                onClick={() => {
                                    if ( 'inbox' === this.props.type ) {
                                        this.props.history.push(`/shopping-list/inbox/`);
                                    } else {
                                        this.props.history.push(`/shopping-list/${this.props.type}/${this.props.collection.id}`);
                                    }
                                }}
                            >{ __wprm( 'Shopping List' ) }</Button>
                        }
                    </Fragment>
                }
                {
                    'remove-items' === this.props.mode
                    &&
                    <Fragment>
                        <Button className="wprmprc-collection-action wprmprc-collection-action-remove-all" onClick={ () => {
                            if ( confirm( __wprm( 'Are you sure you want to remove all items from this collection?' ) ) ) {
                                this.props.onRemoveAll();
                            }
                        } }>{ __wprm( 'Remove All Items' ) }</Button>
                        <Button className="wprmprc-collection-action wprmprc-collection-action-remove-cancel" onClick={ () => this.props.onChangeMode('overview' ) }>{ __wprm( 'Stop Removing Items' ) }</Button>
                    </Fragment>
                }
                {
                    'add-item' === this.props.mode
                    &&
                    <Fragment>
                        <div className="wprmprc-collection-header wprmprc-collection-action-header">
                            <Button tag="span" className="wprmprc-header-link" onClick={ () => this.props.onChangeMode('overview') }>{ __wprm( 'Actions' ) }</Button>
                            <span className="wprmprc-header-link-separator">&gt;</span>
                            { __wprm( 'Add Item' ) }
                        </div>
                        <AddItem
                            layout={this.props.layout}
                            collections={this.props.collections}
                            type={this.props.type}
                            collection={this.props.collection}
                            addItems={this.props.addItems}
                            onChangeAddItems={this.props.onChangeAddItems}
                            options={this.props.modeOptions['add-item']}
                            onChangeModeOptions={this.props.onChangeModeOptions}
                            interface="drag"
                        />
                    </Fragment>
                }
                {
                    'inbox' !== this.props.type && 'columns-groups' === this.props.mode
                    &&
                    <Fragment>
                        <div className="wprmprc-collection-header wprmprc-collection-action-header">
                            <Button tag="span" className="wprmprc-header-link" onClick={ () => this.props.onChangeMode('overview') }>{ __wprm( 'Actions' ) }</Button>
                            <span className="wprmprc-header-link-separator">&gt;</span>
                            { __wprm( 'Columns & Groups' ) }
                        </div>
                        <ColumnsGroups
                            columns={this.props.columns}
                            onChangeColumns={this.props.onChangeColumns}
                            groups={this.props.groups}
                            onChangeGroups={this.props.onChangeGroups}
                        />
                    </Fragment>
                }
            </div>
        );
    }
}

export default withRouter( Actions );