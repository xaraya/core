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
 * Autoload function for this module's properties
 */
function roles_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'afferoproperty'        => 'modules.roles.xarproperties.affero',
        'aimproperty'           => 'modules.roles.xarproperties.aim',
        'emailproperty'         => 'modules.roles.xarproperties.email',
        'grouplistproperty'     => 'modules.roles.xarproperties.grouplist',
        'icqproperty'           => 'modules.roles.xarproperties.icq',
        'msnproperty'           => 'modules.roles.xarproperties.msn',
        'passboxproperty'       => 'modules.roles.xarproperties.passwordbox',
        'rolestreeproperty'     => 'modules.roles.xarproperties.rolestree',
        'userlistproperty'      => 'modules.roles.xarproperties.userlist',
        'usernameproperty'      => 'modules.roles.xarproperties.username',
        'yahooproperty'         => 'modules.roles.xarproperties.yahoo',
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
    xarAutoload::registerFunction('roles_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}