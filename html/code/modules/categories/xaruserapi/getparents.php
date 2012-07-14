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
 * get parents of a specific (list of) category
 *
 * @param $args['cid'] id of category to get children for, or
 * @param $args['cids'] array of category ids to get children for
 * @param $args['return_itself'] =Boolean= return the cid itself (default true)
 * @returns array
 * @return array of category info arrays, false on failure
 */
function categories_userapi_getparents($args)
{
    $return_itself = true;
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }
    $info = array();
    if (empty($cid)) {
        return $info;
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $categoriestable = $xartable['categories'];

// TODO : evaluate alternative with 2 queries
    $SQLquery = "SELECT
                        P1.id,
                        P1.name,
                        P1.description,
                        P1.image,
                        P1.parent_id,
                        P1.left_id,
                        P1.right_id
                   FROM $categoriestable AS P1,
                        $categoriestable AS P2
                  WHERE P2.left_id
                     >= P1.left_id
                    AND P2.left_id
                     <= P1.right_id";
/* this is terribly slow, at least for MySQL 3.23.49-nt
                  WHERE P2.left_id
                BETWEEN P1.left_id AND
                        P1.right_id";
*/
    $SQLquery .= " AND P2.id = ?";
    $SQLquery .= " ORDER BY P1.left_id";

    $result = $dbconn->Execute($SQLquery,array($cid));
    if (!$result) return;

    while (!$result->EOF) {
        list($pid, $name, $description, $image, $parent, $left, $right) = $result->fields;
        if (!xarSecurityCheck('ViewCategories',0,'Category',"$name:$cid")) {
             $result->MoveNext();
             continue;
        }

        if(($cid == $pid && $return_itself) || ($cid != $pid)) {
            $info[$pid] = Array(
                                "cid"         => $pid,
                                "name"        => $name,
                                "description" => $description,
                                "image"       => $image,
                                "parent"      => $parent,
                                "left"        => $left,
                                "right"       => $right
                                );
        }
        $result->MoveNext();
    }
    return $info;
}

?>
