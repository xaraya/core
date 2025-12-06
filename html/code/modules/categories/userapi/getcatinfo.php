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

/**
 * categories userapi getcatinfo function
 * @extends MethodClass<UserApi>
 */
class GetcatinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get info on a specific (list of) category
     * @param array<string,mixed> $args
     * @param mixed $args ['cid'] id of category to get info, or
     * @param mixed $args ['cids'] array of category ids to get info
     * @return array|bool Returns category info array, or array of cat info arrays, false on failure
     * @see UserApi::getcatinfo()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($cid) && !isset($cids)) {
            $this->session()->setVar('errormsg', $this->ml('Bad arguments for API function'));
            return false;
        }

        if (empty($cid) && empty($cids)) {
            // nothing to see here, return empty catinfo array
            return [];
        }

        $worker = new CategoryWorker($this->getStaticServices());
        if (isset($cid)) {
            $info = $worker->getInfo($cid);
        } else {
            $info = $worker->getInfo($cids);
        }
        return $info;
    }
}
