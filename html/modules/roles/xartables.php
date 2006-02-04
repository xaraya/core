<?php
/**
 * Table information for roles module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
 
/* Purpose of file:  Table information for roles module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public 
 * @param none $ 
 * @return $xartable array
 * @throws no exceptions
 * @todo nothing
 */
function roles_xartables()
{ 
    // Initialise table array
    $xartable = array();

    $roles = xarDBGetSiteTablePrefix() . '_roles';
    $rolemembers = xarDBGetSiteTablePrefix() . '_rolemembers';
    // FIXME: do you still need those defined here too ?
    $privileges = xarDBGetSiteTablePrefix() . '_privileges';
    $privmembers = xarDBGetSiteTablePrefix() . '_privmembers';
    $acl = xarDBGetSiteTablePrefix() . '_security_acl';
    $masks = xarDBGetSiteTablePrefix() . '_security_masks';
    $instances = xarDBGetSiteTablePrefix() . '_instances';

    $xartable['users_column'] = array('uid' => $roles . '.xar_uid',
        'name' => $roles . '.xar_name',
        'uname' => $roles . '.xar_uname',
        'email' => $roles . '.xar_email',
        'pass' => $roles . '.xar_pass',
        'date_reg' => $roles . '.xar_date_reg',
        'valcode' => $roles . '.xar_valcode',
        'state' => $roles . '.xar_state',
        'auth_module' => $roles . '.xar_auth_module'
        ); 
    // Get the name for the autolinks item table
    $user_status = xarDBGetSiteTablePrefix() . '_user_status'; 
    // Set the table name
    $xartable['roles'] = $roles;
    $xartable['rolemembers'] = $rolemembers;
    $xartable['privileges'] = $privileges;
    $xartable['privmembers'] = $privmembers;
    $xartable['security_acl'] = $acl;
    $xartable['security_masks'] = $masks;
    $xartable['instances'] = $instances;
    $xartable['user_status'] = $user_status; 
    // Return the table information
    return $xartable;
} 

?>