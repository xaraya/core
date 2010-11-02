<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */

function authsystem_user_password($args = array())
{
    return xarMod::guiFunc('roles','user','lostpassword',$args);
}
?>