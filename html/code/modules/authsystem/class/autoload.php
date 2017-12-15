<?php
/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function authsystem_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        'authsystemuserloginsubject'    => 'modules.authsystem.class.eventsubjects.userlogin',
        'authsystemuserlogoutsubject'   => 'modules.authsystem.class.eventsubjects.userlogout',
        // Controllers
        'authsystemshortcontroller'     => 'modules.authsystem.controllers.short',
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
    xarAutoload::registerFunction('authsystem_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>