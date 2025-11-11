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
use xarRoles;

/**
 * roles userapi countall function
 * @extends MethodClass<UserApi>
 */
class CountallMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * count all users
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return int|void the number of users matching the selection criteria (cfr. getall)
     * @see UserApi::countall()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Security check
        if (!$this->sec()->checkAccess('ReadRoles')) {
            return;
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $rolestable = $xartable['roles'];

        $bindvars = [];
        if (!empty($state) && is_numeric($state) && $state != xarRoles::ROLES_STATE_CURRENT) {
            $query = "SELECT COUNT(id) FROM $rolestable WHERE state = ?";
            $bindvars[] = (int) $state;
        } else {
            $query = "SELECT COUNT(id) FROM $rolestable WHERE state != ?";
            $bindvars[] = xarRoles::ROLES_STATE_DELETED;
        }

        //suppress display of pending users to non-admins
        if (!$this->sec()->checkAccess("AdminRole", 0)) {
            $query .= " AND state != ?";
            $bindvars[] = xarRoles::ROLES_STATE_PENDING;
        }

        if (isset($selection)) {
            $query .= $selection;
        }

        // if we aren't including anonymous in the query,
        // then find the anonymous user's id and add
        // a where clause to the query
        if (isset($include_anonymous) && !$include_anonymous) {
            $thisrole = $userapi->get(['uname' => 'anonymous']);
            $query .= " AND id != ?";
            $bindvars[] =  (int) $thisrole['id'];
        }

        $query .= " AND itemtype = ?";
        $bindvars[] = xarRoles::ROLES_USERTYPE;

        // cfr. cachemanager - this approach might change later
        $expire = $this->mod()->getVar('cache.userapi.countall');
        if (!empty($expire)) {
            $result = $dbconn->CacheExecute($expire, $query, $bindvars);
        } else {
            $result = $dbconn->Execute($query, $bindvars);
        }
        // Obtain the number of users
        $result->first();
        [$numroles] = $result->fields;

        $result->Close();

        // Return the number of users
        return $numroles;
    }
}
