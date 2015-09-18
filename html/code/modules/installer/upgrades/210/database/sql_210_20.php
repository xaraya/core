<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_210_20()
{
    // Define parameters
    $module_vars = xarDB::getPrefix() . '_module_vars';
    $roles = xarDB::getPrefix() . '_roles';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Add the version build configuration variable
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    try {
        xarConfigVars::set(null, 'System.Core.VersionRev', xarCore::VERSION_REV);
    } catch (Exception $e) {
        // Damn
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
?>