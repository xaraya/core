<?php
/**
 * View users in group
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
 * getUsers - view users in group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] group id
 * @return array array containing uname, id of the users
 */
function roles_userapi_getUsers(Array $args=array())
{
    extract($args);

    if(!isset($id)) throw new EmptyParameterException('id');
    if(empty($id)) return array();

// Security Check
    if(!xarSecurityCheck('ReadRoles')) return;

    $role = xarRoles::get($id);

    $users = $role->getUsers();

    $flatusers = array();
    foreach($users as $user) {
        $flatusers[] = array('id' => $user->getID(),
                        'uname' => $user->getUser()
                        );
    }

    return $flatusers;
}

?>