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
use xarSession;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getchildren function
 * @extends MethodClass<UserApi>
 */
class GetchildrenMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get direct children of a specific (list of) category
     * @param array<string,mixed> $args
     * @param mixed $args ['cid'] id of category to get children for, or
     * @param mixed $args ['cids'] array of category ids to get children for
     * @param mixed $args ['return_itself'] =Boolean= return the cid itself (default false)
     * @return array|bool Return array of category info arrays, false on failure
     * @see UserApi::getchildren()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($cid) && !isset($cids)) {
            xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
            return false;
        }
        $myself = $args['return_itself'] ?? 0;
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        if (isset($cid)) {
            $children = $worker->getchildren($cid, $myself);
        } else {
            $children = $worker->getchildren($cids, $myself);
        }
        return $children;
    }
}
