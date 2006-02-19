<?php
/**
 * Update a property field
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * update a property field
 *
 * @author the DynamicData module development team
 * @param $args['prop_id'] property id of the item field to update
 * @param $args['modid'] module id of the item field to update (optional)
 * @param $args['itemtype'] item type of the item field to update (optional)
 * @param $args['name'] name of the field to update (optional)
 * @param $args['label'] label of the field to update
 * @param $args['type'] type of the field to update
 * @param $args['default'] default of the field to update (optional)
 * @param $args['source'] data source of the field to update (optional)
 * @param $args['status'] status of the field to update (optional)
 * @param $args['validation'] validation of the field to update (optional)
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_updateprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($prop_id) || !is_numeric($prop_id)) {
        $invalid[] = 'property id';
    }
    if (!isset($label) || !is_string($label)) {
        $invalid[] = 'label';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'updateprop', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (isset($name) && is_string($name)) {
    if(!xarSecurityCheck('EditDynamicDataField',1,'Field',"$name:$type:$prop_id")) return;
    } else {
    if(!xarSecurityCheck('EditDynamicDataField',1,'Field',"All:$type:$prop_id")) return;
    }

    // Get database setup - note that xarDBGetTables()
    // returns an array but we handle it differently.
    // For xarDBGetTables() we want to keep the entire
    // tables array together for easy reference later on
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $bindvars = array();
    $sql = "UPDATE $dynamicprop SET xar_prop_label = ?, xar_prop_type = ?";
    $bindvars[] = $label; $bindvars[] = $type;
    if (isset($default) && is_string($default)) {
        $sql .= ", xar_prop_default = ?";
        $bindvars[] = $default;
    }
// TODO: verify that the data source exists
    if (isset($source) && is_string($source)) {
        $sql .= ", xar_prop_source = ?";
        $bindvars[] = $source;
    }
    if (isset($validation) && is_string($validation)) {
        $sql .= ", xar_prop_validation = ?";
        $bindvars[] = $validation;
    }
// TODO: evaluate if we allow update those too
    if (isset($modid) && is_numeric($modid)) {
        $sql .= ", xar_prop_moduleid = ?";
        $bindvars[] = $modid;
    }
    if (isset($itemtype) && is_numeric($itemtype)) {
        $sql .= ", xar_prop_itemtype = ?";
        $bindvars[] = $itemtype;
    }
    if (isset($name) && is_string($name)) {
        $sql .= ", xar_prop_name = ?";
        $bindvars[] = $name;
    }
    if (isset($status) && is_numeric($status)) {
        $sql .= ", xar_prop_status = ?";
        $bindvars[] = $status;
    }

    $sql .= " WHERE xar_prop_id = ?";
    $bindvars[] = $prop_id;
    $result =& $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    return true;
}

?>