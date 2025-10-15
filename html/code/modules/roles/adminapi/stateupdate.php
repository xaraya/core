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
use Xaraya\Modules\Roles\UserApi;
use EmptyParameterException;
use IDNotFoundException;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles adminapi stateupdate function
 * @extends MethodClass<AdminApi>
 */
class StateupdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a user's state
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     *        integer  $args['id'] user ID<br/>
     *        string   $args['name'] user real name<br/>
     *        string   $args['uname'] user nick name<br/>
     *        string   $args['email'] user email address<br/>
     *        string   $args['pass'] user password
     * TODO: move url to dynamic user data
     *       replace with status
     * @param mixed $args ['url'] user url
     * @see AdminApi::stateupdate()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Argument check - make sure that all required arguments are present,
        // if not then set an appropriate error message and return
        if (!isset($id)) {
            throw new EmptyParameterException('id');
        }
        if (!isset($state)) {
            throw new EmptyParameterException('state');
        }

        $item = $userapi->get(['id' => $id]);

        if ($item == false) {
            throw new IDNotFoundException($id);
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $rolesTable = $xartable['roles'];

        $query = "UPDATE $rolesTable SET state = ?" ;
        $bindvars = [$state];
        if (isset($valcode)) {
            $query .= ", valcode = ?";
            $bindvars[] = $valcode;
        }
        $query .= " WHERE id = ?";
        $bindvars[] = $id;

        $dbconn->Execute($query, $bindvars);

        return true;
    }
}
