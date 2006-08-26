<?php
/**
 * View the current groups
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
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

    sys::import('modules.roles.xartreerenderer');
    $renderer = new xarTreeRenderer();
    // Load Template
    $data['authid'] = xarSecGenAuthKey();
    $data['tree'] = $renderer->drawtree($renderer->maketree());
    return $data;
}

?>
