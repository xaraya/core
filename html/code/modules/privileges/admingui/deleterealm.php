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
use xarController;
use xarDB;
use xarModHooks;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin deleterealm function
 * @extends MethodClass<AdminGui>
 */
class DeleterealmMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * deleteRealm - delete a realm
     * prompts for confirmation
     * @see AdminGui::deleterealm()
     */
    public function __invoke(array $args = [])
    {
        $this->var()->check('id', $id);
        $this->var()->check('confirmed', $confirmed);

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $bindvars = [];
        $tbl = $xartable['security_realms'];
        $query = "SELECT id, name FROM $tbl WHERE id = ?";
        $bindvars[] = $id;
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, $this->db()->getFetchAssoc());
        if (!$result) {
            return;
        }
        while ($result->next()) {
            [$result_id, $name] = $result->fields;
        }

        // Security
        if (empty($name)) {
            return $this->ctl()->notFound();
        }
        if (!$this->sec()->check('ManagePrivileges', 0, 'Realm', $name)) {
            return;
        }

        if (empty($confirmed)) {
            $data['authid'] = $this->sec()->genAuthKey();
            $data['id'] = $id;
            $data['name'] = $name;
            return $data;
        }

        // Check for authorization code
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        $bindvars = [];
        $query = "DELETE FROM $tbl WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $result_id;
        $result = $stmt->executeQuery($bindvars, $this->db()->getFetchAssoc());

        // Hmm... what do we do about hooks?
        //$this->mod()->callHooks('item', 'delete', $id, '');

        // redirect to the next page
        $this->ctl()->redirect($this->ctl()->getModuleURL('privileges', 'admin', 'viewrealms'));
        return true;
    }
}
