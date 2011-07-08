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

function sql_220_10()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Move the debug users to Roles module
    ");
    $data['reply'] = xarML("
        Success!
    ");

    try {
        xarConfigVars::set(null, 'Site.User.DebugAdmins', array('admin'));
        xarModVars::delete('dynamicdata','debugusers');
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