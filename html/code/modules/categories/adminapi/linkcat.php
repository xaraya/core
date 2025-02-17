<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use Xaraya\Modules\Categories\UserApi;
use BadParameterException;
use Query;
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi linkcat function
 * @extends MethodClass<AdminApi>
 */
class LinkcatMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Link items to categories or links each cid in cids to each iid in iids
     * @param mixed $args ['cids'] Array of IDs of the category
     * @param mixed $args ['iids'] Array of IDs of the items
     * @param mixed $args ['basecids'] Array of IDs of the base category
     * @param mixed $args ['modid'] ID of the module
     * @param mixed $args ['itemtype'] item type
     * @param mixed $args ['clean_first'] If is set to true then any link of the item IDs
     * at iids will be removed before inserting the
     * new ones
     * @return bool|null Returns true on success, null on failure.
     * @throws \BadParameterException Thrown if invalid parameters have been given
     * @see AdminApi::linkcat()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Argument check
        if (isset($args['clean_first']) && $args['clean_first'] == true) {
            $clean_first = true;
        } else {
            $clean_first = false;
        }

        // Do we check the validity of the categories before linking?
        $check = $args['check'] ?? true;

        if (
            (!isset($args['cids'])) ||
            (!isset($args['iids'])) ||
            (!isset($args['modid']))
        ) {
            $msg = xarML('Invalid Parameter Count');
            throw new BadParameterException(null, $msg);
        }
        $basecids = $args['basecids'] ?? [];
        if (isset($args['itemtype']) && is_numeric($args['itemtype'])) {
            $itemtype = $args['itemtype'];
        } else {
            $itemtype = 0;
        }
        if (!empty($itemtype)) {
            $modtype = $itemtype;
        } else {
            $modtype = 'All';
        }

        if ($check) {
            foreach ($args['cids'] as $cid) {
                $cidparts = explode('.', $cid);
                $cid = $cidparts[0];
                $cat = $userapi->getcatinfo(
                    [
                        'cid' => $cid,
                    ]
                );
                if ($cat == false) {
                    $msg = xarML('Unknown Category');
                    throw new BadParameterException(null, $msg);
                }
            }
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $categorieslinkagetable = $xartable['categories_linkage'];

        if ($clean_first) {
            // Get current links
            $childiids = $userapi->getlinks(['iids' => $args['iids'],
                'itemtype' => $itemtype,
                'modid' => $args['modid'],
                'reverse' => 0]);
            if (count($childiids) > 0) {
                // Security check
                foreach ($args['iids'] as $iid) {
                    foreach (array_keys($childiids) as $cid) {
                        if (!xarSecurity::check('EditCategoryLink', 1, 'Link', "$args[modid]:$modtype:$iid:$cid")) {
                            return;
                        }
                    }
                }
                // Delete old links
                $bindmarkers = '?' . str_repeat(',?', count($args['iids']) - 1);
                $sql = "DELETE FROM $categorieslinkagetable
                        WHERE module_id = $args[modid] AND
                              itemtype = $itemtype AND
                              item_id IN ($bindmarkers)";
                $result = $dbconn->Execute($sql, $args['iids']);
                if (!$result) {
                    return;
                }
            } else {
                // Security check
                foreach ($args['iids'] as $iid) {
                    if (!xarSecurity::check('SubmitCategoryLink', 1, 'Link', "$args[modid]:$modtype:$iid:All")) {
                        return;
                    }
                }
            }
        }

        foreach ($args['iids'] as $iid) {
            sys::import('xaraya.structures.query');
            // @checkme where is this coming from?
            //sys::import('modules.categories.class.tag');
            $i = 0;
            foreach ($args['cids'] as $cid) {
                // Security check
                if (!xarSecurity::check('SubmitCategoryLink', 1, 'Link', "$args[modid]:$modtype:$iid:$cid")) {
                    return;
                }

                $basecid = $basecids[$i] ?? 0;
                $cidparts = explode('.', $cid);
                $cid = $cidparts[0];
                $ccid = $cidparts[1] ?? 0;

                // Insert the link
                $q = new Query('INSERT', $categorieslinkagetable);
                $q->addfield('category_id', $cid);
                $q->addfield('child_category_id', $ccid);
                $q->addfield('basecategory', $basecid);
                // @checkme where is this coming from?
                //$tag = Tag($args['modid'], $itemtype, $iid, $q);

                if (!$q->run()) {
                    return;
                }

                /*
                $sql = "INSERT INTO $categorieslinkagetable (
                          ,
                          ,
                          item_id,
                          itemtype,
                          module_id,
                          )
                        VALUES(?,?,?,?,?,?)";
                $bindvars = array(, $ccid, $iid, $itemtype, $args['modid'], $basecid);
                $result = $dbconn->Execute($sql,$bindvars);
                if (!$result) return;
                */
                $i++;
            }
        }

        /* Don't implement for now
        // Remove the entries of these categories from the summary table
        $categorieslinkagesummarytable = $xartable['categories_linkage_summary'];
        $bindmarkers = '?' . str_repeat(',?',count($args['cids'])-1);
        $sql = "DELETE FROM $categorieslinkagesummarytable
                WHERE module_id = $args[modid] AND
                      itemtype = $itemtype AND
                      category_id IN ($bindmarkers)";
        $result = $dbconn->Execute($sql,$args['cids']);

        // Insert the entries of these categories from the summary table
        foreach ($args['cids'] as $cid)
        {
          $sql = "INSERT INTO $categorieslinkagesummarytable (
                    category_id,
                    item_id,
                    itemtype,
                    module_id,
                    links)
                  VALUES(?,?,?,?,?)";
          $bindvars = array($cid, $iid, $itemtype, $args['modid'], 0);
          $result = $dbconn->Execute($sql,$bindvars);
          if (!$result) return;
        }
        */

        return true;
    }
}
