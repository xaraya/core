<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the roles scheduler API
 *
 * @method mixed expire(array $args = []) expire non-validated accounts or whatever (executed by the scheduler module)
 * @extends UserApiClass<Module>
 */
class SchedulerApi extends UserApiClass
{
    public function configure()
    {
        $this->setModType('scheduler');
        // don't call xarMod:apiLoad() for roles scheduler API
    }
}
