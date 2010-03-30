<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * display role
 */
function roles_admin_display()
{
    if (!xarVarFetch('itemtype','id',$itemtype, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();


    $data = array();
    sys::import('modules.roles.class.roles');
    $role = xarRoles::get($id);

    $data['itemtype'] = $role->getType();
    $data['basetype'] = $data['itemtype'];

    $object = xarMod::apiFunc('dynamicdata','user','getobject',array('module'   => 'roles',
                                                'itemtype' => $data['basetype']));

    $itemid = $object->getItem(array('itemid' => $id));

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
                           'parentname' => $parent->getName(),
                           'parentuname' => $parent->getUname());
    }
    $data['parents'] = $parents;

    $name = $role->getName();

    if (!xarSecurityCheck('EditRole',1,'Roles',$name)) return;
    $data['frozen'] = xarSecurityCheck('ViewRoles',0,'Roles',$name);

    $data['id'] = $id;

    $types = xarMod::apiFunc('roles','user','getitemtypes');

    $data['name'] = $name;

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('id' => $id));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $id, $item);
    $data['hooks'] = $hooks;
    $data['object'] = & $object;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>