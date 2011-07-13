<?php
/**
 * Add a role to a group
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * addmember - add a role to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['gid'] group id<br/>
 *        integer  $args['id'] role id
 * @return boolean true on succes, false on failure
 */
function roles_userapi_addmember(Array $args=array())
{
    extract($args);

    if (!isset($gid)) throw new EmptyParameterException('gid');
    if (!isset($id)) throw new EmptyParameterException('id');

    $group = xarRoles::get($gid);
    if($group->isUser()) throw new IDNotFoundException($gid);

    $user = xarRoles::get($id);

// Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$group->getName() . ":" . $user->getName())) return;

    if (!$group->addMember($user)) return;

    return true;
}

?>