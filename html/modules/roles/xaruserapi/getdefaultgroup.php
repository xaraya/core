<?php
/**
 * Get the group that users are members of by default
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * getdefaultgroup - get the group that users are members of by default
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return string groupname
 */
function roles_userapi_getdefaultgroup()
{
    $defaultgroup = xarModGetVar('roles','defaultgroup');
    if(empty($defaultgroup)) {
        $defaultgroup = 'Users'; // TODO: improve on this hardwiring
    }
    return $defaultgroup;
}

?>
