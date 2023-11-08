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
 * Show new role form
 *
 * @author Marc Lutolf
 * @author Johnny Robeson
 * @return array<mixed>|string|bool|void data for the template display
 */
function roles_admin_new()
{
    // Security
    if (!xarSecurity::check('AddRoles')) return;

    $data = [];
    if (!xarVar::fetch('parentid',    'id',    $data['parentid'], (int)xarModVars::get('roles','defaultgroup'), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('itemtype',    'int',   $data['itemtype'], xarRoles::ROLES_USERTYPE, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('duvs',        'array', $data['duvs'], array(), xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('confirm',     'str',   $confirm, '', xarVar::NOT_REQUIRED)) return;

    if ($data['itemtype'] == xarRoles::ROLES_USERTYPE) $name = 'roles_users';
    elseif ($data['itemtype'] == xarRoles::ROLES_GROUPTYPE) $name = 'roles_groups';

    $data['object'] = DataObjectFactory::getObject(array('name'   => $name));

    // call item new hooks
    $item = $data;
    $item['exclude_module'] = array('dynamicdata');
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype'];
    $data['hooks'] = xarModHooks::call('item', 'new', '', $item);

    if ($confirm) {
        // Check for a valid confirmation key
        if(!xarSec::confirmAuthKey()) return;

        // Enforce a check on the existence of a user of this user name
        $data['object']->properties['uname']->validation_existrule = 1;
        
        // Get the data from the form
        $isvalid = $data['object']->checkInput();

        if (!$isvalid) {
            // Bad data: redisplay the form with error messages
            return xarTpl::module('roles','admin','new', $data);        
        } else {
            // Good data: create the item
            $itemid = $data['object']->createItem();

            // Jump to the next page
            xarController::redirect(xarController::URL('roles','admin','new'));
            return true;
        }
    }
    return $data;
}
