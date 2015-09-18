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
 * Delete all links for a specific Item ID
 * 
 * @param $args['iid'] the ID of the item
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type
 * @param $args['confirm'] from delete GUI
 * @return boolean|null Returns true on success, null on failure
 * @throws BadParameterException Thrown if invalid parameters have been given
 */
function categories_adminapi_unlink($args)
{
    // Get arguments from argument array
    extract($args);

    if (!empty($confirm)) {
        if (!xarSecurityCheck('AdminCategories')) return;
    } else {
        // Argument check
        if ((empty($modid)) || !is_numeric($modid) ||
            (empty($iid)) || !is_numeric($iid))
        {
            $msg = xarML('Invalid Parameter Count', '', 'admin', 'unlink', 'categories');
            throw new BadParameterException(null, $msg);
        }

        if (!isset($itemtype) || !is_numeric($itemtype)) {
            $itemtype = 0;
        }

        // Confirm linkage exists
        $childiids = xarMod::apiFunc('categories',
                                  'user',
                                  'getlinks',
                                  array('iids' => array($iid),
                                        'itemtype' => $itemtype,
                                        'modid' => $modid,
                                        'reverse' => 0));

    // Note : this is a feature, not a bug in this case :-)
        // If Link doesn�t exist then
        if ($childiids == Array()) {
            return true;
        }

        if (!empty($itemtype)) {
            $modtype = $itemtype;
        } else {
            $modtype = 'All';
        }

    // Note : yes, edit is enough here (cfr. updatehook)
        $cids = array_keys($childiids);
        foreach ($cids as $cid) {
            if(!xarSecurityCheck('EditCategoryLink',1,'Link',"$modid:$modtype:$iid:$cid")) return;
        }
    }

    // Get datbase setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Delete the link
    $bindvars = array();
    $query = "DELETE FROM $categorieslinkagetable";

    if (!empty($modid)) {
        if (!is_numeric($modid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                         'module id', 'admin', 'unlink', 'categories');
            throw new BadParameterException(null, $msg);
        }
        if (empty($itemtype) || !is_numeric($itemtype)) {
            $itemtype = 0;
        }
        $query .= " WHERE module_id = ? AND itemtype = ?";
        $bindvars[] = $modid; $bindvars[] = $itemtype;
        if (!empty($iid)) {
            $query .= " AND item_id = ?";
            $bindvars[] =  $iid;
        }
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}

?>
