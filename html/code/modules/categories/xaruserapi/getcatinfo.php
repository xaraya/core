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
 * get info on a specific (list of) category
 * @param $args['cid'] id of category to get info, or
 * @param $args['cids'] array of category ids to get info
 * @returns array
 * @return category info array, or array of cat info arrays, false on failure
 */
function categories_userapi_getcatinfo($args)
{
    extract($args);

    if (!isset($cid) && !isset($cids)) {
       xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
       return false;
    }

    if (empty($cid) && empty($cids)) {
       // nothing to see here, return empty catinfo array
       return array();
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $categoriestable = $xartable['categories'];

    // TODO: simplify api by always using cids, if one cat, only 1 element in the array
    $SQLquery = "SELECT id,
                        name,
                        description,
                        image,
                        parent_id,
                        left_id,
                        right_id,
                        state
                   FROM $categoriestable ";
    if (isset($cid)) {
        $SQLquery .= "WHERE id = ?";
        $bindvars = array($cid);
    } else {
        $bindmarkers = '?' . str_repeat(',?',count($cids)-1);
        $SQLquery .= "WHERE id IN ($bindmarkers)";
        $bindvars = $cids;
    }

    $result = $dbconn->Execute($SQLquery,$bindvars);
    if (!$result) return;

    if ($result->EOF) {
        xarSession::setVar('errormsg', xarML('Unknown Category'));
        return false;
    }

    if (isset($cid)) {
        list($cid, $name, $description, $image, $parent, $left, $right, $state) = $result->fields;
        $info = Array(
                      "cid"         => $cid,
                      "name"        => $name,
                      "description" => $description,
                      "image"       => $image,
                      "parent"      => $parent,
                      "left"        => $left,
                      "right"       => $right,
                      "state"       => $state
                     );
        return $info;
    } else {
        $info = array();
        while (!$result->EOF) {
            list($cid, $name, $description, $image, $parent, $left, $right, $state) = $result->fields;
            $info[$cid] = Array(
                                "cid"         => $cid,
                                "name"        => $name,
                                "description" => $description,
                                "image"       => $image,
                                "parent"      => $parent,
                                "left"        => $left,
                                "right"       => $right,
                                "state"       => $state
                               );
            $result->MoveNext();
        }
        return $info;
    }
}

?>
