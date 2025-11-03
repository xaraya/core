<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\UserApi;
use xarUser;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem userapi authenticate_user function
 * @extends MethodClass<UserApi>
 */
class AuthenticateUserMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Authenticate a user
     * @author Marco Canini
     * @param string[] $args Array of optional parameters<br/>
     * string   $args['uname'] user name of user<br/>
     * string   $args['pass'] password of user
     * @return int Returns user id on successful authentication, xarUser::AUTH_FAILED otherwise
     * @see UserApi::authenticateUser()
     */
    public function __invoke(array $args = [])
    {
        /**
         * Pending
         * @todo use roles api, not direct db
         */

        extract($args);

        assert(!empty($uname) && isset($pass));

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // Get user information
        $rolestable = $xartable['roles'];
        $query = "SELECT id, pass FROM $rolestable WHERE uname = ?";
        $stmt = $dbconn->prepareStatement($query);

        $result = $stmt->executeQuery([$uname]);

        if (!$result->first()) {
            $result->close();
            return xarUser::AUTH_FAILED;
        }

        [$id, $realpass] = $result->fields;
        $result->close();

        // Confirm that passwords match
        if (!$this->user()->comparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2))) {
            return xarUser::AUTH_FAILED;
        }

        return $id;
    }
}
