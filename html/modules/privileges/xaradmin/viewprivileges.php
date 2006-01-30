<?php
/**
 * View the current privileges
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewPrivileges - view the current privileges
 * Takes no parameters
 */
function privileges_admin_viewprivileges()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    // call the Privileges class
    $privs = new xarPrivileges();

    //Load Template
    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    //$data['trees'] = $renderer->drawtrees($data['show']);
    $data['trees'] = $renderer->maketrees($data['show']);
    //set_exception_handler(array('ExceptionHandlers','bone'));
       //    debug($data['newtrees']);
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}


?>
