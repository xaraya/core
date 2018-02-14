<?php
/**
 * Roles Module
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function roles_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        // Controllers

        'role'              => 'modules.roles.class.role',
        'xarroles'          => 'modules.roles.class.roles',
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
    xarAutoload::registerFunction('roles_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>