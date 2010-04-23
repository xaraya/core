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

    if (!xarVarFetch('return_url',  'isset', $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('parentid',    'id',    $data['parentid'], (int)xarModVars::get('roles','defaultgroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',   $data['itemtype'], xarRoles::ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs',        'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('module'   => 'roles', 'itemtype' => $data['itemtype']-1));
    $data['object']->properties['name']->dispkay_layout = 'single';

    xarSession::setVar('ddcontext.roles', array(
                                            'return_url' => xarServer::getCurrentURL(),
                                            'basetype' => $data['itemtype'],
                                            'parentid' => $data['parentid'],
                                                ));
    // call item new hooks
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype'];
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    return $data;
}
?>