<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete a property field
 *
 * @author the DynamicData module development team
 * @param $args['id'] property id of the item field to delete
// TODO: do we want those for security check ? Yes, but the original values...
 * @param $args['modid'] module id of the item field to delete
 * @param $args['itemtype'] item type of the item field to delete
 * @param $args['name'] name of the field to delete
 * @param $args['label'] label of the field to delete
 * @param $args['type'] type of the field to delete
 * @param $args['defaultvalue'] default of the field to delete
 * @param $args['source'] data source of the field to delete
 * @param $args['configuration'] configuration of the field to delete
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($id) || !is_numeric($id)) {
        $invalid[] = 'property id';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'deleteprop', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    // TODO: check based on other arguments too
    if(!xarSecurityCheck('DeleteDynamicDataField',1,'Field',"All:All:$id")) return;

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    try {
        $dbconn->begin();
        $sql = "DELETE FROM $dynamicprop WHERE id = ?";
        $dbconn->Execute($sql,array($id));

        // TODO: don't delete if the data source is not in dynamic_data
        // delete all data too !
        $dynamicdata = $xartable['dynamic_data'];
        $sql = "DELETE FROM $dynamicdata WHERE propid = ?";
        $dbconn->Execute($sql,array($id));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

?>
