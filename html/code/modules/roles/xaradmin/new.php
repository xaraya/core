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
    if (!xarVarFetch('confirm',     'int',   $confirm, 0, XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('module'   => 'roles', 'itemtype' => $data['itemtype']));
    $data['object']->properties['name']->display_layout = 'single';

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