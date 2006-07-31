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

    extract($args);
    if (!isset($redirecturl)) $redirecturl = xarServerGetBaseURL();
    xarVarFetch('redirecturl', 'str:1:254', $data['redirecturl'], $redirecturl, XARVAR_NOT_REQUIRED);
    
    $defaultauthdata=xarModAPIFunc('roles','user','getdefaultauthdata');
    $defaultloginmodname=$defaultauthdata['defaultloginmodname'];

    if (!xarUserIsLoggedIn()) {
      // Security check
      // TODO: if exception redirects are set to ON we end up here, if further
      // more anon has no priv for ViewAuthSystem, we end up here again => infinite loop
      // 1. augment (i.e. hack it in) to force the check to go?
      // 2. why is this security check here in the first place (a usecase would be nice)
      
      if (!xarSecurityCheck('ViewAuthsystem')) return;
      $data['loginlabel'] = xarML('Log In');
      $data['loginurl']=xarModURL($defaultloginmodname,'user','login');

      return $data;
    } else {
      xarResponseRedirect($data['redirecturl']);
    }// if
}
?>