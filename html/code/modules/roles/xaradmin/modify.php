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
 * Modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function roles_admin_modify()
{
    if (!xarVarFetch('confirm',     'int',   $confirm, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('id', 'id', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $data['itemid'], NULL, XARVAR_DONT_SET)) return;
    $id = isset($data['itemid']) ? $data['itemid'] : $id;

    if (!xarVarFetch('duvs', 'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    $data['object'] = xarRoles::get($id);
    if (empty($data['object'])) return xarResponse::NotFound();
    $data['object']->properties['name']->display_layout = 'single';
    $data['itemtype'] = $data['object']->getType();

    $parents = array();
    $names = array();

    foreach ($data['object']->getParents() as $parent) {
        if(xarSecurityCheck('RemoveRole',0,'Relation',$parent->getName() . ":" . $data['object']->getName())) {
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
        if(!xarSecurityCheck('AttachRole',0,'Relation',$nam . ":" . $data['object']->getName())) continue;
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
    if (!xarSecurityCheck('EditRole',0,'Roles',$data['object']->getName())) {
        if (!xarSecurityCheck('ReadRoles',1,'Roles',$data['object']->getName())) return;
    }

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['exclude_module'] = array('dynamicdata');
    $item['module']= 'roles';
    $item['itemtype'] = $data['object']->getType();
    $item['itemid']= $id;
    $data['hooks'] = xarModCallHooks('item', 'modify', $id, $item);

    $data['groups'] = $groups;
    $data['parents'] = $parents;

    if ($confirm) {

        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        // Get the data from the form
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTplModule('roles','admin','modify', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['object']->updateItem(array('itemid' => $data['itemid']));

            // Jump to the next page
            xarController::redirect(xarModURL('roles','admin','modify',array('itemid' => $data['itemid'])));
            return true;
        }
    }
    return $data;
}
?>
