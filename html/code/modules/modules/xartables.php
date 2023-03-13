<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * Return table name definitions to Xaraya.
 * This function is called internally by the core whenever the module is
 * loaded. It adds the table names to a globally accessible array
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array the registered tables of this module
 */
function modules_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['modules']         = $prefix . '_modules';
    $tables['module_vars']     = $prefix . '_module_vars';
    $tables['module_itemvars'] = $prefix . '_module_itemvars';
    $tables['hooks']           = $prefix . '_hooks';
    $tables['eventsystem']     = $prefix . '_eventsystem';
    return $tables;
}
