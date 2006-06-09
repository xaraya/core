<?php
/**
 * Shows the user login form when login block is not active
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
    #redirecturl
    extract($args);
    if (!isset($redirecturl)) $redirecturl = xarServerGetBaseURL();

    xarVarFetch('redirecturl', 'str:1:300', $data['redirecturl'], $redirecturl, XARVAR_NOT_REQUIRED);


    if (!xarUserIsLoggedIn()) {
      // Security check
      if (!xarSecurityCheck('ViewAuthsystem')) return;
      $data['loginlabel'] = xarML('Log In');


      return $data;
    } else {
      xarResponseRedirect($data['redirecturl']);
    }// if
}
?>
