import React from 'react';

import striptags from 'striptags';

import { __wprm } from 'Shared/Translations';
import Icon from 'Shared/Icon';
import Tooltip from 'Shared/Tooltip';
import Loader from 'Shared/Loader';
import FieldDropdown from 'Modal/fields/FieldDropdown';
import FieldRichText from 'Modal/fields/FieldRichText';
import { parseQuantity, formatQuantity } from 'Shared/quantities';

const unitConversionOptions = ( convertedUnitSystem ) => {
    let options = [
        {
            label: __wprm( 'Convert' ),
            options: [
                {
                    value: 'none',
                    label: __wprm( 'Keep Unit' ),
                },
                {
                    value: 'automatic',
                    label: __wprm( 'Automatically' ),
                }
            ],
        }
    ];

    let weightOptions = [];
    wprm_admin_modal.unit_conversion.systems[ convertedUnitSystem ].weight.map( (unit) => {
        weightOptions.push({
            value: unit,
            label: wprm_admin_modal.unit_conversion.units.data[ unit ].label,
        })
    });

    if ( 0 < weightOptions.length ) {
        options.push({
            label: __wprm( 'Weight Units' ),
            options: weightOptions,
        })
    }

    let volumeOptions = [];
    wprm_admin_modal.unit_conversion.systems[ convertedUnitSystem ].volume.map( (unit) => {
        volumeOptions.push({
            value: unit,
            label: wprm_admin_modal.unit_conversion.units.data[ unit ].label,
        })
    });

    if ( 0 < volumeOptions.length ) {
        options.push({
            label: __wprm( 'Volume Units' ),
            options: volumeOptions,
        })
    }

    return options;
}

const UnitConversionIngredient = (props) => {
    const { ingredient, allIngredients, isConverting, method } = props;
    
    // Derive converted from props - don't use useMemo as we want it to update on every render
    let converted = ingredient.converted ? ingredient.converted : false;

    // Check if converted is set up correctly.
    if ( typeof converted !== 'object' || ! converted.hasOwnProperty( 2 ) || ! converted[ 2 ].hasOwnProperty( 'amount' ) || ! converted[ 2 ].hasOwnProperty( 'unit' ) ) {
        converted = false;
    }

    if ( ! converted ) {
        converted = { 2: { amount: '', unit: '' } };
    }

    // Check if conversion feature is being used (at least one other ingredient has a conversion)
    const isConversionFeatureUsed = React.useMemo(() => {
        if (!allIngredients || allIngredients.length === 0) {
            return false;
        }
        
        return allIngredients.some(item => 
            item && 
            item.converted && 
            item.converted[2] && 
            item.converted[2].amount && 
            item.converted[2].amount !== ''
        );
    }, [allIngredients]);

    // Check if ingredient was added after conversion was calculated (missing snapshot)
    // Only show warning if ingredient has an amount (some ingredients don't need amounts/conversions)
    // Don't warn if ingredient already has a conversion (legacy data - snapshot will be auto-generated)
    const isMissingSnapshot = React.useMemo(() => {
        if (!isConversionFeatureUsed) {
            return false;
        }
        
        // Only show warning if ingredient has an amount set
        if (!ingredient.amount || ingredient.amount.trim() === '') {
            return false;
        }
        
        // If ingredient already has a conversion, it's legacy data (snapshot will be auto-generated)
        const hasConversion = ingredient.converted && ingredient.converted[2] && ingredient.converted[2].amount && ingredient.converted[2].amount !== '';
        if (hasConversion) {
            return false;
        }
        
        // If this ingredient has no snapshot and no conversion, and other ingredients have conversions, it was likely added after
        return !ingredient.conversion_item_snapshot;
    }, [isConversionFeatureUsed, ingredient.conversion_item_snapshot, ingredient.amount, ingredient.converted?.[2]?.amount, ingredient.converted?.[2]?.unit]);

    // Check if ingredient has changed since conversion was calculated
    const hasIngredientChanged = React.useMemo(() => {
        if (!ingredient.conversion_item_snapshot) {
            return false;
        }

        const snapshot = ingredient.conversion_item_snapshot;
        const current = {
            amount: ingredient.amount || '',
            unit: ingredient.unit || ''
        };

        const amountChanged = snapshot.amount !== current.amount;
        const unitChanged = snapshot.unit !== current.unit;
        const changed = amountChanged || unitChanged;

        return changed;
    }, [ingredient.conversion_item_snapshot, ingredient.amount, ingredient.unit, ingredient.name]);

    // Helper function to check if only amount changed (for proportional recalculation)
    const isOnlyAmountChanged = React.useMemo(() => {
        if (!hasIngredientChanged || !ingredient.conversion_item_snapshot) {
            return false;
        }

        const snapshot = ingredient.conversion_item_snapshot;
        const current = {
            amount: ingredient.amount || '',
            unit: ingredient.unit || ''
        };

        // Only amount changed, unit stayed the same
        return snapshot.amount !== current.amount && snapshot.unit === current.unit;
    }, [hasIngredientChanged, ingredient.conversion_item_snapshot, ingredient.amount, ingredient.unit]);

    // Calculate proportional converted amount
    const getProportionalConvertedAmount = React.useMemo(() => {
        if (!isOnlyAmountChanged || !ingredient.conversion_item_snapshot || !ingredient.converted || !ingredient.converted[2] || !ingredient.converted[2].amount) {
            return null;
        }

        const snapshot = ingredient.conversion_item_snapshot;
        const oldAmount = parseQuantity(snapshot.amount);
        const newAmount = parseQuantity(ingredient.amount || '');
        const currentConvertedAmount = parseQuantity(ingredient.converted[2].amount || '');

        if (oldAmount === 0 || newAmount === 0 || currentConvertedAmount === 0 || isNaN(oldAmount) || isNaN(newAmount) || isNaN(currentConvertedAmount)) {
            return null;
        }

        const ratio = newAmount / oldAmount;
        const newConvertedAmount = currentConvertedAmount * ratio;

        // Format the new amount with same settings as conversion
        let allowFractions = wprmp_admin && wprmp_admin.settings ? wprmp_admin.settings.unit_conversion_system_2_fractions : false;
        let decimals = wprmp_admin && wprmp_admin.settings ? wprmp_admin.settings.unit_conversion_round_to_decimals : 2;
        
        return formatQuantity(newConvertedAmount, decimals, allowFractions);
    }, [isOnlyAmountChanged, ingredient.conversion_item_snapshot, ingredient.amount, ingredient.converted?.[2]?.amount]);

    // Generate change description for tooltip
    const getChangeDescription = React.useMemo(() => {
        if (isMissingSnapshot) {
            return __wprm('Ingredient was added after unit conversion was calculated. Consider calculating the conversion for this ingredient.');
        }

        if (!hasIngredientChanged || !ingredient.conversion_item_snapshot) {
            return '';
        }

        const snapshot = ingredient.conversion_item_snapshot;
        const current = {
            amount: ingredient.amount || '',
            unit: ingredient.unit || ''
        };

        const changes = [];
        
        if (snapshot.amount !== current.amount) {
            changes.push(`${__wprm('Amount')}: ${snapshot.amount} → ${current.amount}`);
        }
        if (snapshot.unit !== current.unit) {
            changes.push(`${__wprm('Unit')}: ${snapshot.unit} → ${current.unit}`);
        }
        
        return changes.join('<br/>');
    }, [isMissingSnapshot, hasIngredientChanged, ingredient.conversion_item_snapshot, ingredient.amount, ingredient.unit]);

    const methodOptions = unitConversionOptions( props.convertedUnitSystem );

    let originalIngredient = `${ingredient.amount} ${ingredient.unit}`.trim();
    originalIngredient = `${originalIngredient} ${ingredient.name}`.trim();

    if ( ingredient.notes ) {
        originalIngredient += ` (${ingredient.notes})`;
    }

    return (
        <tr>
            <td>
                <FieldDropdown
                    isDisabled={ isConverting }
                    options={ methodOptions }
                    placeholder={ __wprm( 'Convert...' ) }
                    value={ method }
                    onChange={ (method) => {
                        props.onMethodChange( method );
                    }}
                    width={ 150 }
                />
            </td>
            <td
                style={ 'failed' === method ? { color: 'darkred' } : null }
            >
                {
                    isConverting
                    ?
                    <Loader />
                    :
                    <div className="wprm-admin-modal-field-ingredient-unit-conversion-fields">
                        <FieldRichText
                            key={`amount-v${props.fieldVersion || 0}`}
                            singleLine
                            value={ '' + (converted[2].amount || '') }
                            onChange={(amount) => {
                                // Pass field name and value to parent to avoid stale closure issues
                                props.onConvertedFieldChange('amount', amount);
                            }}
                        />
                        <FieldRichText
                            key={`unit-v${props.fieldVersion || 0}`}
                            singleLine
                            value={ '' + (converted[2].unit || '') }
                            onChange={(unit) => {
                                // Pass field name and value to parent to avoid stale closure issues
                                props.onConvertedFieldChange('unit', unit);
                            }}
                        />
                    </div>
                }
            </td>
            <td>
                { striptags( originalIngredient ) }
                {
                    (hasIngredientChanged || isMissingSnapshot) &&
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', marginLeft: '8px' }}>
                        <Tooltip content={isMissingSnapshot 
                            ? getChangeDescription
                            : `${__wprm('Ingredient changed since unit conversion was calculated')}:<br/><br/>${getChangeDescription}<br/><br/>${__wprm('Consider recalculating the conversion or marking it as OK.')}`
                        }>
                            <Icon
                                type="warning"
                                color="#8B0000"
                            />
                        </Tooltip>
                        {
                            // For missing snapshots, only show "Mark as OK" icon
                            isMissingSnapshot ? (
                                <Tooltip content={__wprm('Mark as OK')}>
                                    <Icon
                                        type="checkmark"
                                        color="#008000"
                                        onClick={() => {
                                            if (props.onMarkAsOK) {
                                                props.onMarkAsOK();
                                            }
                                        }}
                                    />
                                </Tooltip>
                            ) : (
                                // For changed ingredients, show all icons
                                <>
                                    {
                                        isOnlyAmountChanged && getProportionalConvertedAmount &&
                                        <Tooltip content={`${__wprm('Recalculate proportionally')}: ${converted[2].amount} → ${getProportionalConvertedAmount}`}>
                                            <Icon
                                                type="reload"
                                                onClick={() => {
                                                    // Recalculate conversion proportionally
                                                    if (props.onRecalculateProportionally && getProportionalConvertedAmount) {
                                                        props.onRecalculateProportionally(getProportionalConvertedAmount);
                                                    }
                                                }}
                                            />
                                        </Tooltip>
                                    }
                                    <Tooltip content={__wprm('Clear converted values')}>
                                        <Icon
                                            type="trash"
                                            onClick={() => {
                                                if (props.onClearConversion) {
                                                    props.onClearConversion();
                                                }
                                            }}
                                        />
                                    </Tooltip>
                                    <Tooltip content={__wprm('Mark as OK')}>
                                        <Icon
                                            type="checkmark"
                                            color="#008000"
                                            onClick={() => {
                                                if (props.onMarkAsOK) {
                                                    props.onMarkAsOK();
                                                }
                                            }}
                                        />
                                    </Tooltip>
                                </>
                            )
                        }
                    </div>
                }
            </td>
        </tr>
    );
}
export default UnitConversionIngredient;