<?php
/**
 * File: $Id$
 *
 * Check whether a user variable is available from this module
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
 * check whether a user variable is available from this module (currently unused)
 * @public
 * @author Marco Canini
 * @returns boolean
 */
function authsystem_userapi_is_valid_variable($args)
{
// TODO: differentiate between read & update - might be different

    // ...some way to check if variable is valid...

    // Authsystem can handle all user variables
    return true;
}

?>