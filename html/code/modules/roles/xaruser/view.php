<?php
/**
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * view users
 */
function roles_user_view($args)
{
    if (!xarSecurityCheck('ViewRoles')) return;

    // members list disabled? only show to roles admins
    if ((bool)xarModVars::get('roles', 'displayrolelist') == false && !xarSecurityCheck('AdminRoles', 0)) {
        xarController::$response->redirect(xarModURL('roles', 'user', 'main'));
    }
//    extract($args);

    if(!xarVarFetch('startnum', 'int:1', $args['startnum'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('itemtype', 'int', $args['itemtype'], ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('search', 'str:1:100', $args['search'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('order', 'str', $args['order'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('include', 'str', $args['include'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('exclude', 'str', $args['exclude'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule', 'str', $args['tplmodule'], 'roles', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'str', $args['template'], '', XARVAR_NOT_REQUIRED)) {return;}

    $data['items'] = array();
    $data['pager'] = '';

    $roles = xarMod::apiFunc('roles', 'user', 'getallroles',$args);
    $items = $roles['nativeitems'];
    $objectlists = $roles['dditems'];

    // keep track of the selected id's

    $itemlabels = array(xarML('ID'),xarML('Name'),xarML('Itemtype'),xarML('Users'),xarML('User Name'),xarML('Password'),xarML('Email'),xarML('Date Registered'),xarML('State'),xarML('Validation Code'),xarML('Created By'),);
    $ddlabels = xarMod::apiFunc('dynamicdata','user','getitemfields',array('modid' => 27, 'itemtype' => $args['itemtype']));
    foreach ($ddlabels as $label) $itemlabels[] = $label['label'];

    $data['total'] = count($items);
    $data['itemtype'] = $args['itemtype'];
    $data['basetype'] = $data['itemtype'];
    $types = xarMod::apiFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['items'] = $items;
    $data['objectlists'] = array($objectlists);
    $data['itemlabels'] = $itemlabels;
    if (!isset($order)) $data['order'] = 'name';
    if (!isset($search)) $data['search'] = '';
    $data['startnum'] = (!isset($args['startnum'])) ? 1 : $args['startnum'];
    if (!isset($numitems)) $numitems = (int)xarModVars::get('roles', 'items_per_page');

    $numitems = (int)xarModVars::get('roles', 'items_per_page');
    $pagerfilter['order'] = $data['order'];
    $pagerfilter['search'] = $data['search'];
    $pagerfilter['startnum'] = '%%';

    $data['itemsperpage'] = $numitems;
    $data['urltemplate'] = xarModURL('roles', 'user', 'view', $pagerfilter);
    $data['urlitemmatch'] = '%%';

    return xarTplModule($args['tplmodule'],'user','view',$data,$args['template']);
}

?>