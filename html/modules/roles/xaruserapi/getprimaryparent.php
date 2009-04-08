<?php
/**
 * Get a role's primary parent group
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param int $itemid whether
 * @return id representing the role's primary parent group
 */
function roles_userapi_getprimaryparent($args)
{
    extract($args);

    if (!empty($itemid) && !is_numeric($itemid)) {
        throw new VariableValidationException(array('itemid',$itemid,'numeric'));
    }

    $parentid = xarModItemVars::get('roles','primaryparent',$itemid);
    $role = xarRoles::get($itemid);
    $parents = $role->getParents();
    //CHECKME: the better way would be to have the default primary parent modvar be null, rather than Everybody
    // then this looping would be unnecessary
    $validparent = false;
    foreach ($parents as $parent) {
        if ($parentid == $parent->getID()) $validparent = true;
    }
    if (!$validparent) $parentid = $parents[0]->getID();

    return $parentid;
}
?>
