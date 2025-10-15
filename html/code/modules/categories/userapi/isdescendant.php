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
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi isdescendant function
 * @extends MethodClass<UserApi>
 */
class IsdescendantMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Checks whether one or more cid is a descendant of one or more category
     * tree branches. Returns true if any cid is a descendant of any branch.
     * Common use: within a template to determine if the visitor is browsing
     * within a region of the website - the 'region' being defined by one or
     * more branches.
     * @author Jason Judge judgej@xaraya.com
     * @param array<string,mixed> $args
     * @param mixed $args ['cid'] id of category to test; or
     * @param mixed $args ['cids'] array of category ids to test; defaults to query parameter 'catid'
     * @param mixed $args ['branch'] id of the category branch; or
     * @param mixed $args ['branches'] id of the category branches
     * @param mixed $args ['include_root'] flag to indicate whether a branch root is included in the check [false]
     * @return bool|void Returns true if one or more cids is a descendant of one or more of the branch roots
     * @see UserApi::isdescendant()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // TODO: proper error handling.
        if (empty($cid) && empty($cids)) {
            // TODO: try the query parameter 'catid'

            $this->session()->setVar('errormsg', $this->ml('Bad arguments for API function'));
            return false;
        }

        if (empty($cids)) {
            $cids = [$cid];
        }
        if (empty($branches)) {
            $branches = [$branch];
        }

        // If there is just one cid, then it may have a prefix to be stripped.
        if (count($cids) == 1) {
            $cids[0] = str_replace('_', '', $cids[0]);
        }

        $cids = array_filter($cids, 'is_numeric');
        $branches = array_filter($branches, 'is_numeric');

        if (empty($cids) || empty($branches)) {
            return false;
        }

        if (empty($include_root)) {
            $include_root = false;
        }

        // Simple check first (not involving the database).
        if ($include_root && array_intersect($cids, $branches)) {
            // One or more of the cids is equal to one or more of the branch roots.
            return true;
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $categoriestable = $xartable['categories'];

        $query = '
            SELECT  P1.id
            FROM    ' . $categoriestable . ' AS P1,
                    ' . $categoriestable . ' AS P2
            WHERE   P2.left_id >= P1.left_id
            AND     P2.left_id <= P1.right_id
            AND     P2.id in(' . implode(',', $cids) . ')
            AND     P1.id in(' . implode(',', $branches) . ')
            AND     P1.id not in(' . implode(',', $cids) . ')';

        $result = $dbconn->SelectLimit($query, 1);
        if (!$result) {
            return;
        }

        if ($result->first()) {
            return true;
        } else {
            return false;
        }
    }
}
