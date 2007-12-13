<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * update a property field
 *
 * @author the DynamicData module development team
 * @param $args['id'] property id of the item field to update
 * @param $args['name'] name of the field to update (optional)
 * @param $args['label'] label of the field to update
 * @param $args['type'] type of the field to update
 * @param $args['defaultvalue'] default of the field to update (optional)
 * @param $args['source'] data source of the field to update (optional)
 * @param $args['status'] status of the field to update (optional)
 * @param $args['validation'] validation of the field to update (optional)
 * @return bool
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_updateprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($id) || !is_numeric($id)) {
        $invalid[] = 'property id';
    }
    if (!isset($label) || !is_string($label)) {
        $invalid[] = 'label';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'updateprop', 'DynamicData');
        throw new BadParameterException($vars, $msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (isset($name) && is_string($name)) {
    if(!xarSecurityCheck('EditDynamicDataField',1,'Field',"$name:$type:$id")) return;
    } else {
    if(!xarSecurityCheck('EditDynamicDataField',1,'Field',"All:$type:$id")) return;
    }

    // Get database setup - note that xarDB::getConn()
    // returns an array but we handle it differently.
    // For xarDB::getConn() we want to keep the entire
    // tables array together for easy reference later on
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $bindvars = array();
    $sql = "UPDATE $dynamicprop SET label = ?, type = ?";
    $bindvars[] = $label; $bindvars[] = $type;
    if (isset($default) && is_string($default)) {
        $sql .= ", defaultvalue = ?";
        $bindvars[] = $default;
    }
    // TODO: verify that the data source exists
    if (isset($source) && is_string($source)) {
        $sql .= ", source = ?";
        $bindvars[] = $source;
    }
    if (isset($validation) && is_string($validation)) {
        $sql .= ", validation = ?";
        $bindvars[] = $validation;
    }
    if (isset($name) && is_string($name)) {
        $sql .= ", name = ?";
        $bindvars[] = $name;
    }
    if (isset($status) && is_numeric($status)) {
        $sql .= ", status = ?";
        $bindvars[] = $status;
    }

    $sql .= " WHERE id = ?";
    $bindvars[] = $id;
    $dbconn->Execute($sql,$bindvars);

    return true;
}
?>
