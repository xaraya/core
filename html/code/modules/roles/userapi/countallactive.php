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
use xarConfigVars;
use xarDB;
use xarMod;
use xarModVars;
use xarRoles;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi countallactive function
 * @extends MethodClass<UserApi>
 */
class CountallactiveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Count all active users
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * boolean  $args['include_anonymous'] whether or not to include anonymous user<br/>
     * string   $args['filter']
     * @return int|void the number of users
     * @see UserApi::countallactive()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!isset($include_anonymous)) {
            $include_anonymous = true;
        } else {
            $include_anonymous = (bool) $include_anonymous;
        }

        // Optional arguments.
        if (empty($filter)) {
            $filter = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);
        }

        // Security Check
        if (!xarSecurity::check('ReadRoles')) {
            return;
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        $sessioninfoTable = $xartable['session_info'];
        $rolestable = $xartable['roles'];

        $bindvars = [];
        $query = "SELECT COUNT(*)
                  FROM $rolestable a, $sessioninfoTable b
                  WHERE a.id = b.role_id AND b.last_use > ? AND a.id > ?";
        $bindvars[] = $filter;
        $bindvars[] = 1;

        // FIXME: this adds a part to the query which does NOT have bindvars but direct values
        if (isset($selection)) {
            $query .= $selection;
        }
        // TODO: this would be the place to add the bindvars applicable for $selection

        // if we aren't including anonymous in the query,
        // then find the anonymous user's id and add
        // a where clause to the query
        if (!$include_anonymous) {
            $anon = $userapi->get(['uname' => 'anonymous']);
            $query .= " AND a.id != ?";
            $bindvars[] = (int) $anon['id'];
        }
        $query .= " AND itemtype = ?";
        $bindvars[] = xarRoles::ROLES_USERTYPE;

        // cfr. cachemanager - this approach might change later
        $expire = xarModVars::get('roles', 'cache.userapi.countallactive');
        if (!empty($expire)) {
            $result = $dbconn->CacheExecute($expire, $query, $bindvars);
        } else {
            $result = $dbconn->Execute($query, $bindvars);
        }

        // Obtain the number of users
        [$numroles] = $result->fields;

        $result->Close();

        // Return the number of users
        return $numroles;
    }
}
