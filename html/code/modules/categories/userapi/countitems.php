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

/**
 * categories userapi countitems function
 * @extends MethodClass<UserApi>
 */
class CountitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Count number of items
     * @param mixed $args ['cids'] optional array of cids we're counting for (OR/AND)
     * @param mixed $args ['andcids'] true means AND-ing categories listed in cids
     * @param mixed $args ['modid'] module�s ID
     * @param mixed $args ['itemtype'] item type
     * @return int|void Returns the item count
     * @see UserApi::countitems()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Optional arguments
        if (!isset($cids)) {
            $cids = [];
        }

        // Security check
        if (!$this->sec()->checkAccess('ViewCategoryLink')) {
            return;
        }

        // Get database setup
        $dbconn = $this->db()->getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from categories
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the categories-specific columns too now
        $categoriesdef = $userapi->leftjoin($args);

        if ($dbconn->databaseType == 'sqlite') {
            $sql = 'SELECT COUNT(*)
                    FROM (SELECT DISTINCT ' . $categoriesdef['item_id'];
        } else {
            $sql = 'SELECT COUNT(DISTINCT ' . $categoriesdef['item_id'] . ')';
        }
        $sql .= ' FROM ' . $categoriesdef['table'];
        $sql .= $categoriesdef['more'];
        if (!empty($categoriesdef['where'])) {
            $sql .= ' WHERE ' . $categoriesdef['where'];
        }
        if ($dbconn->databaseType == 'sqlite') {
            $sql .= ')';
        }

        $result = $dbconn->Execute($sql);
        if (!$result) {
            return;
        }

        $num = $result->fields[0];

        $result->close();

        return $num;
    }
}
