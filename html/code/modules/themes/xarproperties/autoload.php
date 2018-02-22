<?php
/**
 * Privileges Module
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's properties
 */
function privileges_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'accessproperty'            => 'modules.privileges.xarproperties.access',
        'privielegestreeproperty'   => 'modules.privileges.xarproperties.privielegestree',
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
    xarAutoload::registerFunction('privileges_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>