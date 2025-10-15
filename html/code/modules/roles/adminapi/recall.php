<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminApi;
use EmptyParameterException;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles adminapi recall function
 * @extends MethodClass<AdminApi>
 */
class RecallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['id'] id of the role that is being called
     * @return bool true on success, false on failure
     * @see AdminApi::recall()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments
        extract($args);

        if (!isset($id) || $id == 0) {
            throw new EmptyParameterException('id');
        }
        if (!isset($state) || $state == 0) {
            throw new EmptyParameterException('state');
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $rolestable = $xartable['roles'];

        $deleted = '[' . $this->ml('deleted') . ']';

        $role = xarRoles::get($id);
        $uname = explode($deleted, $role->getUser());
        $email = explode($deleted, $role->getEmail());

        $query = "UPDATE $rolestable
                  SET uname = ?, email = ?, state = ?
                  WHERE id = ?";
        $bindvars = [$uname[0],$email[0],$state,$id];
        $dbconn->Execute($query, $bindvars);

        // Let any hooks know that we have recalled this user.
        $item['module'] = 'roles';
        $item['itemid'] = $id;
        $item['method'] = 'recall';
        $this->mod()->callHooks('item', 'create', $id, $item);

        //finished successfully
        return true;
    }
}
