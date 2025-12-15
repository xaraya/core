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
use IDNotFoundException;

/**
 * roles userapi addmember function
 * @extends MethodClass<UserApi>
 */
class AddmemberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addmember - add a role to a group
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['gid'] group id<br/>
     * integer  $args['id'] role id
     * @return bool|void true on succes, false on failure
     * @see UserApi::addmember()
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

        $group = $this->user()->getRole('id', (int) $gid);
        if ($group->isUser()) {
            throw new IDNotFoundException($gid);
        }

        $user = $this->user()->getRole('id', (int) $id);

        // Security Check
        if (!$this->sec()->check('AttachRole', 1, 'Relation', $group->getName() . ":" . $user->getName())) {
            return;
        }

        if (!$group->addMember($user)) {
            return;
        }

        return true;
    }
}
