<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Return the field names and correct values for joining on categories table
 * example : SELECT ..., $cid, ...
 *           FROM ...
 *           LEFT JOIN $table
 *               ON $field = <name of itemid field in your module>
 *           $more
 *           WHERE ...
 *               AND $where // this includes module_id = <your module ID>
 *
 * @param $args['modid'] your module ID (use xarMod::getRegId('mymodule'))
 * @param $args['itemtype'] your item type (default is none) or array of itemtypes
 * @param $args['iids'] optional array of item ids that we are selecting on
 * @param $args['cids'] optional array of cids we're counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @param $args['cidtree'] get items in cid or anywhere below it (= slower than cids, usually)
 *
 * @return array Returns an array in the following format array('table' => 'xar_categories_linkage',
 *               'field' => 'xar_categories_linkage.item_id',
 *               'where' => 'xar_categories_linkage.module_id = ...
 *                           AND xar_categories_linkage.id IN (...)',
 *               'category_id'   => 'xar_categories_linkage.category_id',
 *               ...
 *               'moduleid' => 'nuke_categories_linkage.module_id')
 */
function categories_userapi_leftjoin($args)
{
    /**
     * Pending 
     * @todo think about qstr() and bindvars here, this function return a string, so it's a bit harder
     */
    // Get arguments from argument array
    extract($args);

    $dbconn = xarDB::getConn();

    // Required argument ?
    if (!isset($modid) || !is_numeric($modid)) {
        $msg = xarML('Missing parameter #(1) for #(2)',
                    'modid','categories');
        throw new BadParameterException(null,$msg);
    }

    // Optional argument
    if (!empty($catid)) {
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } elseif (strpos($catid,'-')) {
            $cids = explode('-',$catid);
            $andcids = false;
        } else {
            $cids = array($catid);
            $andcids = false;
        }
    }
    if (!isset($cids)) {
        $cids = array();
    }
    if (!isset($iids)) {
        $iids = array();
    }
    if (!isset($andcids)) {
        $andcids = false;
    }

    // Security check
    if (!xarSecurityCheck('ViewCategoryLink',0)) return array();

/*
    if (count($cids) > 0) {
        if (count($iids) > 0) {
            foreach ($cids as $cid) {
                foreach ($iids as $iid) {
                    if(!xarSecurityCheck('ViewCategoryLink',1,'Link',"$modid:All:$iid:$cid")) return;
                }
            }
        } else {
            foreach ($cids as $cid) {
                if(!xarSecurityCheck('ViewCategoryLink',1,'Link',"$modid:All:All:$cid")) return;
            }
        }
    } elseif (count($iids) > 0) {
    // Note: your module should be checking security for the iids too !
        foreach ($iids as $iid) {
            if(!xarSecurityCheck('ViewCategoryLink',1,'Link',"$modid:All:$iid:All")) return;
        }
    } else {
        if(!xarSecurityCheck('ViewCategoryLink',1,'Link',"$modid:All:All:All")) return;
    }
*/

    // dummy cids array when we're going for x categories at a time
    if (isset($groupcids) && count($cids) == 0) {
        $andcids = true;
        $isdummy = 1;
        for ($i = 0; $i < $groupcids; $i++) {
            $cids[] = $i;
        }
    } else {
        $isdummy = 0;
    }

    // trick : cids = array(_NN) corresponds to cidtree = NN
    if (count($cids) == 1 && preg_match('/^_(\d+)$/',$cids[0],$matches)) {
        $cidtree = $matches[1];
        $cids = array();
    }

    // Table definition
    $xartable =& xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    $leftjoin = array();

    // create list of tables we'll be left joining for AND
    if (count($cids) > 0 && $andcids) {
        $catlinks = array();
        for ($i = 0; $i < count($cids); $i++) {
            $catlinks[] = 'catlink' . $i;
        }
        $linktable = $catlinks[0];
    } else {
        $linktable = $categorieslinkagetable;
    }

    // Add available columns in the categories table
    $columns = array('category_id','item_id','module_id','itemtype','basecategory');
    foreach ($columns as $column) {
        $leftjoin[$column] = $linktable . '.' . $column;
    }

    // Specify LEFT JOIN ... ON ... [WHERE ...] parts
    if (count($cids) > 0 && $andcids) {
        $leftjoin['table'] = $categorieslinkagetable . ' ' . $catlinks[0];
        $leftjoin['more'] = ' ';
        $leftjoin['cids'] = array();
        $leftjoin['cids'][] = $catlinks[0] . '.category_id';
        for ($i = 1; $i < count($catlinks); $i++) {
            $leftjoin['more'] .= ' LEFT JOIN ' . $categorieslinkagetable .
                                     ' ' . $catlinks[$i] .
                                 ' ON ' . $leftjoin['item_id'] . ' = ' .
                                     $catlinks[$i] . '.item_id' .
                                 ' AND ' . $leftjoin['module_id'] . ' = ' .
                                     $catlinks[$i] . '.module_id ';
            $leftjoin['cids'][] = $catlinks[$i] . '.category_id';
        }
    } elseif (!empty($cidtree)) {
        $leftjoin['table'] = $categorieslinkagetable;
        $categoriestable = $xartable['categories'];
        $leftjoin['more'] = ' LEFT JOIN ' . $categoriestable .
                            ' ON ' . $categoriestable . '.id = ' .  $leftjoin['category_id'] . ' ';
    } else {
        $leftjoin['table'] = $categorieslinkagetable;
        $leftjoin['more'] = '';
    }
    $leftjoin['field'] = $leftjoin['item_id'];

    // Specify the WHERE part
    $where = array();
    if (!empty($modid) && is_numeric($modid)) {
        // FIXME: needs a better soluton
        $where[] = $leftjoin['module_id'] . ' = ' . $modid;
    }
    // Note : do not default to 0 here, because we want to be able to do things across item types
    if (isset($itemtype)) {
        if (is_numeric($itemtype)) {
            $where[] = $leftjoin['itemtype'] . ' = ' . $itemtype;
        } elseif (is_array($itemtype) && count($itemtype) > 0) {
            $seentype = array();
            foreach ($itemtype as $id) {
                if (empty($id) || !is_numeric($id)) continue;
                $seentype[$id] = 1;
            }
            if (count($seentype) == 1) {
                $itemtypes = array_keys($seentype);
                $where[] = $leftjoin['itemtype'] . ' = ' . $itemtypes[0];
            } elseif (count($seentype) > 1) {
                $itemtypes = join(', ', array_keys($seentype));
                $where[] = $leftjoin['itemtype'] . ' IN (' . $itemtypes . ')';
            }
        }
    }
    if (isset($basecid)) {
        if (is_numeric($basecid)) {
            $where[] = $leftjoin['basecategory'] . ' = ' . $basecid;
        } elseif (is_array($basecid) && count($basecid) > 0) {
            $seenbasecid = array();
            foreach ($basecid as $id) {
                if (empty($id) || !is_numeric($id)) continue;
                $seenbasecid[$id] = 1;
            }
            if (count($seenbasecid) == 1) {
                $basecids = array_keys($seenbasecid);
                $where[] = $leftjoin['basecategory'] . ' = ' . $basecids[0];
            } elseif (count($seenbasecid) > 1) {
                $basecids = join(', ', array_keys($seenbasecid));
                $where[] = $leftjoin['basecategory'] . ' IN (' . $basecids . ')';
            }
        }
    }
    if (count($cids) > 0) {
        if ($andcids) {
            // select only the 1-2-4 combination, not the 2-1-4, 4-2-1, etc.
            if ($isdummy) {
                $oldcid = '';
                foreach ($leftjoin['cids'] as $cid) {
                    if (!empty($oldcid)) {
                        $where[] .= $oldcid . ' < ' . $cid;
                    }
                    $oldcid = $cid;
                }
            // select the categories you wanted
            } else {
                for ($i = 0; $i < count($cids); $i++) {
                    $where[] = $catlinks[$i] . '.category_id = ' . $cids[$i];
                }
            }
            // include all cids here
            $leftjoin['category_id'] = join(', ',$leftjoin['cids']);
        } else {
            $allcids = join(', ', $cids);
            $where[] = $leftjoin['category_id'] . ' IN (' . $allcids . ')';
        }
    }
    if (!empty($cidtree)) {
        $cat = xarMod::apiFunc('categories','user','getcatinfo',Array('cid' => $cidtree));
        if (!empty($cat)) {
            $where[] = $categoriestable . '.left_id >= ' . $cat['left_id'];
            $where[] = $categoriestable . '.left_id <= ' . $cat['right_id'];
        }
    }
    if (count($iids) > 0) {
        $alliids = join(', ', $iids);
        $where[] = $leftjoin['item_id'] . ' IN (' . $alliids . ')';
    }
    if (count($where) > 0) {
        $leftjoin['where'] = join(' AND ', $where);
    } else {
        $leftjoin['where'] = '';
    }

    return $leftjoin;
}

?>