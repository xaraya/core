<?php
/**
 * Table information for privileges module
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * Purpose of file:  Table information for privileges module
 * Return table name definitions to Xaraya
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * This function is called internally by the core whenever the module is
 * loaded. It is called by xarMod__dbInfoLoad()
 *
 * @return array
 */
function privileges_xartables()
{
    // Initialise table array
    $tables = array();

    $privileges  = xarDBGetSiteTablePrefix() . '_privileges';
    $privMembers = xarDBGetSiteTablePrefix() . '_privmembers';
    $roles       = xarDBGetSiteTablePrefix() . '_roles';
    $roleMembers = xarDBGetSiteTablePrefix() . '_rolemembers';
    $acl         = xarDBGetSiteTablePrefix() . '_security_acl';
    $instances   = xarDBGetSiteTablePrefix() . '_instances';
    $modules     = xarDBGetSiteTablePrefix() . '_modules';
//    $privsets    = xarDBGetSiteTablePrefix() . '_security_privsets';
    $realms      = xarDBGetSiteTablePrefix() . '_security_realms';
    $instances   = xarDBGetSiteTablePrefix() . '_security_instances';

    // Set the table names
    $tables['privileges']     = $privileges;
    $tables['privmembers']    = $privMembers;
    $tables['roles']          = $roles;
    $tables['rolemembers']    = $roleMembers;
    $tables['security_acl']   = $acl;
    $tables['instances']      = $instances;
    $tables['modules']        = $modules;
//    $tables['security_privsets']      = $privsets;
    $tables['security_realms']= $realms;
    $tables['security_instances']= $instances;

    // Return the table information
    return $tables;
}

?>
