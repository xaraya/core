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
use xarDB;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi countitems function
 * @extends MethodClass<UserApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to count the number of users
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return int the number of items held by this module
     * @see UserApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $rolestable = $xartable['roles'];

        // Get user
        $query = "SELECT COUNT(1)
                FROM $rolestable";
        $result = $dbconn->Execute($query);

        // Obtain the number of users
        [$numroles] = $result->fields;

        $result->Close();

        // Return the number of users
        return $numroles;
    }
}
