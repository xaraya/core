<?php

/**
 *
 * @package core\security
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Richard Cave <rcave@xaraya.com>
 * @todo bring back possibility of time authorized keys
 */

/**
 * Notes on security system
 *
 * Special ID and GIDS:
 *  ID -1 corresponds to 'all users', includes unregistered users
 *  GID -1 corresponds to 'all groups', includes unregistered users
 *  ID 0 corresponds to unregistered users
 *  GID 0 corresponds to unregistered users
 *
 */

use Xaraya\Services\xar;

/**
 * @deprecated 2.4.1 cachesecurity module = gone
// @todo Maybe changing this touch to a centralized API would be a good idea?
//Even if in the end it would use touched files too...
if (file_exists(sys::varpath() . '/security/on.touch')) {
    sys::import('xaraya.xarCacheSecurity');
}
 */

// @todo move xarSecurity class from privileges to here or keep it modular?

/**
 * Move public static functions to class
 *
 * @package core\security
 * @deprecated 2.8.5 use xar::sec() instead
 */
class xarSec extends xarObject
{
    /**
     * Generate an authorisation key
     *
     * The authorisation key is used to confirm that actions requested by a
     * particular user have followed the correct path.  Any stage that an
     * action could be made (e.g. a form or a 'delete' button) this function
     * must be called and the resultant string passed to the client as either
     * a GET or POST variable.  When the action then takes place it first calls
     * xarSec::confirmAuthKey() to ensure that the operation has
     * indeed been manually requested by the user and that the key is valid
     *
     * @param ?string $modName the module this authorisation key is for (optional)
     * @return string an encrypted key for use in authorisation of operations
     * @todo bring back possibility of extra security by using date (See code)
     */
    public static function genAuthKey($modName = null)
    {
        return xar::sec()->genAuthKey($modName);
    }

    /**
     * Confirm an authorisation key is valid
     *
     * See description of xarSec::genAuthKey for information on
     * this function
     *
     * @param ?string $modName
     * @param string $varName
     * @return boolean $catch true if the key is valid, false if it is not
     * @throws ForbiddenOperationException
     * @todo bring back possibility of time authorized keys
     */
    public static function confirmAuthKey($modName = null, $varName = 'authid', $catch = false)
    {
        return xar::sec()->confirmAuthKey($modName, $varName, $catch);
    }
}
