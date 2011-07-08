<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * utility function to create an array for a getmenulinks function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @return array menulinks for the module
 *
 & @TODO: remove this once all modules are calling loadmenuarray
 */
function base_adminapi_menuarray(Array $args=array())
{
    // Handle calls from admin getmenulinks functions which haven't yet been updated to use loadmenuarray()
    if (!isset($args['modname']) && isset($args['module'])) {
        // They all use module instead of modname, and are always called by admin type getmenulinks functions
        $args['modname'] = $args['module'];
        $args['modtype'] = 'admin';
        // since loadmenuarray can itself call the getmenulinks function,
        // so that we don't end up in a loop, we request only links in xml files
        $args['nolinks'] = 1;
    }
    // let loadmenuarray do the work
    return xarMod::apiFunc('base', 'admin', 'loadmenuarray', $args);
}

?>
