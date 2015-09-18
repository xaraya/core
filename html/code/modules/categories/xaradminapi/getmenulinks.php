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
 * Utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * 
 * @param void N/A
 * @return arrau Array containing menulinks for the main menu items.
 */
function categories_adminapi_getmenulinks()
{
    return xarMod::apiFunc('base','admin','menuarray',array('module' => 'categories'));

}

?>