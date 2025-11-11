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
 * roles userapi checkprivilege function
 * @extends MethodClass<UserApi>
 */
class CheckprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['privilege'] name of a privilege<br/>
     * string   $args['role_id'] id of a role
     * @return bool
     * @see UserApi::checkprivilege()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($privilege)) {
            throw new EmptyParameterException('privilege');
        }

        if (empty($id)) {
            $id = $this->user()->getId();
        }
        $role = xarRoles::get($id);
        return $role->hasPrivilege($privilege);
    }
}
