<?php
/**
 * Rename a group
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
 * renamegroup - rename a group
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id'] group id
 * @param $args['gname'] group name
 * @return true on success, false on failure.
 */
function roles_adminapi_renamegroup($args)
{
    extract($args);

    if (!isset($id))  throw new EmptyParameterException('id');
    if (!isset($gname)) throw new EmptyParameterException('gname');


// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $role = xarRoles::getRole($uid);
    $role->setName($gname);

    return $role->update();
}

?>
