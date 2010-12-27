<?php

/**
 * count number of categories (optionally below some category)
 * Usage : $num = xarMod::apiFunc('categories', 'user', 'countcats', $cat);
 *         $total = xarMod::apiFunc('categories', 'user', 'countcats', array());
 *
 * @param $args['cid'] The ID of the category you are counting for (optional)
 * @param $args['left'] The left value for that category (optional)
 * @param $args['right'] The right value for that category (optional)
 * @returns int
 * @return number of categories
 */
function categories_userapi_countcats($args)
{
    // Get arguments from argument array
    extract($args);

    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    // Database information
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categoriestable = $xartable['categories'];
    $bindvars = array();

    // Get number of categories
    if (!empty($left) && is_numeric($left) &&
        !empty($right) && is_numeric($right)) {
        $sql = "SELECT COUNT(id) AS childnum
                  FROM $categoriestable
                 WHERE left_id
               BETWEEN ? AND ?";
        $bindvars[] = $left; $bindvars[] = $right;
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
