<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Return table name definitions to Xaraya
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * This function is called internally by the core whenever the module is
 * loaded. It is called by xarMod__dbInfoLoad()
 *
 * @return array
 */
function roles_xartables()
{
    $prefix = xarDB::getPrefix();
    $tables['roles']          = $prefix . '_roles';
    $tables['rolemembers']    = $prefix . '_rolemembers';
    return $tables;
}
?>