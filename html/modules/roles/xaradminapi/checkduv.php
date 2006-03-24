<?php
/**
 * Check a roles DUV
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * checkduv - perform an existence or active check on a DUV
 * @author - Marc Lutolf
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
			$duvs = xarModGetVar('roles',$name);
			if (isset($duvs)) {
				$result = true;
			}
			break;
		case 1 :
		default: 
            // TODO: investigate how this case would differ now or
            //   how the State has been handled since conversion to moduservars
			$result = false;
			$duvs = xarModGetVar('roles',$name);
			if (isset($duvs)) {
    			$result = true;
			}
	}
	return $result;
}

?>