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
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getstates function
 * @extends MethodClass<UserApi>
 */
class GetstatesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get States
     * @author Marc Lutolf
     * @todo this either needs to move somewhere else, or become smarter (ie: use workflow)
     * there's no current way this can be included in the property configuration
     * since property configuration can't be MLed
     * @see UserApi::getstates()
     */
    public function __invoke(array $args = [])
    {
        sys::import('modules.roles.class.roles');
        return [
            ['id' => xarRoles::ROLES_STATE_INACTIVE, 'name' => $this->ml('Inactive')],
            ['id' => xarRoles::ROLES_STATE_NOTVALIDATED, 'name'  => $this->ml('Not Validated')],
            ['id' => xarRoles::ROLES_STATE_ACTIVE, 'name'  => $this->ml('Active')],
            ['id' => xarRoles::ROLES_STATE_PENDING, 'name'  => $this->ml('Pending')],
        ];
    }
}
