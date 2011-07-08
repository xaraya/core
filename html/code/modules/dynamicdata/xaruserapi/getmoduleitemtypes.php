<?php
/**
 * Retrieve list of itemtypes of any module
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to retrieve the list of item types of a module (if any)
 *
 * @todo remove this before it can propagate
 * @param array    $args array of optional parameters<br/>
 * @return array containing the item types and their description
 */
function dynamicdata_userapi_getmoduleitemtypes(Array $args=array())
{
    return DataObjectMaster::getModuleItemTypes($args);      
}
?>