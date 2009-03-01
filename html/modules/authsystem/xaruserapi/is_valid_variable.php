<?php
/**
 * Check whether a user variable is available
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * check whether a user variable is available from this module (currently unused)
 * @public
 * @author Marco Canini
 * @return boolean true
 */
function authsystem_userapi_is_valid_variable($args)
{
// TODO: differentiate between read & update - might be different

    // ...some way to check if variable is valid...

    // Authsystem can handle all user variables
    return true;
}

?>
