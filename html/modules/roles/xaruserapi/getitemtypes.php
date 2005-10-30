<?php
/**
 * Utility function to retrieve the list of item types of this module (if any)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the item types and their description
 */
function roles_userapi_getitemtypes($args)
{
    $itemtypes = array();

// TODO: use 1 and 2 instead of 0 and 1 for itemtypes - cfr. bug 3439

/* this is the default for roles at the moment - select ALL in hooks if you want this*/
    $itemtypes[0] = array('label' => xarML('User'),
                          'title' => xarML('View User'),
                          'url'   => xarModURL('roles','user','view')
                         );

    $itemtypes[1] = array('label' => xarML('Group'),
                          'title' => xarML('View Group'),
                          'url'   => xarModURL('roles','user','viewtree')
                         );
    return $itemtypes;
}

?>
