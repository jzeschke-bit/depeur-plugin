import React from 'react';
import SVG from 'react-inlinesvg';

import Tooltip from './Tooltip';

import IconBasketAdd from '../../../../icons/basket-add.svg';
import IconCalendarAdd from '../../../../icons/calendar-add.svg';
import IconCart from '../../../../icons/cart.svg';
import IconCartRefresh from '../../../../icons/cart-refresh.svg';
import IconCheck from '../../../../icons/check.svg';
import IconCheckboxEmpty from '../../../../icons/checkbox-empty.svg';
import IconCheckboxChecked from '../../../../icons/checkbox-checked.svg';
import IconClear from '../../../../icons/clear.svg';
import IconClose from '../../../../icons/close.svg';
import IconCloudUpload from '../../../../icons/cloud-upload.svg';
import IconCloudUploadAlt from '../../../../icons/cloud-upload-alt.svg';
import IconDelete from '../../../../icons/delete.svg';
import IconDots from '../../../../icons/dots.svg';
import IconDownload from '../../../../icons/download.svg';
import IconDuplicate from '../../../../icons/duplicate.svg';
import IconDrag from '../../../../icons/drag.svg';
import IconDragAlt from '../../../../icons/drag-alt.svg';
import IconEdit from '../../../../icons/edit.svg';
import IconGrid from '../../../../icons/grid.svg';
import IconHistory from '../../../../icons/history.svg';
import IconInfo from '../../../../icons/info.svg';
import IconLink from '../../../../icons/link.svg';
import IconMinus from '../../../../icons/minus.svg';
import IconMinusAlt from '../../../../icons/minus-alt.svg';
import IconNoNutrition from '../../../../icons/no-nutrition.svg';
import IconNutrition from '../../../../icons/nutrition.svg';
import IconPlus from '../../../../icons/plus.svg';
import IconPlusAlt from '../../../../icons/plus-alt.svg';
import IconPlusThin from '../../../../icons/plus-thin.svg';
import IconPrinter from '../../../../icons/printer.svg';
import IconTrash from '../../../../icons/trash.svg';

import '../../../../css/public/icon.scss';
 
const icons = {
    'basket-add': IconBasketAdd,
    'calendar-add': IconCalendarAdd,
    cart: IconCart,
    'cart-refresh': IconCartRefresh,
    check: IconCheck,
    checkboxEmpty: IconCheckboxEmpty,
    checkboxChecked: IconCheckboxChecked,
    clear: IconClear,
    close: IconClose,
    'cloud-upload': IconCloudUpload,
    'cloud-upload-alt': IconCloudUploadAlt,
    delete: IconDelete,
    dots: IconDots,
    download: IconDownload,
    duplicate: IconDuplicate,
    drag: IconDrag,
    'drag-alt': IconDragAlt,
    edit: IconEdit,
    grid: IconGrid,
    history: IconHistory,
    info: IconInfo,
    link: IconLink,
    minus: IconMinus,
    'minus-alt': IconMinusAlt,
    nutrition: IconNutrition,
    'no-nutrition': IconNoNutrition,
    plus: IconPlus,
    'plus-alt': IconPlusAlt,
    'plus-thin': IconPlusThin,
    printer: IconPrinter,
    trash: IconTrash,
};

const Icon = (props) => {
    let icon = icons.hasOwnProperty(props.type) ? icons[props.type] : false;

    // Optional custom icon.
    const customIcons = wprmprc_public.settings.hasOwnProperty( 'recipe_collections_icons' ) ? wprmprc_public.settings.recipe_collections_icons : {};
    if ( customIcons.hasOwnProperty( props.type ) && customIcons[ props.type ] ) {
        icon = customIcons[ props.type ];
    }

    if ( !icon ) {
        return null;
    }

    // Optional custom color.
    let customColor = false;
    if ( wprmprc_public.settings.hasOwnProperty( 'recipe_collections_icon_color' ) && '#111111' !== wprmprc_public.settings.recipe_collections_icon_color ) {
        customColor = wprmprc_public.settings.recipe_collections_icon_color;
    }

    // Optional tooltip.
    const tooltip = props.hasOwnProperty('title') ? props.title : false;

    return (
        <span className="wprmprc-icon">
            <Tooltip
                content={ tooltip }
                tabIndex={ props.hasOwnProperty('tabIndex') ? props.tabIndex : false }
            >
                <SVG
                    src={icon}
                    preProcessor={(code) => {
                        if ( customColor && '#' === customColor.charAt(0) ) {
                            code = code.replaceAll('#111111', customColor );
                        }

                        return code;
                    }}
                />
            </Tooltip>
        </span>
    );
}
export default Icon;