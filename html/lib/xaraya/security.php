<?php
/**
 *
 * @package core\security
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
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

// @todo Maybe changing this touch to a centralized API would be a good idea?
//Even if in the end it would use touched files too...
if (file_exists(sys::varpath() . '/security/on.touch')) {
    sys::import('xaraya.xarCacheSecurity');
}

// FIXME: Can we reverse this? (i.e. the module loading the files from here?)
//        said another way, can we move the two files to /includes (partially preferably)
sys::import('modules.privileges.class.privileges');
sys::import('modules.roles.class.roles');

// @todo move xarSecurity class from privileges to here or keep it modular?

/**
 * Move public static functions to class
 *
 * @package core\security
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
     * @param string modName the module this authorisation key is for (optional)
     * @return string an encrypted key for use in authorisation of operations
     * @todo bring back possibility of extra security by using date (See code)
     */
    public static function genAuthKey($modName = NULL)
    {
        if (empty($modName)) {
            list($modName) = xarController::getRequest()->getInfo();
        }

        // Date gives extra security but leave it out for now
        // $key = xarSession::getVar('rand') . $modName . date ('YmdGi');
        $key = xarSession::getVar('rand') . strtolower($modName);

        // Encrypt key
        $authid = md5($key);

        // Tell xarCache not to cache this page
        xarCache::noCache();

        // Return encrypted key
        return $authid;
    }

    /**
     * Confirm an authorisation key is valid
     *
     * See description of xarSec::genAuthKey for information on
     * this function
     *
     * @param string authIdVarName
     * @return boolean true if the key is valid, false if it is not
     * @throws ForbiddenOperationException
     * @todo bring back possibility of time authorized keys
     */
    public static function confirmAuthKey($modName=NULL, $authIdVarName='authid', $catch=false)
    {
        // We don't need this check for AJAX calls
        if (xarController::getRequest()->isAjax()) return true;

        if(!isset($modName)) list($modName) = xarController::getRequest()->getInfo();
        $authid = xarController::getVar($authIdVarName);

        // Regenerate static part of key
        $partkey = xarSession::getVar('rand') . strtolower($modName);

    // Not using time-sensitive keys for the moment
    //    // Key life is 5 minutes, so search backwards and forwards 5
    //    // minutes to see if there is a match anywhere
    //    for ($i=-5; $i<=5; $i++) {
    //        $testdate  = mktime(date('G'), date('i')+$i, 0, date('m') , date('d'), date('Y'));
    //
    //        $testauthid = md5($partkey . date('YmdGi', $testdate));
    //        if ($testauthid == $authid) {
    //            // Match
    //
    //            // We've used up the current random
    //            // number, make up a new one
    //            srand((double) microtime(true) * 1000000.0);
    //            xarSession::setVar('rand', rand());
    //
    //            return true;
    //        }
    //    }
        if ((md5($partkey)) == $authid) {
            // Match - generate new random number for next key and leave happy
            srand((double) microtime(true) * 1000000.0);
            xarSession::setVar('rand', rand());
            return true;
        }
        // Not found, assume invalid
        if ($catch) throw new ForbiddenOperationException();
        else return false;
    }
}

// Legacy calls - import by default for now...
//sys::import('xaraya.legacy.security');
