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
 * @return array|string|void data for the template display
 */
function privileges_admin_viewprivileges()
{
    // Security
    if(!xarSecurity::check('EditPrivileges')) return;

    $data = array();

    if (!xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

    $data['authid'] = xarSec::genAuthKey();
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}
