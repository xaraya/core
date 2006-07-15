<?php
/**
 * Get admin menu links
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Pass individual menu items to the admin menu
 *
 * @author the Base module development team
 * @return array containing the menulinks for the admin menu items.
 */
function base_adminapi_getmenulinks()
{
     // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminBase',0)) {

        $menulinks[] = array('url'   => xarModURL('base','admin','sysinfo'),
                             'title' => xarML('View your PHP configuration'),
                             'label' => xarML('System Info'));
        $menulinks[] = array('url'   => xarModURL('base','admin','release'),
                             'title' => xarML('View recent released extensions'),
                             'label' => xarML('Extension Releases'));
        $menulinks[] = array('url'   => xarModURL('base','admin','modifyconfig'),
                             'title' => xarML('Modify Base configuration values'),
                             'label' => xarML('Modify Config'));
    }
    return $menulinks;
}
?>
