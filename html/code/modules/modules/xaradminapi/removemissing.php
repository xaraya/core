<?php
/**
 * Remove a module when the files are missing
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
/**
 * Remove a module when the files are missing
 *
 * @author Xaraya Development Team
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['regid'] the id of the module
 * @return boolean|void true on success, false on failure
 */
function modules_adminapi_removemissing(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // TODO (random) This whole exercise is on hold because w have no way of knowing which
    // tables actually belong to the module being removed, and so the cleanup is incomplete
    // For now just remove the entry in the modules table

    //    if (!xarVar::fetch('remove', 'str', $remove, NULL, xarVar::NOT_REQUIRED)) return;
    // Get module information
    //    $modinfo = xarMod::getInfo($regid);
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $modInfo = xarMod::getInfo($regid);
    $modId = $modInfo['systemid'];
    // Make what we do at least atomic
    try {
        $dbconn->begin();
        $query = "DELETE FROM $tables[modules] WHERE id = ?";
        $dbconn->Execute($query,array($modId));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}
