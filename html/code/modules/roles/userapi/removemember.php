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
use IDNotFoundException;
use xarRoles;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi removemember function
 * @extends MethodClass<UserApi>
 */
class RemovememberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removemember - remove a role from a group
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['gid'] group id<br/>
     * integer  $args['id'] role id
     * @return bool|void true on succes, false on failure
     * @see UserApi::removemember()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($gid)) {
            throw new EmptyParameterException('gid');
        }
        if (!isset($id)) {
            throw new EmptyParameterException('id');
        }

        $group = xarRoles::get($gid);
        if ($group->isUser()) {
            throw new IDNotFoundException($gid);
        }
        $user = xarRoles::get($id);

        // Security Check
        if (!$this->sec()->check('RemoveRole', 1, 'Relation', $group->getName() . ":" . $user->getName())) {
            return;
        }

        if (!$group->removeMember($user)) {
            return;
        }

        return true;
    }
}
