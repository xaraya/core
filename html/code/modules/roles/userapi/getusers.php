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
use EmptyParameterException;

/**
 * roles userapi getusers function
 * @extends MethodClass<UserApi>
 */
class GetusersMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getUsers - view users in group
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['id'] group id
     * @return array|void array containing uname, id of the users
     * @see UserApi::getusers()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($id)) {
            throw new EmptyParameterException('id');
        }
        if (empty($id)) {
            return [];
        }

        // Security Check
        if (!$this->sec()->checkAccess('ReadRoles')) {
            return;
        }

        $role = xarRoles::get($id);

        $users = $role->getUsers();

        $flatusers = [];
        foreach ($users as $user) {
            $flatusers[] = ['id' => $user->getID(),
                'uname' => $user->getUser(),
            ];
        }

        return $flatusers;
    }
}
