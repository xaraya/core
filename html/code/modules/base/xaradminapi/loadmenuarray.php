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
 * @param string $args[layout] return links for menu or links for tabs with menu title info (default links)
 * @returns array
 * @return array of menulinks for a module
 * @throws none
 */
function base_adminapi_loadmenuarray($args)
{
    if (!isset($args['modname']) || !isset($args['modtype']) || !isset($args['funcname'])) {
        $urlinfo = xarController::$request->getInfo();
        if (empty($args['modname'])) $args['modname'] = $urlinfo[0];
        if (empty($args['modtype'])) $args['modtype'] = $urlinfo[1];
        if (empty($args['funcname'])) $args['funcname'] = $urlinfo[2];
    }
    if (!isset($args['layout'])) $args['layout'] = 'links';

    $menu = array();
    $menulinks = array();
    $xmlfile = sys::code() . "modules/{$args['modname']}/xardata/{$args['modtype']}menu-dat.xml";
    if (file_exists($xmlfile)) {
        try {
            $xml = simplexml_load_file($xmlfile);
            if($args['layout'] == 'tabs' && isset($xml->menutitle)) {
                $menutitle = $xml->menutitle;
                $menu['label'] = isset($menutitle->label) ? trim((string)$menutitle->label) : xarML('Actions');
                $menu['title'] = isset($menutitle->title) ? trim((string)$menutitle->title) : xarML('Choose an action to perform');
            }
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

    // set active link
    if (!empty($menulinks)) {
        $currenturl = xarServer::getCurrentURL();
        foreach ($menulinks as $k => $v) {
            // sec check
            if (!empty($v['mask']) && !xarSecurityCheck($v['mask'], 0)) {
                unset($menulinks[$k]);
                continue;
            }
            // active link?
            if (!empty($v['active']) && is_array($v['active']) && in_array($args['funcname'], $v['active']) ||
                $v['url'] == $currenturl) {
                $menulinks[$k]['isactive'] = 1;
            } else {
                $menulinks[$k]['isactive'] = 0;
            }
            $menulinks[$k]['url'] = $v['url'] == $currenturl ? '' : $v['url'];
        }
    }

    // tabbed layout
    if ($args['layout'] == 'tabs') {
        // if we didn't get title info, set some defaults
        if (empty($menu)) {
            $menu['label'] = xarML('Actions');
            $menu['title'] = xarML('Choose an action to perform');
        }
        $menu['menulinks'] = $menulinks;
        return $menu;
    }

    // default, just return the links
    return $menulinks;
}
?>