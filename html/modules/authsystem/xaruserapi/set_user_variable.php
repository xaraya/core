<?php
/**
 * File: $Id$
 *
 * Set a user variable
 *
 * @package authentication
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem
 * @author Gregor J. Rothfuss
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
        $msg = xarML('Empty uid (#(1)) or name (#(2)) or value (#(3)).', $uid, $name, $value);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // ...update the user variable in the external auth system if applicable...

    // throw back an exception if the user doesn't exist
    //if (...) {
    //    $msg = xarML('User identified by uid #(1) does not exist.', $uid);
    //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
    //                  new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
    //    return;
    //}

    return true;
}

?>