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

/**
 * roles userapi countgroups function
 * @extends MethodClass<UserApi>
 */
class CountgroupsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to count the number of items held by this module
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return int the number of items held by this module
     * @see UserApi::countgroups()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        return count($userapi->getallgroups());
    }
}
