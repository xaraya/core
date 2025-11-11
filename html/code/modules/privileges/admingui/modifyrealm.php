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
        if (!$this->sec()->check('EditPrivileges', 0, 'Realm')) {
            return;
        }

        $this->var()->find('id', $id, 'int', '');
        $this->var()->find('confirmed', $confirmed, 'bool', false);
        $this->var()->find('name', $name, 'str:1.20', '');

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

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
            $this->var()->find('newname', $newname, 'str:1.20', '');
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
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

            $this->ctl()->redirect($this->ctl()->getModuleURL('privileges', 'admin', 'viewrealms'));
            return true;
        }

        $data['id'] = $id;
        $data['name'] = $name;
        $data['newname'] = '';
        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
