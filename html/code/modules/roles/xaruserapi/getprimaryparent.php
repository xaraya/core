<?php
/**
 * Get a role's primary parent group
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
 * @param array    $args array of optional parameters<br/>
 * @param int $itemid whether
 * @return integer id representing the role's primary parent group
 */
function roles_userapi_getprimaryparent(Array $args=array())
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
