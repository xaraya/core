<?php
/**
 * File: $Id$
 *
 * Get the next itemtype of objects pertaining to a given module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author random <mfl@netspan.ch>
*/
/**
 * get the next itemtype of objects pertaining to a given module
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_adminapi_getnextitemtype($args = array())
{
    $objs =  Dynamic_Object_Master::getObjects($args);
    return count($objs) + 1000;
}

?>