<?php
/**
 * File: $Id$
 *
 * Check whether this module has a certain capability
 *
 * @package authentication
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem
 * @author Marco Canini
*/
/**
 * check whether this module has a certain capability
 * @public
 * @param args['capability'] the capability to check for
 * @author Marco Canini
 * @returns bool
 */
function authsystem_userapi_has_capability($args)
{
    extract($args);

    assert('isset($capability)');

    switch($capability) {
        case XARUSER_AUTH_AUTHENTICATION:
            return true;
            break;
        case XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER:
        case XARUSER_AUTH_USER_ENUMERABLE:
        case XARUSER_AUTH_PERMISSIONS_OVERRIDER:
        case XARUSER_AUTH_USER_CREATEABLE:
        case XARUSER_AUTH_USER_DELETEABLE:
            return false;
            break;
    }
    $msg = xarML('Unknown capability.');
    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                   new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
    return;
}

?>