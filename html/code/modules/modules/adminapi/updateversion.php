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
use EmptyParameterException;
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi updateversion function
 * @extends MethodClass<AdminApi>
 */
class UpdateversionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update the module version in the database
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regId'] the id number of the module to update
     * @return bool|void true on success, false on failure
     * @see AdminApi::updateversion()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regId)) {
            throw new EmptyParameterException('redId');
        }

        // Security Check
        if (!xarSecurity::check('AdminModules', 0, 'All', "All:All:$regId")) {
            return;
        }

        //  Get database connection and tables
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $modules_table = $xartable['modules'];

        // Get module information from the filesystem
        $fileModule = $adminapi->getfilemodules(['regId' => $regId]);
        if (!isset($fileModule)) {
            return;
        }

        // Update database version
        $sql = "UPDATE $modules_table SET version = ? WHERE regid = ?";
        $bindvars = [$fileModule['version'],$fileModule['regid']];

        $result = $dbconn->Execute($sql, $bindvars);

        return true;
    }
}
