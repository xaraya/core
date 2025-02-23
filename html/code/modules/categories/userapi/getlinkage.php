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
use Query;
use xarDB;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getlinkage function
 * @extends MethodClass<UserApi>
 */
class GetlinkageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Fetches category linkage from database
     * @param array<string,mixed> $args Parameter data array
     * @return array Linkage data array
     * @see UserApi::getlinkage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Requires: module, itemtype, itemid (but not validated)

        if (!isset($itemid)) {
            return [];
        }
        if (empty($module)) {
            $module = $this->mod()->getName();
        }

        $modid = $this->mod()->getID($module);

        $tables = $this->db()->getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT');
        $q->addtable($tables['categories_linkage'], 'cl');
        $q->addtable($tables['categories'], 'c');
        $q->join('c.id', 'cl.category_id');
        $q->eq('module_id', $modid);
        if (!empty($itemtype)) {
            if (is_array($itemtype)) {
                $q->in('itemtype', $itemtype);
            } else {
                $q->eq('itemtype', $itemtype);
            }
        }
        if (!empty($itemid)) {
            if (is_array($itemid)) {
                $q->in('item_id', $itemid);
            } else {
                $q->eq('item_id', $itemid);
            }
        }
        if (!empty($basecid)) {
            if (is_array($basecid)) {
                $q->in('basecategory', $basecid);
            } else {
                $q->eq('basecategory', $basecid);
            }
        }
        if (!empty($categoryid)) {
            if (is_array($categoryid)) {
                $q->in('category_id', $categoryid);
            } else {
                $q->eq('category_id', $categoryid);
            }
        }
        $q->addfield('c.id AS id');
        $q->addfield('cl.child_category_id AS childid');
        $q->addfield('c.name AS name');
        $q->addfield('cl.basecategory AS basecategory_id');
        $q->addfield('cl.module_id AS module_id');
        $q->addfield('cl.item_id AS item_id');
        $q->addfield('cl.itemtype AS itemtype');
        //    $q->qecho();
        if (!$q->run()) {
            return [];
        }
        return $q->output();
    }
}
