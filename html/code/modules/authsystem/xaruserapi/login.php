<?php
/**
 * Log a user on the system
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/42.html
 *
 * @author Jan Schrage
 * @author John Cox
 * @author Gregor Rothfuss
 */

/**
 *Api function to log a user on to the system
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param  string[]  $args Array of optional parameters<br/>
 *         string    $args['uname'] User name of user<br/>
 *         string    $args['pass'] Password of user<br/>
 *         string    $args['rememberme'] Remember this user (optional)
 * @return boolean Returns true on success, false upon failure
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