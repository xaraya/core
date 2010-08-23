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
 * Show new role form
 *
 * @author Marc Lutolf
 * @author Johnny Robeson
 */
function roles_admin_new()
{
    if (!xarSecurityCheck('AddRoles')) return;

    if (!xarVarFetch('parentid',    'id',    $data['parentid'], (int)xarModVars::get('roles','defaultgroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',   $data['itemtype'], xarRoles::ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs',        'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm',     'str',   $confirm, '', XARVAR_NOT_REQUIRED)) return;

    if ($data['itemtype'] == ROLES_USERTYPE) $name = 'roles_users';
    elseif ($data['itemtype'] == ROLES_GROUPTYPE) $name = 'roles_groups';

    $data['object'] = DataObjectMaster::getObject(array('name'   => $name));

    // call item new hooks
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype'];
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    if ($confirm) {
        // Check for a valid confirmation key
        if(!xarSecConfirmAuthKey()) return;

        // Get the data from the form
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTplModule('roles','admin','new', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['object']->createItem();

            // Jump to the next page
            xarController::redirect(xarModURL('roles','admin','new'));
            return true;
        }
    }
    return $data;
}
?>
