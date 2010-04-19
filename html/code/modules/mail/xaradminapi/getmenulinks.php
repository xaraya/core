<?php
/**
 * Pass individual menu items to the admin panels
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * utility function pass individual menu items to the admin panels
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function mail_adminapi_getmenulinks()
{
    $menulinks = xarMod::apiFunc('base','admin','menuarray',array('module' => 'mail'));
    if (xarModIsAvailable('scheduler')) {
        $menulinks[] = array('url' => xarModURL('mail','admin','viewq'),
                             'title' => xarML('View all mails scheduled to be sent later'),
                             'label' => xarML('View Mail Queue'));
    }
    return $menulinks;
}
?>