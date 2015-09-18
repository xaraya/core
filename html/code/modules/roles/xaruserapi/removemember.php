<?php
/**
 * Remove a role from a group
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * removemember - remove a role from a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['gid'] group id<br/>
 *        integer  $args['id'] role id
 * @return boolean true on succes, false on failure
 */
function roles_userapi_removemember(Array $args=array())
{
    extract($args);

    if (!isset($gid)) throw new EmptyParameterException('gid');
    if (!isset($id)) throw new EmptyParameterException('id');

    $group = xarRoles::get($gid);
    if($group->isUser()) throw new IDNotFoundException($gid);
    $user = xarRoles::get($id);

// Security Check
    if(!xarSecurityCheck('RemoveRole',1,'Relation',$group->getName() . ":" . $user->getName())) return;

    if (!$group->removeMember($user)) return;

    return true;
}

?>