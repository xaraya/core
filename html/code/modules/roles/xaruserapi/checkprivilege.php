<?php
/**
 * Check privilege
 *
 * @package modules
 * @subpackage roles module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param   string privilege name privname
 * @param   string role ID id
 * @return  bool
 */
function roles_userapi_checkprivilege($args)
{
    extract($args);

    if(!isset($privilege)) throw new EmptyParameterException('privilege');

    if (empty($id)) $id = xarSession::getVar('id');
    $role = xarRoles::get($id);
    return $role->hasPrivilege($privilege);
}

?>
