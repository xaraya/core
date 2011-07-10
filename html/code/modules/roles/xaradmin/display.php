<?php
/**
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
 * display role
 * @return array data for the template display
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

    // Security
    if (!xarSecurityCheck('EditRoles',1,'Roles',$name)) return;
    
    $data['frozen'] = xarSecurityCheck('ViewRoles',0,'Roles',$name);

    $data['id'] = $id;

    $types = xarMod::apiFunc('roles','user','getitemtypes');

    $data['name'] = $name;

    $item = $data;
    $item['exclude_module'] = array('dynamicdata');
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('id' => $id));
    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $id, $item);
    $data['hooks'] = $hooks;
    $data['object'] = $role;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>
