<?php
/**
 * Get ancestors of a role
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getancestors - get ancestors of a role
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['id'] role id
 * @return array array containing name, id of the ancstors
 */
function roles_userapi_getancestors(Array $args=array())
{
    extract($args);

    if(!isset($id)) throw new EmptyParameterException('id');

    if(!xarSecurityCheck('ReadRoles')) return;

    $role = xarRoles::get($id);

    if (empty($args['parents'])) {
        $ancestors = $role->getRoleAncestors();
    } else {
        $ancestors = $role->getParents();
    }

    $flatancestors = array();
    foreach($ancestors as $ancestor) {
        $flatancestors[] = array('id' => $ancestor->getID(),
                        'name' => $ancestor->getName()
                        );
    }
    return $flatancestors;
}
?>