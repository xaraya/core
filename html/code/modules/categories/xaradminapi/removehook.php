<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Delete all category links for a module - hook for ('module','remove','API')
 * 
 * @param $args['objectid'] ID of the object (must be the module name here !!)
 * @param $args['extrainfo'] extra information
 * @return array Data array
 * @throws BadParameterException Thrown is invalid parameters have been given
 */
function categories_adminapi_removehook($args)
{
    /**
     * Pending
     * TODO: remove per itemtype ?
     */
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, we should get the real module name from objectid
    // here, because the current module is probably going to be 'modules' !!!
    if (!isset($objectid) || !is_string($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID (= module name)', 'admin', 'removehook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    $modid = xarMod::getRegId($objectid);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module ID', 'admin', 'removehook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    if(!xarSecurityCheck('ManageCategoryLink',1,'Link',"$modid:All:All:All")) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    // Delete the link
    $sql = "DELETE FROM $categorieslinkagetable
            WHERE module_id = ?";
    $dbconn->Execute($sql,array(xarMod::getId($objectid)));

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)','admin', 'removehook', 'categories');
        throw new BadParameterException(null, $msg);
    }

    // Return the extra info
    return $extrainfo;
}

?>