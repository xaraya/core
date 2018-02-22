<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @return array the menulinks for the admin menu items of this module.
 */
function mail_adminapi_getmenulinks()
{
    if (xarMod::isAvailable('scheduler')) {
        $menulinks[] = array('url' => xarModURL('mail','admin','viewq'),
                             'title' => xarML('View all mails scheduled to be sent later'),
                             'label' => xarML('View Mail Queue'));
    }
    return $menulinks;
}
?>