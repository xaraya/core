<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * get info on neighbours based on left/right numbers
 * (easiest is to pass it a category array coming from getcat*)
 *
 * @param $args['left'] left number
 * @param $args['right'] right number
// * @param $args['parent'] parent id (optional)
 * @returns array
 * @return TODO
 */
function categories_userapi_getneighbours($args)
{
    extract($args);

    if (!isset($left) || !isset($right) || !is_numeric($left) || !is_numeric($right)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }

//    if (!isset($parent) || !is_numeric($parent)) {
//       $parent = 0;
//    }

// TODO: evaluate this
    // don't return neighbours unless we're at a leaf node
//    if ($left != $right - 1) {
//        return array();
//    }

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $categoriestable = $xartable['categories'];

    $SQLquery = "SELECT id,
                        name,
                        description,
                        image,
                        parent_id,
                        left_id,
                        right_id
                   FROM $categoriestable ";
// next at same level
    $SQLquery .= "WHERE left_id =". ($right + 1);
// next at level higher
    $SQLquery .= " OR right_id =". ($right + 1);
// next at level lower (if we accept non-leaf nodes)
    $SQLquery .= " OR left_id =". ($left + 1);
// previous at same level
    $SQLquery .= " OR right_id =". ($left - 1);
// previous at level higher
    $SQLquery .= " OR left_id =". ($left - 1);
// previous at level lower (if we accept non-leaf nodes)
    $SQLquery .= " OR right_id =". ($right - 1);
// parent node, just in case
//    if (!empty($parent)) {
//        $SQLquery .= " OR id =". $parent;
//    }

    $result = $dbconn->Execute($SQLquery);
    if (!$result) return;

    if ($result->EOF) {
        xarSession::setVar('errormsg', xarML('Unknown Category'));
        return false;
    }

//    $curparent = $parent;
    $info = array();
    while (!$result->EOF) {
        list($cid, $name, $description, $image, $parent, $cleft, $cright) = $result->fields;
        if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$cid")) {
             $result->MoveNext();
             continue;
        }
//        if ($cid == $curparent) {
//            $link = 'parent';
//        } elseif ($cleft == $right + 1) {
        if ($cleft == $right + 1) {
            $link = 'next';
        } elseif ($cleft == $left - 1) {
            // Note: we'll never get here, actually - cfr. parent
            $link = 'previousup';
        } elseif ($cright == $right + 1) {
            // Note: we'll never get here, actually - cfr. parent
            $link = 'nextup';
        } elseif ($cleft == $left + 1) {
            $link = 'nextdown';
        } elseif ($cright == $left - 1) {
            $link = 'previous';
        } elseif ($cright == $right - 1) {
            $link = 'previousdown';
        }
        $info[$cid] = Array(
                            "cid"         => $cid,
                            "name"        => $name,
                            "description" => $description,
                            "image"       => $image,
                            "parent"      => $parent,
                            "left"        => $cleft,
                            "right"       => $cright,
                            "link"        => $link
                           );
        $result->MoveNext();
    }
    return $info;
}

?>
