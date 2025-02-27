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
        if (!$this->sec()->check('AddPrivileges', 0, 'Realm')) {
            return;
        }

        $data = [];

        $this->var()->find('name', $name, 'str:1:20', '');
        $this->var()->find('confirmed', $confirmed, 'bool', false);

        if ($confirmed) {
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            $dbconn = $this->db()->getConn();
            $xartable = $this->db()->getTables();
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
            $this->ctl()->redirect($this->ctl()->getModuleURL('privileges', 'admin', 'viewrealms'));
        }

        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
