<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Get the list of modules and itemtypes for which we're categorising items
 *
 * @return array Returns an array in the following format $array[$modid][$itemtype] = array('items' => $numitems,'cats' => $numcats,'links' => $numlinks);
 */
function categories_userapi_getmodules($args)
{
    // Get arguments from argument array
    extract($args);

    // Security check
    if(!xarSecurityCheck('ViewCategoryLink')) return;

    if (empty($cid) || !is_numeric($cid)) {
        $cid = 0;
    }

    // Database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $linkagetable = $xartable['categories_linkage'];
    $prefix = xarDB::getPrefix();
    $modulestable = $prefix . '_modules';

    if($dbconn->databaseType == 'sqlite') {

    // TODO: see if we can't do this some other way in SQLite

        $bindvars = array();
        // Get links
        $sql = "SELECT c.module_id, c.itemtype, COUNT(*), m.name
                FROM $linkagetable c, $modulestable m
                WHERE c.module_id = m.regid";
        if (!empty($cid)) {
            $sql .= " AND category_id = ?";
            $bindvars[] = $cid;
        }
        $sql .= " GROUP BY m.name, c.itemtype";

        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) return;

        $modlist = array();
        while (!$result->EOF) {
            list($regid,$itemtype,$numlinks) = $result->fields;
            if (!isset($modlist[$regid])) {
                $modlist[$regid] = array();
            }
            $modlist[$regid][$itemtype] = array('items' => 0, 'cats' => 0, 'links' => $numlinks);
            $result->MoveNext();
        }
        $result->close();

        // Get items
        $sql = "SELECT c.module_id,, c.itemtype, COUNT(*),  m.name
                FROM (SELECT DISTINCT c.item_id, c.module_id, m.regid, c.itemtype
                      FROM $linkagetable c, $modulestable m
                      WHERE c.module_id = m.regid";
        if (!empty($cid)) {
            $sql .= " AND category_id = ?";
            $bindvars[] = $cid;
        }
        $sql .= ") GROUP BY m.name, c.itemtype";

        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) return;

        while (!$result->EOF) {
            list($regid,$itemtype,$numitems) = $result->fields;
            $modlist[$regid][$itemtype]['items'] = $numitems;
            $result->MoveNext();
        }
        $result->close();

        // Get cats
        $sql = "SELECT c.module_id, c.itemtype, COUNT(*), m.name
                FROM (SELECT DISTINCT c.category_id, c.module_id, m.regid, c.itemtype
                      FROM $linkagetable c, $modulestable m
                      WHERE c.module_id = m.regid";
        if (!empty($cid)) {
            $sql .= " AND category_id = ?";
            $bindvars[] = $cid;
        }
        $sql .= ") GROUP BY m.name, c.itemtype";

        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) return;

        while (!$result->EOF) {
            list($modid,$itemtype,$numcats) = $result->fields;
            $modlist[$modid][$itemtype]['cats'] = $numcats;
            $result->MoveNext();
        }
        $result->close();

    } else {
        $bindvars = array();
        // Get items
        $sql = "SELECT c.module_id, c.itemtype, COUNT(*), COUNT(DISTINCT item_id), COUNT(DISTINCT category_id), m.name
                FROM $linkagetable c, $modulestable m
                WHERE c.module_id = m.regid";
        if (!empty($cid)) {
            $sql .= " AND category_id = ?";
            $bindvars[] = $cid;
        }
        $sql .= " GROUP BY m.name, c.itemtype";

        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) return;

        $modlist = array();
        while (!$result->EOF) {
            list($regid,$itemtype,$numlinks,$numitems,$numcats) = $result->fields;
            if (!isset($modlist[$regid])) {
                $modlist[$regid] = array();
            }
            $modlist[$regid][$itemtype] = array('items' => $numitems, 'cats' => $numcats, 'links' => $numlinks);
            $result->MoveNext();
        }
        $result->close();
    }

    return $modlist;
}

?>