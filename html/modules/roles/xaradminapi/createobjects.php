<?php
/**
 * Utility function to create the native objects of this module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * utility function to create the native objects of this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns boolean
 */
function roles_adminapi_createobjects($args)
{
    $moduleid = 27;

# --------------------------------------------------------
#
# Create the role object
#
    $prefix = xarDBGetSiteTablePrefix();
    $itemtype = 1;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'role',
                                    'label'    => 'Role',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    $status1 = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_ADDMODIFY;
    $status2 = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE | DataPropertyMaster::DD_INPUTSTATE_NOINPUT;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_roles.xar_uid',
                                    'status'   => $status2,
                                    'order'    => 1,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'type',
                                    'label'    => 'Type',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 20,
                                    'default'  => 1,
                                    'source'   =>  $prefix . '_roles.xar_type',
                                    'status'   => $status2,
                                    'order'    => 3,
                                    ))) {
                                    return;}
# --------------------------------------------------------
#
# Create the user object
#

    $itemtype = 2;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'user',
                                    'label'    => 'User',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_roles.xar_uid',
                                    'status'   => $status2,
                                    'order'    => 1,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'name',
                                    'label'    => 'Name',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_name',
                                    'status'   => $status1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'type',
                                    'label'    => 'Type',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 20,
                                    'default'  => 2,
                                    'source'   =>  $prefix . '_roles.xar_type',
                                    'status'   => $status2,
                                    'order'    => 3,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'uname',
                                    'label'    => 'User Name',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_uname',
                                    'status'   => $status1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'email',
                                    'label'    => 'Email',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 26,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_email',
                                    'status'   => $status1,
                                    'order'    => 5,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'password',
                                    'label'    => 'Password',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 26,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_pass',
                                    'status'   => $status2,
                                    'order'    => 6,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'regdate',
                                    'label'    => 'Reg. Date',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_date_reg',
                                    'status'   => $status1,
                                    'order'    => 7,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'valcode',
                                    'label'    => 'Val. Code',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_valcode',
                                    'status'   => $status2,
                                    'order'    => 8,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'state',
                                    'label'    => 'State',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_state',
                                    'status'   => $status2,
                                    'order'    => 9,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'authmodule',
                                    'label'    => 'Auth. Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_auth_modid',
                                    'status'   => $status2,
                                    'order'    => 10,
                                    ))) return;

# --------------------------------------------------------
#
# Create the group object
#
    $itemtype = 3;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'group',
                                    'label'    => 'Group',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_roles.xar_uid',
                                    'status'   => $status2,
                                    'order'    => 1,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'name',
                                    'label'    => 'Name',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_name',
                                    'status'   => $status1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'type',
                                    'label'    => 'Type',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 20,
                                    'default'  => 3,
                                    'source'   =>  $prefix . '_roles.xar_type',
                                    'status'   => $status2,
                                    'order'    => 3,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'users',
                                    'label'    => 'Users',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
                                    'default'  => 0,
                                    'source'   =>  $prefix . '_roles.xar_users',
                                    'status'   => $status1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'uname',
                                    'label'    => 'UserName',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_uname',
                                    'status'   => $status2,
                                    'order'    => 5,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'regdate',
                                    'label'    => 'Reg. Date',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_date_reg',
                                    'status'   => $status1,
                                    'order'    => 6,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'valcode',
                                    'label'    => 'Val. Code',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_valcode',
                                    'status'   => $status2,
                                    'order'    => 7,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'state',
                                    'label'    => 'State',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_state',
                                    'status'   => $status2,
                                    'order'    => 8,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'authmodule',
                                    'label'    => 'Auth. Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_roles.xar_auth_modid',
                                    'status'   => $status2,
                                    'order'    => 9,
                                    ))) return;

    return true;
}

?>
