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

/**
 * delete a category
 * @param $args['cid'] the ID of the category
 * @returns bool
 * @return true on success, false on failure
 */
function categories_adminapi_delete($args)
{
    // Get arguments from argument array
    extract($args);
    // Argument check
    if (empty($cid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'cid', 'admin', 'delete', 'categories');
        throw new Exception($msg);
    }

    // Obtain current information on the reference category
    $args = Array(
                  'cid' => $cid,
                  'getparents' => false,
                  'getchildren' => true,
                  'return_itself' => true
                 );
    $cat = xarMod::apiFunc('categories', 'user', 'getcatinfo', $args);
    if ($cat == false) {
        $msg = xarML('Category does not exist. Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'category', 'admin', 'delete', 'categories');
        throw new Exception($msg);
    }
    // These are set to be used later on
    $right = $cat['right'];
    $left = $cat['left'];
    $deslocation_inside = $right - $left + 1;
    $categories = xarMod::apiFunc('categories',
                                'user',
                                'getcat',
                                $args);
    if ($categories == false || count($categories) == 0) {
        $msg = xarML('Category does not exist. Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'category', 'admin', 'delete', 'categories');
        throw new Exception($msg);
    }
    // Useful Variables set...

    // Security check
    // Don´t check by name anything! That´s evil... Unique ID is the way to go.
    if(!xarSecurityCheck('ManageCategories',1,'category',"All:$cid")) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Deleting a category

    //There are two possibilities when deleting a set:
    //1 - Destroy every child inside it
    //2 - Destroy the parent, and make the parent´s parent inherit the children
    //As this model has the moving feature, i think the best option is '1'

    // This part was mostly taken from Joe Celko´s article SQL for Smarties on DBMS, April 1996

    // So deleting all the subtree


    // TODO: Hooks

    // Remove linkage in the category and its sub-tree
    $categorieslinkagetable = $xartable['categories_linkage'];

    $catlist = array();
    foreach ($categories as $mycat) {
        $catlist[] = $mycat['cid'];
    }
    $cats_comma_separated = implode (',', $catlist);

    $sql = "DELETE FROM $categorieslinkagetable
            WHERE category_id IN (" . $cats_comma_separated . ")";
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    // Remove the category and its sub-tree
    $categoriestable = $xartable['categories'];

    $SQLquery = "DELETE FROM $categoriestable
                 WHERE left_id
                 BETWEEN $left AND $right";

    $result = $dbconn->Execute($SQLquery);
    if (!$result) return;

    // Now close up the the gap
    $SQLquery = "UPDATE $categoriestable
                 SET left_id =
                 CASE WHEN left_id > $left
                      THEN left_id - $deslocation_inside
                      ELSE left_id
                 END,
                     right_id =
                 CASE WHEN right_id > $left
                      THEN right_id - $deslocation_inside
                      ELSE right_id
                 END
                 ";
    $result = $dbconn->Execute($SQLquery);
    if (!$result) return;
    // Call delete hooks
    $args['module'] = 'categories';
    $args['itemtype'] = 0;
    $args['itemid'] = $cid;
    xarModCallHooks('item', 'delete', $cid, $args);
    return true;
}
?>