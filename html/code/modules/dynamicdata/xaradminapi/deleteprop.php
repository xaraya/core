<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete a property field
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] property id of the item field to delete<br/>
// TODO: do we want those for security check ? Yes, but the original values...<br/>
 *        integer  $args['module_id'] module id of the item field to delete<br/>
 *        string   $args['itemtype'] item type of the item field to delete<br/>
 *        string   $args['name'] name of the field to delete<br/>
 *        string   $args['label'] label of the field to delete<br/>
 *        string   $args['type'] type of the field to delete<br/>
 *        string   $args['defaultvalue'] default of the field to delete<br/>
 *        string   $args['source'] data source of the field to delete<br/>
 *        string   $args['configuration'] configuration of the field to delete
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteprop(Array $args=array())
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

    // TODO: security check on object level

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
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
        $sql = "DELETE FROM $dynamicdata WHERE property_id = ?";
        $dbconn->Execute($sql,array($id));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

?>
