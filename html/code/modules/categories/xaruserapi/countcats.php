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
 * Count number of categories (optionally below some category)
 * Usage : $num = xarMod::apiFunc('categories', 'user', 'countcats', $cat);
 *         $total = xarMod::apiFunc('categories', 'user', 'countcats', array());
 *
 * @param $args['cid'] The ID of the category you are counting for (optional)
 * @param $args['left_id'] The left value for that category (optional)
 * @param $args['right_id'] The right value for that category (optional)
 * @return int Returns number of categories
 */
function categories_userapi_countcats($args)
{
    // Get arguments from argument array
    extract($args);

    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    // Database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $categoriestable = $xartable['categories'];
    $bindvars = array();

    // Get number of categories
    if (!empty($left_id) && is_numeric($left_id) &&
        !empty($right_id) && is_numeric($right_id)) {
        $sql = "SELECT COUNT(id) AS childnum
                  FROM $categoriestable
                 WHERE left_id
               BETWEEN ? AND ?";
        $bindvars[] = $left_id; $bindvars[] = $right_id;
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
/* this is terribly slow, at least for MySQL 3.23.49-nt
               BETWEEN P1.left_id AND
                       P1.right_id
                   AND P1.id
                        = ".xar Var Prep For Store($cid); // making my greps happy <mrb>
*/
    } else {
        $sql = "SELECT COUNT(id) AS childnum
                  FROM $categoriestable";
    }

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    $num = $result->fields[0];

    $result->Close();

    return $num;
}

?>
