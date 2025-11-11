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

/**
 * modules adminapi getdbmodules function
 * @extends MethodClass<AdminApi>
 */
class GetdbmodulesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get all modules in the database
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] - optional regid to retrieve
     * @return array modules in the database
     * @see AdminApi::getdbmodules()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments
        extract($args);

        // Check for $regId
        $modregid = 0;
        if (isset($regId)) {
            $modregid = $regId;
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $dbModules = [];

        // Get all modules in DB
        $sql = "SELECT regid, name, directory, class, version, state
                FROM $xartable[modules] ";

        if ($modregid) {
            $sql .= " WHERE $xartable[modules].regid = ?";
            $bindvars = [$modregid];
        } else {
            $bindvars = [];
        }
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery($bindvars);

        while ($result->next()) {
            [$regid, $name, $directory, $class, $version, $state] = $result->fields;

            // If returning one module, then push array without name index
            if ($modregid) {
                $dbModules = ['name'    => $name,
                    'regid'   => $regid,
                    'version' => $version,
                    'class'   => $class,
                    'state'   => $state];
            } else {
                //Push it into array (should we change to index by regid instead?)
                $dbModules[$name] = ['name'    => $name,
                    'regid'   => $regid,
                    'version' => $version,
                    'class'   => $class,
                    'state'   => $state];
            }
        }
        $result->close();

        return $dbModules;
    }
}
