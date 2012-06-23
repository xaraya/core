<?php
/**
 * Log a user on the system
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 *
 * @author Jan Schrage
 * @author John Cox
 * @author Gregor Rothfuss
 */
/**
 * log a user in
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array   $args array of optional parameters<br/>
 *        string  $args['uname'] user name of user<br/>
 *        string  $args['pass'] password of user<br/>
 *        string  $args['rememberme'] remember this user (optional)
 * @return boolean true on success, false on failure
 */
function authsystem_userapi_login(Array $args=array())
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
        throw new BadParameterException(null,xarML('Wrong arguments to authsystem_userapi_login.'));
    }

    return xarUserLogIn($uname, $pass, $rememberme);
}

?>