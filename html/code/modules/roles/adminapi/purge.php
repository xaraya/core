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
use xarRoles;

/**
 * roles adminapi purge function
 * @extends MethodClass<AdminApi>
 */
class PurgeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete users based on status
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['state'] state that we are deleting.
     * @return bool|string|void true on success, false on failure
     * @see AdminApi::purge()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments
        extract($args);


        if ($state == xarRoles::ROLES_STATE_ACTIVE) {
            return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'purge_active_user']);
        }

        $items = $userapi->getall(['state' => $state]);

        foreach ($items as $item) {

            // The user API function is called.
            $user = $userapi->get(['id' => $item['id']]);

            // Security check
            if (!$this->sec()->check('ManageRoles', 0, 'Item', "$item[name]::$item[id]")) {
                return;
            }

            // Call the Roles class
            $role = xarRoles::get($item['id']);
            if (!$role->purge()) {
                return;
            }

            // Let any hooks know that we have purged this user.
            $item['module'] = 'roles';
            $item['itemid'] = $item['id'];
            $item['method'] = 'purge';
            $this->mod()->callHooks('item', 'delete', $id, $item);
        }

        //finished successfully
        return true;
    }
}
