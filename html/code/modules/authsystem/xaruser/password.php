<?php
/**
 * Display a password request page
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/42.html
 */

/**
 * Display a password request page.
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * 
 * @param array $args Arguments passed to Gui function. 
 * @return array Data for the display template
 */
function authsystem_user_password($args = array())
{
    return xarMod::guiFunc('roles','user','lostpassword',$args);
}
?>