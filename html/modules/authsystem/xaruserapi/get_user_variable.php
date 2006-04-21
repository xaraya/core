<?php
/**
 * Get a user variable
 *
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
 * @param args['uid'] user id
 * @param args['name'] variable name
 * @return string
 */
function authsystem_userapi_get_user_variable($args)
{
    // Second level cache
    static $vars = array();

    extract($args);

    if (!isset($uid) || !isset($name)) {
        $msg = xarML('Empty uid (#(1)) or name (#(2))', $uid, $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if (!isset($vars[$uid])) {
        $vars[$uid] = array();
    }

    if (!isset($vars[$uid][$name])) {
        $vars[$uid][$name] = false;

        // ... retrieve the user variable somehow ...

        // throw back an exception if the user doesn't exist
        //if (...) {
        //    $msg = xarML('User identified by uid #(1) does not exist.', $uid);
        //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
        //                  new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        //    return;
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