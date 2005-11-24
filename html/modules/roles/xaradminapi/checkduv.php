<?php
/**
 * Check a roles DUV
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * checkduv - perform an existence or active check on a DUV
 * @param $args['name'] name of the duv
 * @param $args['check'] type of check to be performed
 * @return boolean
 */
function roles_adminapi_checkduv($args)
{
    extract($args);
    if (!isset($name)) return false;
    $state = isset($state) ? $state : 0;

	switch ($state) {
		case 0 :
			$result = false;
			$duvs = xarModGetVar('roles','duvs');
			if (isset($duvs)) {
				$duvs = unserialize($duvs);
				if (isset($duvs[$name])) {
					$result = true;
				}
			}
			break;
		case 1 :
		default:
			$result = false;
			$duvs = xarModGetVar('roles','duvs');
			if (isset($duvs)) {
				$duvs = unserialize($duvs);
				if (isset($duvs[$name]) && $duvs[$name]['state']) {
					$result = true;
				}
			}
	}
	return $result;
}

?>