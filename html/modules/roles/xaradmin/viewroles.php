<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * viewRoles - view the current groups
 * Takes no parameters
 */
function roles_admin_viewroles()
{
    // Clear Session Vars
    xarSessionDelVar('roles_statusmsg');
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    // Call the Roles class
    $roles = new xarRoles();

    include_once 'modules/roles/xartreerenderer.php';
    $renderer = new xarTreeRenderer();
    // Load Template
    $data['authid'] = xarSecGenAuthKey();
    $data['tree'] = $renderer->drawtree($renderer->maketree());
    return $data;
}

?>