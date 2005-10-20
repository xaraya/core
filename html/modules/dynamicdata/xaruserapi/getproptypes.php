<?php
/**
 * File: $Id$
 *
 * Get the list of defined property types 
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
 * get the list of defined property types from somewhere...
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of property types
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    return Dynamic_Property_Master::getPropertyTypes();
}

?>