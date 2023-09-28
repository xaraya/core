<?php
/**
 * Check privilege
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['privilege'] name of a privilege<br/>
 *        string   $args['role_id'] id of a role
 * @return  boolean
 */
function roles_userapi_checkprivilege(Array $args=array())
{
    extract($args);

    if(!isset($privilege)) throw new EmptyParameterException('privilege');

    if (empty($id)) $id = xarSession::getVar('role_id');
    $role = xarRoles::get($id);
    return $role->hasPrivilege($privilege);
}
