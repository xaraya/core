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
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi countcats function
 * @extends MethodClass<UserApi>
 */
class CountcatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Count number of categories (optionally below some category)
     * Usage : $num = $userapi->countcats($cat);
     *         $total = $userapi->countcats(array());
     * @param array<string,mixed> $args
     * @param mixed $args ['cid'] The ID of the category you are counting for (optional)
     * @param mixed $args ['left_id'] The left value for that category (optional)
     * @param mixed $args ['right_id'] The right value for that category (optional)
     * @return int|void Returns number of categories
     * @see UserApi::countcats()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Security check
        if (!xarSecurity::check('ViewCategories')) {
            return;
        }

        // Database information
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $categoriestable = $xartable['categories'];
        $bindvars = [];

        // Get number of categories
        if (!empty($left_id) && is_numeric($left_id) &&
            !empty($right_id) && is_numeric($right_id)) {
            $sql = "SELECT COUNT(id) AS childnum
                      FROM $categoriestable
                     WHERE left_id
                   BETWEEN ? AND ?";
            $bindvars[] = $left_id;
            $bindvars[] = $right_id;
        } elseif (!empty($cid) && is_numeric($cid)) {
            $sql = "SELECT COUNT(P2.id) AS childnum
                      FROM $categoriestable AS P1,
                           $categoriestable AS P2
                     WHERE P2.left_id
                        >= P1.left_id
                       AND P2.left_id
                        <= P1.right_id
                       AND P1.id = ?";
            $bindvars[] = $cid;
        } else {
            $sql = "SELECT COUNT(id) AS childnum
                      FROM $categoriestable";
        }

        $result = $dbconn->Execute($sql, $bindvars);
        if (!$result) {
            return;
        }
        $result->first();
        [$num] = $result->fields;

        $result->Close();

        return $num;
    }
}
