import React from 'react';

import striptags from 'striptags';

import Icon from 'Shared/Icon';
import { __wprm } from 'Shared/Translations';
import Api from 'Shared/Api';

import Loader from 'Shared/Loader';
import Toolbar from 'Modal/general/Toolbar';
import Tooltip from 'Shared/Tooltip';

const Item = (props) => {
    const { item, isUpdating, onItemChange, taxonomy, openSecondaryModal } = props;
    const [isLoadingProduct, setIsLoadingProduct] = React.useState(false);
    const [isAmountFocused, setIsAmountFocused] = React.useState(false);
    const useDefaultProductAmount = (
        'wprm_ingredient' === taxonomy
        && wprm_admin_modal
        && wprm_admin_modal.settings
        && wprm_admin_modal.settings.products_default_linked_ingredient_amount
    ) || (
        'wprm_equipment' === taxonomy
        && wprm_admin_modal
        && wprm_admin_modal.settings
        && wprm_admin_modal.settings.products_default_linked_equipment_amount
    );
    const resetToDefault = React.useCallback(() => {
        onItemChange({
            product_amount: '1',
            product_amount_default: true,
            product_item_snapshot: null,
        });
    }, [onItemChange]);
    const productAmountValue = undefined === item.product_amount || null === item.product_amount ? '' : item.product_amount;
    
    // Helper function to get current item values
    const getCurrentValues = React.useCallback(() => {
        const current = {
            amount: item.amount || '',
            name: item.name || '',
            notes: item.notes || ''
        };

        // For ingredients, also include unit field
        if (taxonomy === 'wprm_ingredient') {
            current.unit = item.unit || '';
        }
        
        return current;
    }, [item.amount, item.unit, item.name, item.notes, taxonomy]);
    
    // Helper function to create a new snapshot
    const createSnapshot = React.useCallback(() => {
        const snapshot = getCurrentValues();
        snapshot.timestamp = Date.now();
        return snapshot;
    }, [getCurrentValues]);
    
    // Helper function to compare snapshot with current values
    const compareWithSnapshot = React.useCallback((snapshot) => {
        const current = getCurrentValues();
        return (
            snapshot.amount !== current.amount ||
            snapshot.name !== current.name ||
            snapshot.notes !== current.notes ||
            (taxonomy === 'wprm_ingredient' && snapshot.unit !== current.unit)
        );
    }, [getCurrentValues, taxonomy]);
    
    // Helper function to check if only amount changed (for proportional recalculation)
    const isOnlyAmountChanged = React.useCallback((snapshot) => {
        const current = getCurrentValues();
        return (
            snapshot.amount !== current.amount &&
            snapshot.name === current.name &&
            snapshot.notes === current.notes &&
            (taxonomy !== 'wprm_ingredient' || snapshot.unit === current.unit)
        );
    }, [getCurrentValues, taxonomy]);
    
    // Check if item has changed since product was set (works for both ingredients and equipment)
    const hasItemChanged = React.useMemo(() => {
        if (!item.product || !item.product_amount || !item.product_item_snapshot) {
            return false;
        }

        const amount = parseFloat(item.product_amount);
        if (isNaN(amount) || amount <= 0) {
            return false;
        }
        
        const snapshot = item.product_item_snapshot;
        
        return compareWithSnapshot(snapshot);
    }, [item.product, item.product_item_snapshot, compareWithSnapshot]);
    
    // Generate change description for tooltip
    const getChangeDescription = React.useMemo(() => {
        if (!hasItemChanged || !item.product_item_snapshot) {
            return '';
        }
        
        const snapshot = item.product_item_snapshot;
        const current = getCurrentValues();
        
        const changes = [];
        
        if (snapshot.amount !== current.amount) {
            changes.push(`${__wprm('Amount')}: ${snapshot.amount} → ${current.amount}`);
        }
        if (taxonomy === 'wprm_ingredient' && snapshot.unit !== current.unit) {
            changes.push(`${__wprm('Unit')}: ${snapshot.unit} → ${current.unit}`);
        }
        if (snapshot.name !== current.name) {
            changes.push(`${__wprm('Name')}: ${snapshot.name} → ${current.name}`);
        }
        if (snapshot.notes !== current.notes) {
            changes.push(`${__wprm('Notes')}: ${snapshot.notes} → ${current.notes}`);
        }
        
        return changes.join('<br/>');
    }, [hasItemChanged, item.product_item_snapshot, getCurrentValues, taxonomy]);
    
    // Check if only amount changed (for proportional recalculation)
    const canRecalculateProportionally = React.useMemo(() => {
        if (!hasItemChanged || !item.product_item_snapshot || !item.product_amount) {
            return false;
        }
        
        const snapshot = item.product_item_snapshot;
        
        // Only amount changed, everything else is the same
        return isOnlyAmountChanged(snapshot);
    }, [hasItemChanged, item.product_item_snapshot, isOnlyAmountChanged, item.product_amount]);
    
    // Calculate new product amount proportionally
    const getProportionalAmount = React.useMemo(() => {
        if (!canRecalculateProportionally || !item.product_item_snapshot) {
            return null;
        }
        
        const oldAmount = parseFloat(item.product_item_snapshot.amount) || 0;
        const newAmount = parseFloat(item.amount) || 0;
        const currentProductAmount = parseFloat(item.product_amount) || 0;
        
        if (oldAmount === 0 || newAmount === 0) {
            return null;
        }
        
        const ratio = newAmount / oldAmount;
        const newProductAmount = currentProductAmount * ratio;
        
        return Math.round(newProductAmount * 1000) / 1000; // Round to 3 decimal places
    }, [canRecalculateProportionally, item.product_item_snapshot, item.amount, item.product_amount]);


    let originalItem = `${item.amount} ${ item.hasOwnProperty('unit') ? item.unit : '' }`.trim();
    originalItem = `${originalItem} ${item.name}`.trim();

    if ( item.notes ) {
        originalItem += ` (${item.notes})`;
    }

    return (
        <tr className="wprm-admin-modal-field-product-container">
            <td>
                { striptags( originalItem ) }
                {
                    hasItemChanged &&
                    <div style={{ display: 'flex', alignItems: 'center', gap: '4px', float: 'right', marginRight: '4px' }}>
                        <Tooltip content={`${taxonomy === 'wprm_ingredient' ? __wprm('Ingredient changed since produc amount was set') : __wprm('Equipment changed since product amount was set')}:<br/><br/>${getChangeDescription}<br/><br/>${__wprm('Consider updating the product amount or marking it as OK.')}`}>
                            <Icon
                                type="warning"
                                color="#8B0000"
                                style={{ 
                                    color: '#ff6b6b', 
                                    fontSize: '14px',
                                    cursor: 'help'
                                }}
                            />
                        </Tooltip>
                        {
                            canRecalculateProportionally && getProportionalAmount &&
                            <Tooltip content={`${__wprm('Recalculate proportionally')}: ${item.product_amount} → ${getProportionalAmount}`}>
                                <Icon
                                    type="reload"
                                    style={{ 
                                        fontSize: '12px',
                                        cursor: 'pointer',
                                        padding: '2px'
                                    }}
                                    onClick={() => {
                                        const snapshot = createSnapshot();
                                        onItemChange({
                                            product_amount: getProportionalAmount.toString(),
                                            product_amount_default: false,
                                            product_item_snapshot: snapshot
                                        });
                                    }}
                                />
                            </Tooltip>
                        }
                        <Tooltip content={ useDefaultProductAmount ? __wprm( 'Reset to default' ) : __wprm( 'Clear amount' ) }>
                            <Icon
                                type="trash"
                                style={{ 
                                    fontSize: '12px',
                                    cursor: 'pointer',
                                    padding: '2px'
                                }}
                                onClick={() => {
                                    if ( useDefaultProductAmount ) {
                                        resetToDefault();
                                    } else {
                                        onItemChange({
                                            product_amount: '',
                                            product_amount_default: false,
                                            product_item_snapshot: null
                                        });
                                    }
                                }}
                            />
                        </Tooltip>
                        <Tooltip content={__wprm('Mark as OK')}>
                            <Icon
                                type="checkmark"
                                color="#008000"
                                style={{ 
                                    fontSize: '12px',
                                    cursor: 'pointer',
                                    padding: '2px'
                                }}
                                onClick={() => {
                                    const snapshot = createSnapshot();
                                    onItemChange({ 
                                        product_item_snapshot: snapshot,
                                    });
                                }}
                            />
                        </Tooltip>
                    </div>
                }
            </td>
            <td>
                {
                    item.product
                    &&
                    <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        <input
                            type="number"
                            value={ productAmountValue }
                            onChange={(e) => {
                                const value = e.target.value;
                                // Allow empty string or valid number format (including .5, 0.5, 0.005, etc.)
                                if (value === '' || /^\.?\d*\.?\d*$/.test(value)) {
                                    const numValue = parseFloat(value);
                                    if ( value === '' && useDefaultProductAmount ) {
                                        resetToDefault();
                                    } else if (value === '' || !isNaN(numValue) && numValue >= 0) {
                                        const snapshot = createSnapshot();
                                        onItemChange({
                                            product_amount: value,
                                            product_amount_default: false,
                                            product_item_snapshot: snapshot
                                        });
                                    }
                                }
                            }}
                            onFocus={() => setIsAmountFocused(true)}
                            onBlur={() => setIsAmountFocused(false)}
                            placeholder="1"
                            min="0"
                            step="1"
                            style={{
                                width: '75px',
                                padding: '4px 6px',
                                border: hasItemChanged ? '1px solid #ff6b6b' : '1px solid #ddd',
                                borderRadius: '3px',
                                fontSize: '0.9em'
                            }}
                        />
                    </div>
                }
            </td>
            <td>
                {
                    isUpdating
                    ?
                    <Loader />
                    :
                    <div className="wprm-admin-modal-field-product" style={{ display: 'flex', alignItems: 'center', gap: '10px', padding: '8px 0' }}>
                        {isLoadingProduct ? (
                            <Loader style={{ flexShrink: 0 }} />
                        ) : (
                            <Icon
                                type="pencil"
                                title={ __wprm( 'Change Product' ) }
                                onClick={() => {
                                    setIsLoadingProduct(true);
                                    // Get or create the taxonomy term for this ingredient/equipment
                                    Api.manage.getTermId(taxonomy, item.name).then((response) => {
                                        setIsLoadingProduct(false);
                                        if (response && response.term_id) {
                                            openSecondaryModal( 'product', {
                                                label: item.name,
                                                taxonomy: taxonomy,
                                                term: response.term_id, // Use the actual term ID
                                                product: item.product,
                                                saveCallback: ( selectedProduct ) => {
                                                    const snapshot = createSnapshot();
                                                    let changes = {
                                                        product: selectedProduct,
                                                        product_item_snapshot: snapshot
                                                    };

                                                    if ( useDefaultProductAmount
                                                        && (
                                                            ! item.hasOwnProperty( 'product_amount' )
                                                            || item.product_amount_default
                                                        )
                                                    ) {
                                                        changes.product_amount = '1';
                                                        changes.product_amount_default = true;
                                                    }

                                                    onItemChange( changes );
                                                },
                                            } );
                                        } else {
                                            // Failed to get or create term
                                        }
                                    }).catch((error) => {
                                        setIsLoadingProduct(false);
                                        // Error getting/creating term
                                    });
                                }}
                                style={{ flexShrink: 0, cursor: 'pointer' }}
                            />
                        )}
                        {
                            item.product
                            ?
                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flex: 1, minWidth: 0 }}>
                                {
                                    item.product.image_url
                                    ?
                                    <img 
                                        src={ item.product.image_url } 
                                        alt={ item.product.name }
                                        style={{ width: '40px', height: '40px', objectFit: 'cover', borderRadius: '4px', flexShrink: 0 }}
                                    />
                                    :
                                    null
                                }
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                                        <a href={ item.product.url } target="_blank" style={{ fontSize: '0.95em', fontWeight: '500', textDecoration: 'none' }}>
                                            { item.product.name }
                                        </a>
                                        {
                                            item.product.variation_id && item.product.variation_name
                                            ?
                                            <span style={{ 
                                                fontSize: '0.75em', 
                                                color: '#888', 
                                                backgroundColor: '#f0f0f0', 
                                                padding: '2px 6px', 
                                                borderRadius: '3px',
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                gap: '4px'
                                            }}>
                                                { __wprm( 'Variation' ) }: { item.product.variation_name }
                                                {
                                                    item.product.variation_image_url
                                                    ?
                                                    <img 
                                                        src={ item.product.variation_image_url } 
                                                        alt={ item.product.variation_name }
                                                        style={{ width: '14px', height: '14px', objectFit: 'cover', borderRadius: '2px' }}
                                                    />
                                                    :
                                                    null
                                                }
                                            </span>
                                            :
                                            null
                                        }
                                    </div>
                                </div>
                            </div>
                            :
                            <span style={{ color: '#999', fontStyle: 'italic', fontSize: '0.9em' }}>{ __wprm( 'No product set' ) }</span>
                        }
                    </div>
                }
            </td>
            {
                isAmountFocused && item.product &&
                <Toolbar>
                    <div className="wprm-admin-modal-toolbar-buttons">
                        {[
                            { value: '0.125', symbol: '⅛' },
                            { value: '0.25', symbol: '¼' },
                            { value: '0.333', symbol: '⅓' },
                            { value: '0.5', symbol: '½' },
                            { value: '0.667', symbol: '⅔' },
                            { value: '0.75', symbol: '¾' },
                            { value: '1', symbol: '1' },
                            { value: '1.25', symbol: '1¼' },
                            { value: '1.333', symbol: '1⅓' },
                            { value: '1.5', symbol: '1½' },
                            { value: '1.667', symbol: '1⅔' },
                            { value: '1.75', symbol: '1¾' },
                            { value: '2', symbol: '2' },
                            { value: '2.5', symbol: '2½' },
                            { value: '3', symbol: '3' },
                            { value: '3.5', symbol: '3½' },
                            { value: '4', symbol: '4' },
                            { value: '4.5', symbol: '4½' },
                            { value: '5', symbol: '5' }
                        ].map((fraction) => (
                            <span
                                key={fraction.value}
                                className="wprm-admin-modal-toolbar-button"
                                onMouseDown={ (event) => {
                                    event.preventDefault();
                                    const snapshot = createSnapshot();
                                    onItemChange({
                                        product_amount: fraction.value,
                                        product_amount_default: false,
                                        product_item_snapshot: snapshot,
                                    });
                                }}
                            >
                                <Tooltip content={fraction.value}>
                                    <span className="wprm-admin-modal-toolbar-button-character">{fraction.symbol}</span>
                                </Tooltip>
                            </span>
                        ))}
                        <span
                            className="wprm-admin-modal-toolbar-button"
                            onMouseDown={ (event) => {
                                event.preventDefault();
                                const calculation = prompt( __wprm( 'Enter calculation (e.g., 1/16, 2 * 3.5 + 1, etc.):' ) );
                                if (calculation !== null && calculation.trim() !== '') {
                                    try {
                                        // Replace common fraction symbols with decimal equivalents
                                        let expression = calculation
                                            .replace(/⅛/g, '0.125')
                                            .replace(/¼/g, '0.25')
                                            .replace(/⅓/g, '0.333')
                                            .replace(/½/g, '0.5')
                                            .replace(/⅔/g, '0.667')
                                            .replace(/¾/g, '0.75')
                                            .replace(/1¼/g, '1.25')
                                            .replace(/1⅓/g, '1.333')
                                            .replace(/1½/g, '1.5')
                                            .replace(/1⅔/g, '1.667')
                                            .replace(/1¾/g, '1.75')
                                            .replace(/2½/g, '2.5')
                                            .replace(/3½/g, '3.5')
                                            .replace(/4½/g, '4.5');
                                        
                                        // Evaluate the expression safely
                                        const result = Function('"use strict"; return (' + expression + ')')();
                                        if (typeof result === 'number' && !isNaN(result)) {
                                            const snapshot = createSnapshot();
                                            onItemChange({
                                                product_amount: result.toString(),
                                                product_amount_default: false,
                                                product_item_snapshot: snapshot,
                                            });
                                        } else {
                                            alert('Invalid calculation. Please enter a valid mathematical expression.');
                                        }
                                    } catch (error) {
                                        alert('Invalid calculation. Please enter a valid mathematical expression.');
                                    }
                                }
                            }}
                        >
                            <Tooltip content={ __wprm( 'Calculate' ) }>
                                <span className="wprm-admin-modal-toolbar-button-character">=</span>
                            </Tooltip>
                        </span>
                    </div>
                </Toolbar>
            }
        </tr>
    );
}
export default Item;
