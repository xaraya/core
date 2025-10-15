<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use VariableValidationException;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getactive function
 * @extends MethodClass<UserApi>
 */
class GetactiveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * check if a user is active or not on the site
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * boolean   $args['include_anonymous'] whether or not to include anonymous user
     * @return mixed array of users, or false on failure
     * @see UserApi::getactive()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($id) && !is_numeric($id)) {
            throw new VariableValidationException(['id',$id,'numeric']);
        }

        if (empty($filter)) {
            $filter = time() - ($this->config()->getVar('Site.Session.Duration') * 60);
        }

        $roles = [];

        // Security Check
        if (!$this->sec()->checkAccess('ReadRoles')) {
            return;
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $sessioninfoTable = $xartable['session_info'];

        $query = "SELECT role_id
                  FROM $sessioninfoTable
                  WHERE last_use > ? AND role_id = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars = [(int) $filter,(int) $id];
        $result = $stmt->executeQuery($bindvars);

        // Put users into result array
        while ($result->next()) {
            $id = $result->fields;
            // FIXME: add some instances here
            if ($this->sec()->checkAccess('ReadRoles', 0)) {
                $sessions[] = ['id'       => $id];
            }
        }
        $result->close();

        // Return the users
        if (empty($sessions)) {
            $sessions = '';
        }

        return $sessions;
    }
}
