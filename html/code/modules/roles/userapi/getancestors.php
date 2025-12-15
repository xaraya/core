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
 * roles userapi getancestors function
 * @extends MethodClass<UserApi>
 */
class GetancestorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getancestors - get ancestors of a role
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['id'] role id
     * @return array|void array containing name, id of the ancstors
     * @see UserApi::getancestors()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($id)) {
            throw new EmptyParameterException('id');
        }

        if (!$this->sec()->checkAccess('ReadRoles')) {
            return;
        }

        $role = $this->user()->getRole('id', (int) $id);

        if (empty($args['parents'])) {
            $ancestors = $role->getRoleAncestors();
        } else {
            $ancestors = $role->getParents();
        }

        $flatancestors = [];
        foreach ($ancestors as $ancestor) {
            $flatancestors[] = ['id' => $ancestor->getID(),
                'name' => $ancestor->getName(),
            ];
        }
        return $flatancestors;
    }
}
