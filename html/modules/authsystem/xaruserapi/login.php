<?php
/**
 * Log a user in
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */
/**
 * log a user in
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] user name of user
 * @param $args['pass'] password of user
 * @param $args['rememberme'] remember this user (optional)
 * @returns int
 * @return true on success, false on failure
 */
function authsystem_userapi_login($args)
{
    extract($args);

    // FIXME: this should be removed as far as possible
    if (isset($passwd) && !isset($pass)) {
        die("authsystem_userapi_login: authsystem_userapi_login prototype has changed, " .
            "you should use pass instead of passwd to " .
            "avoid this message being displayed");
    }

    if (!isset($rememberme)) {
        $rememberme = 0;
    }

    if ((!isset($uname)) ||
        (!isset($pass))) {
        $msg = xarML('Wrong arguments to authsystem_userapi_login.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    return xarUserLogIn($uname, $pass, $rememberme);
}

?>