<?php
/**
 * Get the next itemtype of extended objects pertaining to a given module
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
    extract($args);
    if (empty($modid)) throw new EmptyParameterException('modid');
	$types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $modid));
	$ids = array_keys($types);
	sort($ids);
	$lastid = array_pop($ids);
	if ($modid == 182) return $lastid + 1;
	else return max(1000,$lastid + 1);
}

?>