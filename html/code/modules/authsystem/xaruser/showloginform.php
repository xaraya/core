<?php
/**
 * Display the user login form
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the user login form when login block is not active
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodeexaraya.com>
 */
function authsystem_user_showloginform(Array $args = array())
{
    extract($args);
    xarVarFetch('redirecturl', 'str:1:254', $data['redirecturl'], xarServer::getBaseURL(), XARVAR_NOT_REQUIRED);

    return $data;
    
    if (!xarUserIsLoggedIn()) {
        return $data;
    } else {
        xarController::redirect($data['redirecturl']);
        return true;
    }
}
?>
