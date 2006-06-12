<?php
/**
 * Get a user variable
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * get a user variable (currently unused)
 * @public
 * @author Marco Canini
 * @param args['uid'] user id
 * @param args['name'] variable name
 * @returns string
 */
function authsystem_userapi_get_user_variable($args)
{
    // Second level cache
    static $vars = array();

    extract($args);

    if (!isset($uid) || !isset($name)) {
        throw new BadParameterException(array($uid,$name),'Empty uid (#(1)) or name (#(2))');
    }

    if (!isset($vars[$uid])) {
        $vars[$uid] = array();
    }

    if (!isset($vars[$uid][$name])) {
        $vars[$uid][$name] = false;

        // ... retrieve the user variable somehow ...

        // throw back an exception if the user doesn't exist
        //if (...) {
        //    throw new IDNotFoundException($uid,'User identified by uid #(1) does not exist.');
        //}

        // $vars[$uid][$name] = $value;
    }

    // Return the variable
    if (isset($vars[$uid][$name])) {
        return $vars[$uid][$name];
    } else {
        return false;
    }
}

?>
