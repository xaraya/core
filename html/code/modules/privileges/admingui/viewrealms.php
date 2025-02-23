<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use xarDB;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin viewrealms function
 * @extends MethodClass<AdminGui>
 */
class ViewrealmsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * viewRealms - view the defined realms
     * @return array|string|void data for the template display
     * @see AdminGui::viewrealms()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminPrivileges', 0, 'Realm')) {
            return;
        }

        $data = [];

        xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED);

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $rolesobjects = $xartable['security_realms'];
        $bindvars = [];
        $query = "SELECT id AS id, name AS name FROM $rolesobjects ";

        $query .= " ORDER BY name ";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
        if (!$result) {
            return;
        }
        while ($result->next()) {
            $data['realms'] = $result->fields;
        }
        return $data;
    }
}
