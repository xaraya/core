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
use sys;

sys::import('xaraya.modules.method');

/**
 * roles adminapi addmember function
 * @extends MethodClass<AdminApi>
 */
class AddmemberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * insertuser - add a user to a group
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['id'] user id<br/>
     * integer  $args['gid'] group id
     * @return bool true on succes, false on failure
     * @see AdminApi::addmember()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        return $userapi->addmember($args);
    }
}
