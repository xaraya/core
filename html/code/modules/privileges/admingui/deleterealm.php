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

        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $bindvars = [];
        $tbl = $xartable['security_realms'];
        $query = "SELECT id, name FROM $tbl WHERE id = ?";
        $bindvars[] = $id;
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);
        if (!$result) {
            return;
        }
        while ($result->next()) {
            [$result_id, $name] = $result->fields;
        }

        // Security
        if (empty($name)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (!xarSecurity::check('ManagePrivileges', 0, 'Realm', $name)) {
            return;
        }

        if (empty($confirmed)) {
            $data['authid'] = xarSec::genAuthKey();
            $data['id'] = $id;
            $data['name'] = $name;
            return $data;
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $bindvars = [];
        $query = "DELETE FROM $tbl WHERE id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars[] = $result_id;
        $result = $stmt->executeQuery($bindvars, xarDB::FETCHMODE_ASSOC);

        // Hmm... what do we do about hooks?
        //xarModHooks::call('item', 'delete', $id, '');

        // redirect to the next page
        xarController::redirect(xarController::URL('privileges', 'admin', 'viewrealms'), null, $this->getContext());
        return true;
    }
}
