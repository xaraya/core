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
 * Create linkage for an item - hook for ('item','create','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return array Data array
 * @throws BadParameterException Thrown if object was not found.
 */
function categories_adminapi_createhook($args)
{
    extract($args);

    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    sys::import('modules.dynamicdata.class.properties.master');
    $categories = DataPropertyMaster::getProperty(array('name' => 'categories'));
    if ($categories->checkInput('hookedcategories')) {
// CHECKME: aren't we supposed to save the categories here ?
        $categories->createValue($objectid);
    }

    // Return the extra info
    return $extrainfo;
}

?>
