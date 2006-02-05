<?php
/**
 * Utility function to create the native objects of this module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 */
/**
 * utility function to create the native objects of this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns boolean
 */
function privileges_adminapi_createobjects($args)
{
    $moduleid = 1098; // A bit of elaboration on this value would be nice

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
    if (!xarModAPIFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_privileges.xar_pid',
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'source'   =>  $prefix . '_privileges.xar_realm',
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'source'   =>  $prefix . '_security_masks.xar_realm',
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
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
                                    'status'   => 1,
                                    'order'    => 8,
                                    ))) return;

    return true;
}

?>