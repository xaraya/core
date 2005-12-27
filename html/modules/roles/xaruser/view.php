<?php
/**
 * View roles
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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
    if (!xarVarFetch('itemtype', 'int', $args['itemtype'], 0, XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('search', 'str:1:100', $args['search'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('order', 'str', $args['order'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('exclude', 'str', $args['exclude'], NULL, XARVAR_NOT_REQUIRED)) {return;}

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
    $data['objectlists'] = $objectlists;
    $data['itemlabels'] = $itemlabels;
    if (!isset($order)) $data['order'] = 'xar_name';
    if (!isset($search)) $data['search'] = '';
    if (!isset($startnum)) $data['startnum'] = 1;
    if (!isset($numitems)) $numitems = xarModGetVar('roles', 'rolesperpage');

    $numitems = xarModGetVar('roles', 'rolesperpage');
    $pagerfilter['order'] = $data['order'];
    $pagerfilter['search'] = $data['search'];
    $pagerfilter['startnum'] = '%%';

    $data['pager'] = xarTplGetPager(
        $data['startnum'],
        $data['total'],
        xarModURL('roles', 'user', 'viewl', $pagerfilter),
        $numitems
    );
    return $data;
}

?>
