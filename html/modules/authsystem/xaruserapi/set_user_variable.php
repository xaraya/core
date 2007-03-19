<?php
/**
 * Set a user variable
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * set a user variable (currently unused)
 * @access public
 * @author Gregor J. Rothfuss
 * @param args['id'] user id
 * @param args['name'] variable name
 * @param args['value'] variable value
 * @return bool
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
    //    throw new IDNotFoundException($uid,'User identified by id #(1) does not exist.');
    //}

    return true;
}

?>
