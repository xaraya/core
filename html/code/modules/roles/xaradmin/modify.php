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
 * Modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modify()
{
    if (!xarVarFetch('id', 'id', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET)) return;
    $id = isset($itemid) ? $itemid : $id;

    if (!xarVarFetch('duvs', 'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    $object = xarRoles::get($id);
    $data['basetype'] = $object->getType();

    $parents = array();
    $names = array();

    foreach ($object->getParents() as $parent) {
        if(xarSecurityCheck('RemoveRole',0,'Relation',$parent->getName() . ":" . $object->getName())) {
            $parents[] = array('parentid' => $parent->getID(),
                               'parentname' => $parent->getName(),
                               'parentuname'=> $parent->getUname());
            $names[] = $parent->getName();
        }
    }

    $groups = array();
    foreach(xarRoles::getgroups() as $temp) {
        $nam = $temp['name'];
        // TODO: this is very inefficient. Here we have the perfect use case for embedding security checks directly into the SQL calls
        if(!xarSecurityCheck('AttachRole',0,'Relation',$nam . ":" . $object->getName())) continue;
        if (!in_array($nam, $names) && $temp['id'] != $id) {
            $names[] = $nam;
            $groups[] = array('did' => $temp['id'],
                'dname' => $temp['name']);
        }
    }

    xarSession::setVar('ddcontext.roles', array(
                                            'return_url' => xarServer::getCurrentURL(),
                                            'parents' => $parents,
                                            'groups' => $groups,
                                            'basetype' => $data['basetype'],
                                                ));

    if (!xarSecurityCheck('EditRole',0,'Roles',$object->getName())) {
        if (!xarSecurityCheck('ReadRole',1,'Roles',$object->getName())) return;
    }

    $data['object'] = &$object;

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']= 'roles';
    $item['itemtype'] = $object->getType();
    $item['itemid']= $id;
    $data['hooks'] = xarModCallHooks('item', 'modify', $id, $item);

    $data['groups'] = $groups;
    $data['parents'] = $parents;
    return $data;
}
?>
