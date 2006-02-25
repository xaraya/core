<?php
/**
 * Set a user variable
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem
 */
/**
 * set a user variable (currently unused)
 * @public
 * @author Gregor J. Rothfuss
 * @param args['uid'] user id
 * @param args['name'] variable name
 * @param args['value'] variable value
 * @returns bool
 */
function authsystem_userapi_set_user_variable($args)
{
    extract($args);

    if (!isset($uid) || !isset($name) || !isset($value)) {
        throw new BadParameterException(array($uid,$name,$value),'Empty uid (#(1)) or name (#(2)) or value (#(3)).');
    }

    // ...update the user variable in the external auth system if applicable...

    // throw back an exception if the user doesn't exist
    //if (...) {
    //    throw new IDNotFoundException($uid,'User identified by uid #(1) does not exist.');
    //}

    return true;
}

?>
