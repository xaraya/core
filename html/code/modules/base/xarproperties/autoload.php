<?php
/**
 * Base Module
 * 
 * @package modules\base
 * @subpackage base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's properties
 */
function base_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'arrayproperty'            => 'modules.base.xarproperties.array',
        'calculatedproperty'       => 'modules.base.xarproperties.calculated',
        'calendarproperty'         => 'modules.base.xarproperties.calendar',
        'checkboxproperty'         => 'modules.base.xarproperties.checkbox',
        'checkboxlistproperty'     => 'modules.base.xarproperties.checkboxlist',
        'comboboxproperty'         => 'modules.base.xarproperties.combobox',
        'countrylistingproperty'   => 'modules.base.xarproperties.countrylisting',
        'dateformatproperty'       => 'modules.base.xarproperties.dateformat',
        'selectproperty'           => 'modules.base.xarproperties.dropdown',
        'dropdownpropertyinstall'  => 'modules.base.xarproperties.dropdown',
        'extendeddateroperty'      => 'modules.base.xarproperties.extendeddate',
        'filepickerproperty'       => 'modules.base.xarproperties.filepicker',
        'fileuploadproperty'       => 'modules.base.xarproperties.fileupload',
        'floatboxproperty'         => 'modules.base.xarproperties.floatbox',
        'hiddenproperty'           => 'modules.base.xarproperties.hidden',
        'imageproperty'            => 'modules.base.xarproperties.image',
        'imagelistproperty'        => 'modules.base.xarproperties.imagelist',
        'numberboxroperty'         => 'modules.base.xarproperties.integrerbox',
        'integerlistproperty'      => 'modules.base.xarproperties.integerlist',
        'languagelistproperty'     => 'modules.base.xarproperties.language',
        'multiselectproperty'      => 'modules.base.xarproperties.multiselect',
        'orderselectproperty'      => 'modules.base.xarproperties.orderselect',
        'radiobuttonsproperty'     => 'modules.base.xarproperties.radio',
        'statelistproperty'        => 'modules.base.xarproperties.statelisting',
        'statictextproperty'       => 'modules.base.xarproperties.static',
        'subitemarrayproperty'     => 'modules.base.xarproperties.subitemarray',
        'tcolorpickerroperty'      => 'modules.base.xarproperties.tcolorpicker',
        'textareaproperty'         => 'modules.base.xarproperties.textarea',
        'textboxproperty'          => 'modules.base.xarproperties.textbox',
        'timezoneproperty'         => 'modules.base.xarproperties.timezone',
        'urlproperty'              => 'modules.base.xarproperties.url',
        'urliconroperty'           => 'modules.base.xarproperties.urlicon',
        'urltitleproperty'         => 'modules.base.xarproperties.urltitle',
        'htmlpageproperty'         => 'modules.base.xarproperties.webpage',
    );
    
    if (isset($class_array[$class])) {
        sys::import($class_array[$class]);
        return true;
    }
    return false;
}

/**
 * Register this function for autoload on import
 */
if (class_exists('xarAutoload')) {
    xarAutoload::registerFunction('base_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>