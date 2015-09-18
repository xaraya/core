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
 * update linkage for an item - hook for ('item','update','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] Extra information
 * @return array Returns extrainfo array.
 * @throws BadParameterException Thrown if invalid parameters have been given.
 */
function categories_adminapi_updatehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
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
        $categories->updateValue($objectid);
    }

    // Return the extra info
    return $extrainfo;
}

?>
