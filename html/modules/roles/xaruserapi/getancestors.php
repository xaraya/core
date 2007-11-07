<?php
/**
 * Get ancestors of a role
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
 * getancestors - get ancestors of a role
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id'] role id
 * @return $ancestors array containing name, id
 */
function roles_userapi_getancestors($args)
{
    extract($args);

    if(!isset($id)) throw new EmptyParameterException('id');

    if(!xarSecurityCheck('ReadRole')) return;

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
