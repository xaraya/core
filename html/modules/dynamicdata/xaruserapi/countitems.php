<?php
/**
 * Count number of items held by this module
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param $args the usual suspects :)
 * @returns integer
 * @return number of items held by this module
 */
function dynamicdata_userapi_countitems($args)
{
    $mylist = & Dynamic_Object_Master::getObjectList($args);
    if (!isset($mylist)) return;

    return $mylist->countItems();
}

?>
