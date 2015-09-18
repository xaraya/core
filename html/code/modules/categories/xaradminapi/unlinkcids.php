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
 * Delete all links for a specific module, itemtype and list of cids (e.g. orphan links)
 * 
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type
 * @param $args['cids'] array of category ids
 * @return boolean|null Returns true on success, null on failure
 * @throws BadParameterException Thrown if invalid parameters have been given.
 */
function categories_adminapi_unlinkcids($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((empty($modid)) || !is_numeric($modid) ||
        (empty($cids)) || !is_array($cids))
    {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Get datbase setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Delete the link
    $bindvars = array();
    $query = "DELETE FROM $categorieslinkagetable
              WHERE module_id = ?
                AND itemtype = ?
                AND category_id IN (?";
    $bindvars[] = (int) $modid;
    $bindvars[] = (int) $itemtype;
    $bindvars[] = (int) array_shift($cids);
    foreach ($cids as $cid) {
        $bindvars[] = (int) $cid;
        $query .= ',?';
    }
    $query .= ')';

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}

?>
