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
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Checks whether one or more cid is a descendant of one or more category
 * tree branches. Returns true if any cid is a descendant of any branch.
 * Common use: within a template to determine if the visitor is browsing
 * within a region of the website - the 'region' being defined by one or
 * more branches.
 * @author Jason Judge judgej@xaraya.com
 * @param $args['cid'] id of category to test; or
 * @param $args['cids'] array of category ids to test; defaults to query parameter 'catid'
 * @param $args['branch'] id of the category branch; or
 * @param $args['branches'] id of the category branches
 * @param $args['include_root'] flag to indicate whether a branch root is included in the check [false]
 * @return boolean Returns true if one or more cids is a descendant of one or more of the branch roots
 */
function categories_userapi_isdescendant($args)
{
    extract($args);

    // TODO: proper error handling.
    if (empty($cid) && empty($cids)) {
        // TODO: try the query parameter 'catid'

        xarSession::setVar('errormsg', xarML('Bad arguments for API function'));
        return false;
    }

    if (empty($cids)) {$cids = array($cid);}
    if (empty($branches)) {$branches = array($branch);}

    // If there is just one cid, then it may have a prefix to be stripped.
    if (count($cids) == 1) {$cids[0] = str_replace('_', '', $cids[0]);}

    $cids = array_filter($cids, 'is_numeric');
    $branches = array_filter($branches, 'is_numeric');

    if (empty($cids) || empty($branches)) {return false;}

    if (empty($include_root)) {$include_root = false;}

    // Simple check first (not involving the database).
    if ($include_root && array_intersect($cids, $branches)) {
        // One or more of the cids is equal to one or more of the branch roots.
        return true;
    }

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

    $categoriestable = $xartable['categories'];

    $query = '
        SELECT  P1.id
        FROM    '.$categoriestable.' AS P1,
                '.$categoriestable.' AS P2
        WHERE   P2.left_id >= P1.left_id
        AND     P2.left_id <= P1.right_id
        AND     P2.id in(' . implode(',', $cids) . ')
        AND     P1.id in(' . implode(',', $branches) . ')
        AND     P1.id not in(' . implode(',', $cids) . ')';

    $result = $dbconn->SelectLimit($query, 1);
    if (!$result) {return;}

    if (!$result->EOF) {
        return true;
    } else {
        return false;
    }
}

?>