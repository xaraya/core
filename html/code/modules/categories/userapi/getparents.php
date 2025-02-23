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
use xarSecurity;
use xarSession;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getparents function
 * @extends MethodClass<UserApi>
 */
class GetparentsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get parents of a specific (list of) category
     * @param array<string,mixed> $args
     * @param mixed $args ['cid'] id of category to get children for, or
     * @param mixed $args ['cids'] array of category ids to get children for
     * @param mixed $args ['return_itself'] =Boolean= return the cid itself (default true)
     * @return array|bool|void Returns an array of category info, false on failure
     * @see UserApi::getparents()
     */
    public function __invoke(array $args = [])
    {
        $return_itself = true;
        extract($args);

        if (!isset($cid) && !isset($cids)) {
            $this->session()->setVar('errormsg', $this->ml('Bad arguments for API function'));
            return false;
        }
        $info = [];
        if (empty($cid)) {
            return $info;
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $categoriestable = $xartable['categories'];

        // TODO : evaluate alternative with 2 queries
        $SQLquery = "SELECT
                            P1.id,
                            P1.name,
                            P1.description,
                            P1.image,
                            P1.parent_id,
                            P1.left_id,
                            P1.right_id
                       FROM $categoriestable AS P1,
                            $categoriestable AS P2
                      WHERE P2.left_id
                         >= P1.left_id
                        AND P2.left_id
                         <= P1.right_id";
        /* this is terribly slow, at least for MySQL 3.23.49-nt
                          WHERE P2.left_id
                        BETWEEN P1.left_id AND
                                P1.right_id";
        */
        $SQLquery .= " AND P2.id = ?";
        $SQLquery .= " ORDER BY P1.left_id";

        $result = $dbconn->Execute($SQLquery, [$cid]);
        if (!$result) {
            return;
        }

        while (!$result->EOF) {
            [$pid, $name, $description, $image, $parent, $left, $right] = $result->fields;
            if (!xarSecurity::check('ViewCategories', 0, 'Category', "$name:$cid")) {
                $result->MoveNext();
                continue;
            }

            if (($cid == $pid && $return_itself) || ($cid != $pid)) {
                $info[$pid] = [
                    "cid"         => $pid,
                    "name"        => $name,
                    "description" => $description,
                    "image"       => $image,
                    "parent"      => $parent,
                    "left"        => $left,
                    "right"       => $right,
                ];
            }
            $result->MoveNext();
        }
        return $info;
    }
}
