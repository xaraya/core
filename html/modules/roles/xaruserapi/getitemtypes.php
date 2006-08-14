<?php
/**
 * Utility function to retrieve the list of item types of this module (if any)
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
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

/* this is the default for roles at the moment - select ALL in hooks if you want this*/
    $itemtypes[ROLES_ROLETYPE] = array('label' => xarML('Role'),
                          'title' => xarML('View Role'),
                          'url'   => xarModURL('roles','user','view')
                         );
    $itemtypes[ROLES_USERTYPE] = array('label' => xarML('User'),
                          'title' => xarML('View User'),
                          'url'   => xarModURL('roles','user','view')
                         );
    $itemtypes[ROLES_GROUPTYPE] = array('label' => xarML('Group'),
                          'title' => xarML('View Group'),
                          'url'   => xarModURL('roles','user','viewtree')
                         );

    $extensionitemtypes = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes',array('moduleid' => 27, 'native' =>false));

    /* TODO: activate this code when we move to php5
    $keys = array_merge(array_keys($itemtypes),array_keys($extensionitemtypes));
    $values = array_merge(array_values($itemtypes),array_values($extensionitemtypes));
    return array_combine($keys,$values);
    */

    $types = array();
    foreach ($itemtypes as $key => $value) $types[$key] = $value;
    foreach ($extensionitemtypes as $key => $value) $types[$key] = $value;
    return $types;
}

?>
