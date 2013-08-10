<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Utility function to get an array of menulinks from {modtype} getmenulinks function or {modtype}menu-dat.xml file
 * This function pays no respect to the active state of the module, calling functions must determine that
 * It looks for menu links for the specified module and type, falling back to the current request module and type
 * It first looks for links in xml files in ./code/modules/{modname}/xardata/{modtype}menu-dat.xml
 * If the xml file doesn't exist, it falls back to looking for links in the modules' getmenulinks function
 * If no links are found the function returns an empty array
 *
 * Any module which supplies links in xml files is expected to use this function to retrieve those links.
 * See examples of use in core module _adminapi_getmenulinks functions
 *
 * This function is called by the MenuBlocks class and its children
 *
 * This function is also used by the base/xartemplates/includes/admin-menu.xt file to supply links
 * for admin tabs. Other modules can include that instead of creating their own admin-menu.xt file
 * <xar:template type="module" module="base" file="admin-menu" subdata="array('modname' => 'module', 'modtype' => 'admin')"/>
 * Easter egg, the file can also be used in the same way for user tabs by changing the type :)
 * <xar:template type="module" module="base" file="admin-menu" subdata="array('modname' => 'module', 'modtype' => 'user')"/>
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @param string   $args[modname] optional name of module to get links for (default current request module)<br/>
 * @param string   $args[modtype] optional type of links to return [admin|user] (default current request type)<br/>
 * @param string   $args[layout] return links for menu or links for tabs with menu title info (default links)<br/>
 * @param boolean  $args[noxml] optionally force looking for links only from a getmenulinks function<br/>
 * @param boolean  $args[nolinks] optionally force looking for links only from xml files
 * @return array menulinks for the module
 */
function base_adminapi_loadmenuarray(Array $args=array())
{
    if (!isset($args['modname']) || !isset($args['modtype']) || !isset($args['funcname'])) {
        $urlinfo = xarController::$request->getInfo();
        if (empty($args['modname'])) $args['modname'] = $urlinfo[0];
        if (empty($args['modtype'])) $args['modtype'] = $urlinfo[1];
        // handle modules using util as an admin type
        if ($args['modtype'] == 'util') $args['modtype'] = 'admin';
        if (empty($args['funcname'])) $args['funcname'] = $urlinfo[2];
        if (empty($args['urlargs'])) $args['urlargs'] = array();
    }
    if (!isset($args['layout'])) $args['layout'] = 'links';

    // Make sure we have default values
    $menu = array();
    $menulinks = array();
    
    if (!empty($args['xmldata'])) {
        $xmlfile = sys::code() . "modules/{$args['modname']}/xardata/{$args['xmldata']}-dat.xml";
    } else {
        $xmlfile = sys::code() . "modules/{$args['modname']}/xardata/{$args['modtype']}menu-dat.xml";
    }
    if (file_exists($xmlfile) && empty($args['noxml'])) {
        try {
            $xml = simplexml_load_file($xmlfile);

            if(isset($xml->menutitle)) {
                $menutitle = $xml->menutitle;
                $menu['label'] = isset($menutitle->label) ? trim((string)$menutitle->label) : xarML('Actions');
                $menu['title'] = isset($menutitle->title) ? trim((string)$menutitle->title) : xarML('Choose an action to perform');
                $menu['variable'] = isset($menutitle->variable) ? trim((string)$menutitle->variable) : '';
            }

            foreach ($xml->menuitems->children() as $menuitem) {
                $target    = isset($menuitem->target)    ? trim((string)$menuitem->target) : null;
                $label     = isset($menuitem->label)     ? trim((string)$menuitem->label) : xarML('Missing label');
                $title     = isset($menuitem->title)     ? trim((string)$menuitem->title) : $label;
                $mask      = isset($menuitem->mask)      ? trim((string)$menuitem->mask) : null;
                $condition = isset($menuitem->condition) ? trim((string)$menuitem->condition) : null;
                $type      = isset($menuitem->type)      ? trim((string)$menuitem->type) : $args['modtype'] != 'user' ? $args['modtype'] : null;
                $value     = isset($menuitem->value)     ? $menuitem->value : NULL;
                $active    = array();
                if (isset($menuitem->includes)) {
                    foreach ($menuitem->includes->children() as $include) {
                        $active[] = trim((string)$include);
                    }
                }
                if (isset($value)) {
                    if(preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(.*\)/',$value)) {
                        eval('$value = ' . $value .';');
                    }
                }
                if (!empty($menu['variable'])) {
                    $args['urlargs'][$menu['variable']] = $value;
                }
                $url = xarModURL($args['modname'], $type, $target, $args['urlargs']);
                $menulinks[] = array(
                    'label'       => $label,
                    'title'       => $title,
                    'url'         => $url,
                    'type'        => !empty($type) ? $type : $args['modtype'],
                    'mask'        => $mask,
                    'condition'   => $condition,
                    'active'      => $active,
                    'value'       => $value,
                    //'isactive' => in_array($args['funcname'], $active) ? 1 : 0,
                );
            }
        } catch (Exception $e) {
            // invalid? what to do? ignore for now
            // throw ($e);
        }

    } elseif (empty($args['nolinks'])) {
        try {
            $menulinks = xarMod::apiFunc($args['modname'], $args['modtype'], 'getmenulinks');
        } catch (Exception $e) {

        }
    }

    // set active link
    if (!empty($menulinks)) {
        $currenturl = xarServer::getCurrentURL();
        foreach ($menulinks as $k => $v) {
            // Security check
            if (!empty($v['mask']) && !xarSecurityCheck($v['mask'], 0)) {
                unset($menulinks[$k]);
                continue;
            }
            // General condition
            if (isset($v['condition'])) {
                if(preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(.*\)/',$v['condition'])) {
                    eval('$value = ' . $v['condition'] .';');
                } else {
                    $value = $v['condition'];
                }
                if(!$value) {
                    unset($menulinks[$k]);
                    continue;
                }
            }
            // Active link?
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