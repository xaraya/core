<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 */

/**
 * Return table name definitions to Xaraya.
 * This function is called internally by the core whenever the module is
 * loaded. It adds the table names to a globally accessible array
 *
 * @author mikespub <mikespub@xaraya.com>
 * @return array the registered tables of this module
 */
function dynamicdata_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['dynamic_objects']        = $prefix . '_dynamic_objects';
    $tables['dynamic_properties']     = $prefix . '_dynamic_properties';
    $tables['dynamic_data']           = $prefix . '_dynamic_data';
    $tables['dynamic_relations']      = $prefix . '_dynamic_relations';
    $tables['dynamic_properties_def'] = $prefix . '_dynamic_properties_def';
    $tables['dynamic_configurations'] = $prefix . '_dynamic_configurations';
    return $tables;
}
?>
