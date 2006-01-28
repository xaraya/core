<?php
/**
 * Add a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/**
 * addGroup - add a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gname'] group name to add
 * @return true on success, false if group exists
 */
function roles_adminapi_addgroup($args)
{
    extract($args);

    if(!isset($gname)) {
        $msg = xarML('Wrong arguments to groups_adminapi_addgroup.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// Security Check
    if(!xarSecurityCheck('AddRole')) return;

    return xarMakeGroup($gname);
}

?>