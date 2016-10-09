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
 * @link http://xaraya.info/index.php/release/147.html
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
    if (empty($modid) || !is_numeric($modid)) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null, $msg);
    }

    // By convention an itemtype 0 means "all of them"
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Set up the DELETE query and run
    $xartable =& xarDB::getTables();
    sys::import('xaraya.structures.query');
    $q = new Query('DELETE', $xartable['categories_linkage']);
    $q->eq('module_id', (int)$modid);
    if (!empty($itemtype)) $q->eq('itemtype', (int)$itemtype);
    if (!empty($cids)) $q->in('category_id', $cids);
    $q->run();

    return true;
}

?>