<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

/** 
 * Return table name definitions to Xaraya.
 * This function is called internally by the core whenever the module is
 * loaded. It adds the table names to a globally accessible array
 * 
 * @author Marty Vance
 * @return array the registered tables of this module
 */
function themes_xartables()
{

    $prefix = xarDB::getPrefix();
    $tables['themes'] = $prefix . '_themes';
    $tables['themes_configurations'] = $prefix . '_themes_configurations';
    return $tables;
}
?>
