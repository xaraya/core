<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_11()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Create a configvar to hold the SSL port
    ");
    $data['reply'] = xarML("
        Success!
    ");

    try {
        xarConfigVars::set(null, 'Site.Core.SecureServerPort', 443);
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
?>