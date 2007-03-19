<?php
/**
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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

    $roles = xarModAPIFunc('roles', 'user', 'getallroles',$args);
    $items = $roles['nativeitems'];
    $objectlists = $roles['dditems'];

    // keep track of the selected uid's

    $itemlabels = array(xarML('ID'),xarML('Name'),xarML('Itemtype'),xarML('Users'),xarML('User Name'),xarML('Password'),xarML('Email'),xarML('Date Registered'),xarML('State'),xarML('Validation Code'),xarML('Created By'),);
    $ddlabels = xarModAPIFunc('dynamicdata','user','getitemfields',array('modid' => 27, 'itemtype' => $args['itemtype']));
    foreach ($ddlabels as $label) $itemlabels[] = $label['label'];

    $data['total'] = count($items);
    $data['itemtype'] = $args['itemtype'];
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $data['itemtype']));
    $types = xarModAPIFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$data['itemtype']]['label'];
    $data['items'] = $items;
    $data['objectlists'] = array($objectlists);
    $data['itemlabels'] = $itemlabels;
    if (!isset($order)) $data['order'] = 'name';
    if (!isset($search)) $data['search'] = '';
    if (!isset($startnum)) $data['startnum'] = 1;
    if (!isset($numitems)) $numitems = xarModGetVar('roles', 'itemsperpage');

    $numitems = xarModGetVar('roles', 'itemsperpage');
    $pagerfilter['order'] = $data['order'];
    $pagerfilter['search'] = $data['search'];
    $pagerfilter['startnum'] = '%%';

    $data['pager'] = xarTplGetPager(
        $data['startnum'],
        $data['total'],
        xarModURL('roles', 'user', 'view', $pagerfilter),
        $numitems
    );

    return xarTplModule($args['tplmodule'],'user','view',$data,$args['template']);
}

?>
