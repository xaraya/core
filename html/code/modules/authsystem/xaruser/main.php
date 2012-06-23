<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * The main user interface function of this module.
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  
 * The function redirects to the showloginform funtion.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare<jojodee@xaraya.com>
 * @return boolean true after redirection
 */
function authsystem_user_main()
{
    //no registration here - just redirect to the login form
    xarController::redirect(xarModURL('authsystem','user','showloginform'));
    return true;
}

?>