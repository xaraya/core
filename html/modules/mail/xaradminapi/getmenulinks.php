<?php
/**
 * Pass individual menu items to the admin panels
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail module
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
    // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminMail', 0)) {
        $menulinks[] = array('url' => xarModURL('mail','admin','compose'),
                             'title' => xarML('Test your email configuration'),
                             'label' => xarML('Test Configuration'));
        if (xarModIsAvailable('scheduler')) {
            $menulinks[] = array('url' => xarModURL('mail','admin','viewq'),
                                 'title' => xarML('View all mails scheduled to be sent later'),
                                 'label' => xarML('View Mail Queue'));
        }
        $menulinks[] = array('url' => xarModUrl('mail','admin','view'),
                             'title' => xarML('Manage queues for mail item handling'),
                             'label' => xarML('Queue management'));
        $menulinks[] = array('url' => xarModURL('mail','admin','template'),
                             'title' => xarML('Change the mail template for notifications'),
                             'label' => xarML('Notification Template'));
        $menulinks[] = array('url' => xarModURL('mail','admin','modifyconfig'),
                             'title' => xarML('Modify the configuration for the utility mail module'),
                             'label' => xarML('Modify Config'));
    }
    return $menulinks;
}
?>
