<?php
/**
 * Log a user in
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * log a user in
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] user name of user
 * @param $args['pass'] password of user
 * @param $args['rememberme'] remember this user (optional)
 * @returns int
 * @return true on success, false on failure
 */
function roles_userapi_login($args)
{
    extract($args);

    // FIXME: this should be removed as far as possible
    assert('!isset($passwd);/* Do not use \'passwd\', use \'pass\' instead. This function\'s signature has changed');

    if (!isset($rememberme)) $rememberme = 0;
    if (!isset($uname)) throw new EmptyParameterException('uname');
    if (!isset($pass))  throw new EmptyParameterException('pass');

    return xarUserLogIn($uname, $pass, $rememberme);
}

?>
