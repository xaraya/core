<?php
/**
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 */
/**
 * utility function to create the native objects of this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return bool
 */
function privileges_adminapi_createobjects($args)
{
    $moduleid = 1098;

# --------------------------------------------------------
#
# Create the privilege object
#
    $prefix = xarDBGetSiteTablePrefix();
    $itemtype = 1;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'baseprivilege',
                                    'label'    => 'Base Privilege',
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
                                    'source'   =>  $prefix . '_privileges.xar_pid',
                                    'status'   => $status2,
                                    'order'    => 1,
                                    ))) return;

# --------------------------------------------------------
#
# Create the privilege object
#
    $itemtype = 2;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'privilege',
                                    'label'    => 'Privilege',
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
                                    'source'   =>  $prefix . '_privileges.xar_pid',
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
                                    'source'   =>  $prefix . '_privileges.xar_name',
                                    'status'   => $status1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'realm',
                                    'label'    => 'Realm',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    //@todo use objectref or similiar here?
                                    'source'   =>  $prefix . '_privileges.xar_realmid',
                                    'status'   => $status1,
                                    'order'    => 3,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'module',
                                    'label'    => 'Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_module',
                                    'status'   => $status1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'component',
                                    'label'    => 'Component',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_component',
                                    'status'   => $status1,
                                    'order'    => 5,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'instance',
                                    'label'    => 'Instance',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_instance',
                                    'status'   => $status1,
                                    'order'    => 6,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'level',
                                    'label'    => 'Level',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_level',
                                    'status'   => $status1,
                                    'order'    => 7,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'description',
                                    'label'    => 'Description',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_description',
                                    'status'   => $status2,
                                    'order'    => 8,
                                    ))) return;

# --------------------------------------------------------
#
# Create the mask object
#
    $itemtype = 3;
    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'mask',
                                    'label'    => 'Mask',
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
                                    'source'   =>  $prefix . '_security_masks.xar_sid',
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
                                    'source'   =>  $prefix . '_security_masks.xar_name',
                                    'status'   => $status1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'realm',
                                    'label'    => 'Realm',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    //@todo use objectref or similiar here?
                                    'source'   =>  $prefix . '_security_masks.xar_realmid',
                                    'status'   => $status1,
                                    'order'    => 3,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'module',
                                    'label'    => 'Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_modid',
                                    'status'   => $status1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'component',
                                    'label'    => 'Component',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_component',
                                    'status'   => $status1,
                                    'order'    => 5,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'instance',
                                    'label'    => 'Instance',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_instance',
                                    'status'   => $status1,
                                    'order'    => 6,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'level',
                                    'label'    => 'Level',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_level',
                                    'status'   => $status1,
                                    'order'    => 7,
                                    ))) return;
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'description',
                                    'label'    => 'Description',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_description',
                                    'status'   => $status2,
                                    'order'    => 8,
                                    ))) return;

    return true;
}

?>
