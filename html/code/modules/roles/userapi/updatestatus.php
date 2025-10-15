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
use EmptyParameterException;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi updatestatus function
 * @extends MethodClass<UserApi>
 */
class UpdatestatusMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a users status
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     *        string   $args['uname'] is the users system name<br/>
     *        string   $args['state'] is the new state for the user
     * returns bool
     * @see UserApi::updatestatus()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($uname)) {
            throw new EmptyParameterException('uname');
        }
        if (!isset($state)) {
            throw new EmptyParameterException('state');
        }

        if (!$this->sec()->checkAccess('ViewRoles')) {
            return;
        }

        // Get DB Set-up
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $rolesTable = $xartable['roles'];

        // Update the status
        $query = "UPDATE $rolesTable
                  SET valcode = ?, state = ?
                  WHERE uname = ?";
        $bindvars = ['',$state,$uname];

        $dbconn->Execute($query, $bindvars);

        return true;
    }
}
