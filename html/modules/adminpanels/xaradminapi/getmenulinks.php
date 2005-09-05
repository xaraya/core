<?php
/**
 * File: $Id
 *
 * Utility function that passes individual menu items to the main menu
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function adminpanels_adminapi_getmenulinks()
{

// redundant link - TODO: remove/replace
/*     if (xarSecurityCheck('EditPanel',0)) { */
/*  */
/*         $menulinks[] = Array('url'   => xarModURL('adminpanels', */
/*                                                    'admin', */
/*                                                    'view'), */
/*                               'title' => xarML('The overview of this module and its functions'), */
/*                               'label' => xarML('Overview')); */
/*     } */

    // Security Check
    if (xarSecurityCheck('AdminPanel',0)) {

// redundant link, function incorporated into modify config - TODO: remove/replace
/*         $menulinks[] = Array('url'   => xarModURL('adminpanels', */
/*                                                    'admin', */
/*                                                    'config_view'), */
/*                               'title' => xarML('Modify configuration of the Overviews'), */
/*                               'label' => xarML('Overviews')); */

        $menulinks[] = Array('url'   => xarModURL('adminpanels',
                                                   'admin',
                                                   'modifyconfig'),
                              'title' => xarML('Modify configuration for the Admin Panels'),
                              'label' => xarML('Modify config'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}
?>