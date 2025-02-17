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
use DuplicateException;
use xarController;
use xarDB;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin newrealm function
 * @extends MethodClass<AdminGui>
 */
class NewrealmMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addRealm - create a new realm
     * @return array|string|void data for the template display
     * @see AdminGui::newrealm()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AddPrivileges', 0, 'Realm')) {
            return;
        }

        $data = [];

        if (!xarVar::fetch('name', 'str:1:20', $name, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirmed', 'bool', $confirmed, false, xarVar::NOT_REQUIRED)) {
            return;
        }

        if ($confirmed) {
            if (!xarSec::confirmAuthKey()) {
                return xarController::badRequest('bad_author', $this->getContext());
            }

            $dbconn = xarDB::getConn();
            $xartable = xarDB::getTables();
            $bindvars = [];
            $tbl = $xartable['security_realms'];
            $query = "SELECT name FROM $tbl WHERE name = ?";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars[] = $name;
            $result = $stmt->executeQuery($bindvars);
            while ($result->next()) {
                [$name] = $result->fields;
            }

            if ($name != '') {
                throw new DuplicateException(['realm',$name]);
            }

            $bindvars = [];
            $tbl = $xartable['security_realms'];
            $query = "INSERT into $tbl (name) values(?)";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars[] = $name;
            $result = $stmt->executeQuery($bindvars);

            //Redirect to view page
            xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'), null, $this->getContext());
        }

        $data['authid'] = xarSec::genAuthKey();
        return $data;
    }
}
