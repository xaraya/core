<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewPrivileges - view the current privileges
 * @return array data for the template display
 */
function privileges_admin_viewprivileges()
{
    // Security
    if(!xarSecurityCheck('EditPrivileges')) return;

    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    $data['authid'] = xarSecGenAuthKey();
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}


?>
