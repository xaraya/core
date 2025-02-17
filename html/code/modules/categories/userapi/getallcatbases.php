<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\UserApi;
use CategoryWorker;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getallcatbases function
 * @extends MethodClass<UserApi>
 */
class GetallcatbasesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get category bases
     * @param mixed $args ['object'] the name of the object (required)
     * @param mixed $args ['property'] the name of the categories property of the object (optional)
     * @return array of category bases
     * @see UserApi::getallcatbases()
     */
    public function __invoke(array $args = [])
    {
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $bases = $worker->getcatbases($args);
        return $bases;
    }
}
