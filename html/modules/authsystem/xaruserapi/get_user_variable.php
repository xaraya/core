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
 * @access public
 * @author Marco Canini
 * @param args['id'] user id
 * @param args['name'] variable name
 * @return string
 */
function authsystem_userapi_get_user_variable($args)
{
    // Second level cache
    static $vars = array();

    extract($args);

    if (!isset($id) || !isset($name)) {
        throw new BadParameterException(array($id,$name),'Empty id (#(1)) or name (#(2))');
    }

    if (!isset($vars[$id])) {
        $vars[$id] = array();
    }

    if (!isset($vars[$id][$name])) {
        $vars[$id][$name] = false;

        // ... retrieve the user variable somehow ...

        // throw back an exception if the user doesn't exist
        //if (...) {
        //    throw new IDNotFoundException($id,'User identified by id #(1) does not exist.');
        //}

        // $vars[$id][$name] = $value;
    }

    // Return the variable
    if (isset($vars[$id][$name])) {
        return $vars[$id][$name];
    } else {
        return false;
    }
}

?>
