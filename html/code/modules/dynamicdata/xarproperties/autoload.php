<?php
/**
 * Dynamicdata Module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's properties
 */
function dynamicdata_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'subitemsproperty'          => 'modules.dynamicdata.xarproperties.subitems',
        'subformproperty'           => 'modules.dynamicdata.xarproperties.subform',
        'propertyrefproperty'       => 'modules.dynamicdata.xarproperties.propertyref',
        'objectrefproperty'         => 'modules.dynamicdata.xarproperties.objectref',
        'objectmultiselectproperty' => 'modules.dynamicdata.xarproperties.objectmultiselect',
        'objectproperty'            => 'modules.dynamicdata.xarproperties.object',
        'itemtypeproperty'          => 'modules.dynamicdata.xarproperties.itemtype',
        'itemidproperty'            => 'modules.dynamicdata.xarproperties.itemid',
        'fieldtypeproperty'         => 'modules.dynamicdata.xarproperties.fieldtype',
        'fieldstatusproperty'       => 'modules.dynamicdata.xarproperties.fieldstatus',
        'datasourceproperty'        => 'modules.dynamicdata.xarproperties.datasource',
        'configurationproperty'     => 'modules.dynamicdata.xarproperties.configuration',
        'deferreditemproperty'      => 'modules.dynamicdata.xarproperties.deferitem',
        'deferredlistproperty'      => 'modules.dynamicdata.xarproperties.deferlist',
        'deferredmanyproperty'      => 'modules.dynamicdata.xarproperties.defermany',
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
    xarAutoload::registerFunction('dynamicdata_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>
