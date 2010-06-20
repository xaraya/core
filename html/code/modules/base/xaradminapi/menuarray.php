<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to create an array for a getmenulinks function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array of menulinks for a module
 *
 & @TODO: remove this once all modules are calling loadmenuarray
 */
function base_adminapi_menuarray($args)
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
