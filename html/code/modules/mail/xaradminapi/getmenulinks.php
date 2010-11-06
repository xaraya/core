<?php
/**
 * Pass individual menu items to the admin panels
 *
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * utility function pass individual menu items to the admin panels
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @return array the menulinks for the main menu items.
 */
function mail_adminapi_getmenulinks()
{
    // Get menu links from xml
    $menulinks = xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'mail', 'modtype' => 'admin'));
    if (xarModIsAvailable('scheduler')) {
        $menulinks[] = array('url' => xarModURL('mail','admin','viewq'),
                             'title' => xarML('View all mails scheduled to be sent later'),
                             'label' => xarML('View Mail Queue'));
    }
    return $menulinks;
}
?>