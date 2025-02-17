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
 * privileges admin modifyrealm function
 * @extends MethodClass<AdminGui>
 */
class ModifyrealmMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * modifyRealm - modify an existing realm
     * @param int id of the realm to be modified
     * @return array|string|void data for the template display
     * @see AdminGui::modifyrealm()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditPrivileges', 0, 'Realm')) {
            return;
        }

        if (!xarVar::fetch('id', 'int', $id, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('confirmed', 'bool', $confirmed, false, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('name', 'str:1.20', $name, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        if (empty($confirmed)) {
            $bindvars = [];
            $tbl = $xartable['security_realms'];
            $query = "SELECT id, name FROM $tbl WHERE id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars[] = $id;
            $result = $stmt->executeQuery($bindvars);
            while ($result->next()) {
                [$result_id, $name] = $result->fields;
            }
        } else {
            if (!xarVar::fetch('newname', 'str:1.20', $newname, '', xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!xarSec::confirmAuthKey()) {
                return xarController::badRequest('bad_author', $this->getContext());
            }

            $bindvars = [];
            $name = '';
            $tbl = $xartable['security_realms'];
            $query = "SELECT name FROM $tbl WHERE name = ?";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars[] = $newname;
            $result = $stmt->executeQuery($bindvars);
            while ($result->next()) {
                [$name] = $result->fields;
            }

            if ($name != '') {
                throw new DuplicateException(['realm',$newname]);
            }

            $bindvars = [];
            $tbl = $xartable['security_realms'];
            $query = "UPDATE $tbl SET name =? WHERE id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $bindvars[] = $newname;
            $bindvars[] = $id;
            $result = $stmt->executeQuery($bindvars);

            xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'), null, $this->getContext());
        }

        $data['id'] = $id;
        $data['name'] = $name;
        $data['newname'] = '';
        $data['authid'] = xarSec::genAuthKey();
        return $data;
    }
}
