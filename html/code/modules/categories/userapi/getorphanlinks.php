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
use Query;
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getorphanlinks function
 * @extends MethodClass<UserApi>
 */
class GetorphanlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get orphan links
     * @param array<string,mixed> $args
     * @param mixed $args ['modid'] module ID
     * @param mixed $args ['itemtype'] item type (if any)
     * @param mixed $args ['numitems'] optional number of items to return
     * @param mixed $args ['startnum'] optional start at this number (1-based)
     * @return array|bool|void Returns an array of orphan links, or false on failure
     * @see UserApi::getorphanlinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        if (empty($modid)) {
            return false;
        }
        if (!isset($itemtype)) {
            $itemtype = 0;
        }

        sys::import('xaraya.structures.query');
        $tables = $this->db()->getTables();
        $q = new Query();
        $q->addtable($tables['categories'], 'c');
        $q->addtable($tables['categories_linkage'], 'cl');
        $q->leftjoin('cl.category_id', 'c.id');
        $q->addfield('cl.category_id');
        $q->eq('c.id', null);
        $q->addgroup('cl.category_id');
        $q->run();
        $q->qecho();
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $catbases = $worker->getcatbases(
            ['module_id'    => $modid,
                'itemtype' => $itemtype]
        );
        if (empty($catbases)) {
            $args['reverse'] = 1;
            // any link is an orphan here
            return $userapi->getlinks($args);
        }

        $seencid = [];
        foreach ($catbases as $catbase) {
            $seencid[$catbase['category_id']] = 1;
        }
        if (empty($seencid)) {
            $args['reverse'] = 1;
            // any link is an orphan here
            return $userapi->getlinks($args);
        }

        $catlist = $userapi->getcatinfo(['cids' => array_keys($seencid)]);
        uasort($catlist, [$this, 'getorphanlinks_sortbyleft']);

        // Security check
        if (!$this->sec()->checkAccess('ViewCategoryLink')) {
            return;
        }

        // Get database setup
        $dbconn = $this->db()->getConn();

        // Table definition
        $xartable = $this->db()->getTables();
        $categoriestable = $xartable['categories'];
        $categorieslinkagetable = $xartable['categories_linkage'];

        $bindvars = [];
        $bindvars[] = (int) $modid;
        $bindvars[] = (int) $itemtype;

        // find out where the gaps between the base cats are
        $where = [];
        $right = 0;
        foreach ($catlist as $catinfo) {
            // skip empty gaps in the tree
            if ($catinfo['left'] == $right + 1) {
                $right = $catinfo['right'];
                continue;
            }
            $where[] = "($categoriestable.left_id > ? and $categoriestable.left_id < ?)";
            $bindvars[] = (int) $right;
            $bindvars[] = (int) $catinfo['left'];
            $right = $catinfo['right'];
        }
        $where[] = "($categoriestable.left_id > ?)";
        $bindvars[] = (int) $right;

        $sql = "SELECT $categorieslinkagetable.category_id, $categorieslinkagetable.item_id
                  FROM $categorieslinkagetable
             LEFT JOIN $categoriestable
                    ON $categoriestable.id = $categorieslinkagetable.category_id
                 WHERE $categorieslinkagetable.module_id = ?
                   AND $categorieslinkagetable.itemtype = ?
                   AND (" . join(' OR ', $where) . ")";

        if (!empty($numitems)) {
            if (empty($startnum)) {
                $startnum = 1;
            }
            $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1, $bindvars);
        } else {
            $result = $dbconn->Execute($sql, $bindvars);
        }
        if (!$result) {
            return;
        }

        // Makes the linkages array to be returned
        $answer = [];

        while ($result->next()) {
            $fields = $result->fields;
            $iid = array_pop($fields);
            $answer[$iid][] = $fields[0];
        }

        $result->Close();

        // Return Array with linkage
        return $answer;
    }

    public function getorphanlinks_sortbyleft($a, $b)
    {
        if ($a['left'] == $b['left']) {
            return 0;
        }
        return ($a['left'] > $b['left'] ? 1 : -1);
    }
}
