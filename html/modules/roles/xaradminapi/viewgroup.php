<?php
/**
 * View users in a group
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
 * viewgroup - view users in group
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['pid'] group id
 * @return $users array containing uname, pid
 */
function roles_adminapi_viewgroup($args)
{
    return xarModAPIFunc('roles','user','getusers',$args);

}

?>
