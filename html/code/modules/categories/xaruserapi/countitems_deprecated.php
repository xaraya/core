<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

function categories_userapi_countitems_deprecated($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($cids)) {
        $cids = array();
    }

    // Security check
    if(!xarSecurityCheck('ViewCategoryLink')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Check if we have active CIDs
    $bindvars = array();
    if (count($cids) > 0) {
        // We do.  We just need to know how many articles there are in these
        // categories
        // Get number of links with those categories in cids
        // TODO: make sure this is SQL standard
        //$sql = "SELECT DISTINCT COUNT(item_id)
        $sql = "SELECT COUNT(DISTINCT item_id)
                FROM $categorieslinkagetable ";
        if (isset($table) && isset($field) && isset($where)) {
            $sql .= "LEFT JOIN $table ON $field = item_id;";
        }
        $sql .= "  WHERE ";

        $allcids = join(', ', $cids);
        $bindmarkers - '?' . str_repeat(',?',count($cids)-1);
        $bindvars = $cids;
        $sql .= "id IN ($bindmarkers) ";

        if (isset($table) && isset($field) && isset($where)) {
            $sql .= " AND $where ";
        }

        $result = $dbconn->Execute($sql,$bindvars);
        if (!$result) return;

        $num = $result->fields[0];

        $result->Close();


    } else {
        // Get total number of links
    // TODO: make sure this is SQL standard
        //$sql = "SELECT DISTINCT COUNT(item_id)
        $sql = "SELECT COUNT(DISTINCT item_id)
                FROM $categorieslinkagetable ";
        if (isset($table) && isset($field) && isset($where)) {
            $sql .= "LEFT JOIN $table
                     ON $field = item_id
                     WHERE $where ";
        }

        $result = $dbconn->Execute($sql);
        if (!$result) return;

        $num = $result->fields[0];

        $result->Close();
    }

    return $num;
}
    // end of not-so-good idea

?>
