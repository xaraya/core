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
 * utility function to get an array of menulinks from {modtype} getmenulinks function or {modtype}menu-dat.xml file
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array $args optional array of arguments
 * @param string $args[modname] optional name of module to get links for (default current request module)
 * @param string $args[modtype] optional type of links to return [admin|user] (default current request type)
 * @returns array
 * @return array of menulinks for a module
 * @throws none
 */
function base_adminapi_loadmenuarray($args)
{
    //if (!isset($args['modname']) || !isset($args['modtype']) || !isset($args['funcname'])) {
    if (!isset($args['modname']) || !isset($args['modtype'])) {
        $urlinfo = xarController::$request->getInfo();
        if (empty($args['modname'])) $args['modname'] = $urlinfo[0];
        if (empty($args['modtype'])) $args['modtype'] = $urlinfo[1];
        //if (empty($args['funcname'])) $args['funcname'] = $urlinfo[2];
    }

    $menulinks = array();
    $xmlfile = sys::code() . "modules/{$args['modname']}/xardata/{$args['modtype']}menu-dat.xml";
    if (file_exists($xmlfile)) {
        try {
            $xml = simplexml_load_file($xmlfile);
            $menulinks = array();
            foreach ($xml->menuitems->children() as $menuitem) {
                $target = isset($menuitem->target) ? trim((string)$menuitem->target) : null;
                $label = isset($menuitem->label) ? trim((string)$menuitem->label) : xarML('Missing label');
                $title = isset($menuitem->title) ? trim((string)$menuitem->title) : $label;
                $mask = isset($menuitem->mask) ? trim((string)$menuitem->mask) : null;
                $type = isset($menuitem->type) ? trim((string)$menuitem->type) : $args['modtype'] != 'user' ? $args['modtype'] : null;
                $active = array();
                if (isset($menuitem->includes)) {
                    foreach ($menuitem->includes->children() as $include) {
                        $active[] = trim((string)$include);
                    }
                }
                $menulinks[] = array(
                    //'id' => !empty($target) ? $target : 'main',
                    'label' => $label,
                    'title' => $title,
                    'url' => xarModURL($args['modname'], $type, $target, array()),
                    'type' => !empty($type) ? $type : $args['modtype'],
                    'mask' => $mask,
                    'active' => $active,
                    //'isactive' => in_array($args['funcname'], $active) ? 1 : 0,
                );
            }
        } catch (Exception $e) {
            // invalid? what to do? ignore for now
            // throw ($e);
        }

    } else {
        try {
            $menulinks = xarMod::apiFunc($args['modname'], $args['modtype'], 'getmenulinks');
        } catch (Exception $e) {

        }
    }
    return $menulinks;

}
?>