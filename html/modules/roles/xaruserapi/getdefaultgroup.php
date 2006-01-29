<?php
/**
 * Get the group that users are members of by default
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * getdefaultgroup - get the group that users are members of by default
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return string groupname
 */
function roles_userapi_getdefaultgroup()
{
	$authmodule = xarModGetVar('roles','defaultauthmodule');
    //if (!empty($authmodule)) {
    if (xarModIsAvailable('registration')) {
        $registrationgroup= xarModGetVar('registration', 'defaultgroup');
    }
    if (isset($registrationgroup)) {
      /** TODO: jojodee - We really want 'registration' module here not authmodule
	    * Maybe need to review how the vars are being used now that the modules are
        * separated out for roles, registration and authentication. For now HARDWIRED to Registration module
        */
      //$defaultgroup = xarModGetVar(xarModGetNameFromID($authmodule), 'defaultgroup');
        $defaultgroup = $registrationgroup;
    
    } else {
	// TODO: improve on this hardwiring
		$defaultgroup = 'Users';
	}
    return $defaultgroup;
}

?>
