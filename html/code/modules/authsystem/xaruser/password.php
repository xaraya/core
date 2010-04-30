<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
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