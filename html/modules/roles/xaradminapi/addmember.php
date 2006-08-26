<?php
/**
 * Add a user to a group
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * insertuser - add a user to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user id
 * @param $args['gid'] group id
 * @return true on succes, false on failure
 */
function roles_adminapi_addmember($args)
{
    return xarModAPIFunc('roles','user','addmember',$args);
}

?>
