<?php
/**
 * File: $Id$
 *
 * Delete a dynamic object and its properties
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * delete a dynamic object and its properties
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the object to delete
 * @returns int
 * @return object ID on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteobject($args)
{
    $objectid = Dynamic_Object_Master::deleteObject($args);
    return $objectid;
}
?>