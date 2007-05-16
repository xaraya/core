<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Return table name definitions to Xaraya
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * This function is called internally by the core whenever the module is
 * loaded. It is called by xarMod__dbInfoLoad()
 *
 * @return array
 */
function roles_xartables()
{
    $tables = array();

    $prefix = xarDB::getPrefix();
    $tables['privileges']     = $prefix . '_privileges';
    $tables['privmembers']    = $prefix . '_privmembers';
    $tables['roles']          = $prefix . '_roles';
    $tables['rolemembers']    = $prefix . '_rolemembers';
    $tables['security_acl']   = $prefix . '_security_acl';
    $tables['instances']      = $prefix . '_instances';
    //$tables['security_privsets']  = $prefix . '_security_privsets';
    $tables['security_realms']    = $prefix . '_security_realms';
    $tables['security_instances'] = $prefix . '_security_instances';

    return $tables;
}
?>
