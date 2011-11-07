<?php

/**
 * count number of items
 * @param $args['cids'] optional array of cids we're counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['modid'] module´s ID
 * @param $args['itemtype'] item type
 * @returns int
 * @return number of items
 */
function categories_userapi_countitems($args)
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

    // Get the field names and LEFT JOIN ... ON ... parts from categories
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the categories-specific columns too now
    $categoriesdef = xarMod::apiFunc('categories','user','leftjoin',$args);

    if($dbconn->databaseType == 'sqlite') {
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
    if($dbconn->databaseType == 'sqlite') {
        $sql .= ')';
    }

    $result = $dbconn->Execute($sql);
    if (!$result) return;

    $num = $result->fields[0];

    $result->Close();

    return $num;
}

?>
