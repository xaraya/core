<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function roles_admin_modify()
{
    $data = [];
    if (!xarVar::fetch('confirm',     'int',   $confirm, 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('id', 'id', $id, 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('itemid', 'id', $data['itemid'], NULL, xarVar::DONT_SET)) return;
    $id = isset($data['itemid']) ? $data['itemid'] : $id;

    if (!xarVar::fetch('duvs', 'array', $data['duvs'], array(), xarVar::NOT_REQUIRED)) return;

    $data['object'] = xarRoles::get($id);
    if (empty($data['object'])) return xarResponse::NotFound();
    $data['object']->properties['name']->display_layout = 'single';
    $data['itemtype'] = $data['object']->getType();

    $parents = array();
    $names = array();

    foreach ($data['object']->getParents() as $parent) {
        if(xarSecurity::check('RemoveRole',0,'Relation',$parent->getName() . ":" . $data['object']->getName())) {
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
        if(!xarSecurity::check('AttachRole',0,'Relation',$nam . ":" . $data['object']->getName())) continue;
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
                                            'basetype' => $data['itemtype'],
                                                ));

    // Security
    if (!xarSecurity::check('EditRole',0,'Roles',$data['object']->getName())) {
        if (!xarSecurity::check('ReadRoles',1,'Roles',$data['object']->getName())) return;
    }

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['exclude_module'] = array('dynamicdata');
    $item['module']= 'roles';
    $item['itemtype'] = $data['object']->getType();
    $item['itemid']= $id;
    $data['hooks'] = xarModHooks::call('item', 'modify', $id, $item);

    $data['groups'] = $groups;
    $data['parents'] = $parents;

    if ($confirm) {

        // Check for a valid confirmation key
        if(!xarSec::confirmAuthKey()) return;

        // Enforce a check on the existence of a user of this user name
        $data['object']->properties['uname']->validation_existrule = 1;
        
        // Get the data from the form
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTpl::module('roles','admin','modify', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));

            // Jump to the next page
            xarController::redirect(xarController::URL('roles','admin','modify',array('itemid' => $data['itemid'])));
            return true;
        }
    }
    return $data;
}
