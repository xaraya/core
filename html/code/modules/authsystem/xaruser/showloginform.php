<?php
/**
 * Shows the user login form when login block is not active
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the user login form when login block is not active
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodeexaraya.com>
 */
function authsystem_user_showloginform($args = array())
{
    extract($args);
    xarVarFetch('redirecturl', 'str:1:254', $data['redirecturl'], xarServer::getBaseURL(), XARVAR_NOT_REQUIRED);

    if (!xarUserIsLoggedIn()) {
        return $data;
    } else {
        xarResponse::redirect($data['redirecturl']);
        return true;
    }
}
?>