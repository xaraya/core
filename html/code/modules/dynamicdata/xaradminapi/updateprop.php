<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * update a property field
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['id'] property id of the item field to update<br/>
 *        string   $args['name'] name of the field to update (optional)<br/>
 *        string   $args['label'] label of the field to update<br/>
 *        string   $args['type'] type of the field to update<br/>
 *        string   $args['defaultvalue'] default of the field to update (optional)<br/>
 *        string   $args['source'] data source of the field to update (optional)<br/>
 *        integer  $args['status'] status of the field to update (optional)<br/>
 *        string   $args['configuration'] configuration of the field to update (optional)
 * @return boolean
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_updateprop(Array $args=array())
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

    // TODO: security check on object level

    // Get database setup - note that xarDB::getConn()
    // returns an array but we handle it differently.
    // For xarDB::getConn() we want to keep the entire
    // tables array together for easy reference later on
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $bindvars = array();
    $sql = "UPDATE $dynamicprop SET label = ?, type = ?";
    $bindvars[] = $label; $bindvars[] = $type;
    if (isset($defaultvalue) && is_string($defaultvalue)) {
        $sql .= ", defaultvalue = ?";
        $bindvars[] = $defaultvalue;
    }
    if (isset($seq) && is_numeric($seq)) {
        $sql .= ", seq = ?";
        $bindvars[] = $seq;
    }
    if (isset($translatable) && is_string($translatable)) {
        $sql .= ", translatable = ?";
        $bindvars[] = $translatable;
    }
    // TODO: verify that the data source exists
    if (isset($source) && is_string($source)) {
        $sql .= ", source = ?";
        $bindvars[] = $source;
    }
    if (isset($configuration) && is_string($configuration)) {
        $sql .= ", configuration = ?";
        $bindvars[] = $configuration;
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
