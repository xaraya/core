<?php
/**
 * Display a password request page
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 * @return array data for the template display
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */

function authsystem_user_password($args = array())
{
    return xarMod::guiFunc('roles','user','lostpassword',$args);
}
?>