<?php
/**
 * File: $Id$
 *
 * Shows the user login form when login block is not active
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Shows the user login form when login block is not active
 */
function roles_user_showloginform($args = array())
{
    #redirecturl
    extract($args);
    if (!isset($redirecturl)) $redirecturl = 'index.php';

    xarVarFetch('redirecturl', 'str', $data['redirecturl'], $redirecturl, XARVAR_NOT_REQUIRED);


    if (!xarUserIsLoggedIn()) {
      // Security check
      if (!xarSecurityCheck('ViewRoles')) return;
      $data['loginlabel'] = xarML('Log In');


      return $data;
    } else {
      xarResponseRedirect($data['redirecturl']);
    }// if
}
?>