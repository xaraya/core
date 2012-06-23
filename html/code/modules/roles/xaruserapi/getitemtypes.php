<?php
/**
 * Retrieve a list of itemtypes of this module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Utility function to retrieve the list of itemtypes of this module (if any).
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @return array the itemtypes of this module and their description *
 */
function roles_userapi_getitemtypes(Array $args=array())
{
    return xarMod::apiFunc('dynamicdata','user','getmoduleitemtypes',array('moduleid' => 27, 'native' =>false));
}
?>
