<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use SQLException;
use xarDB;
use xarMod;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi removemissing function
 * @extends MethodClass<AdminApi>
 */
class RemovemissingMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a module when the files are missing
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id of the module
     * @return bool|void true on success, false on failure
     * @see AdminApi::removemissing()
     */
    public function __invoke(array $args = [])
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
        $tables = xarDB::getTables();

        $modInfo = xarMod::getInfo($regid);
        $modId = $modInfo['systemid'];
        // Make what we do at least atomic
        try {
            $dbconn->begin();
            $query = "DELETE FROM $tables[modules] WHERE id = ?";
            $dbconn->Execute($query, [$modId]);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        return true;
    }
}
