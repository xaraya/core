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
 */
function base_adminapi_loadadminmenuarray($args)
{
    if (!isset($args['module'])) {
        $urlinfo = xarController::$request->getInfo();
        $args['module'] = $urlinfo[0];
    }
    $menuarray = array();
    try {
        $menufile = sys::code() . 'modules/' . $args['module'] . '/xardata/adminmenu-dat.xml';
        $xmlobject = simplexml_load_file($menufile);
        $menuarray = array();
        if(isset($xmlobject->menutitle)) {
            $menutitle = $xmlobject->menutitle;
            $menuarray['title'] = array();
            $menuarray['title']['label'] = isset($menutitle->label) ? trim((string)$menutitle->label) : xarML('Actions');
            $menuarray['title']['title'] = isset($menutitle->title) ? trim((string)$menutitle->title) : null;

        }
        foreach ($xmlobject->menuitems->children() as $menuitem) {
            $type = isset($menuitem->type) ? trim((string)$menuitem->type) : null;
            $target = isset($menuitem->target) ? trim((string)$menuitem->target) : null;
            $label = isset($menuitem->label) ? trim((string)$menuitem->label) : xarML('Missing label');
            $title = isset($menuitem->title) ? trim((string)$menuitem->title) : $label;
            $mask = isset($menuitem->mask) ? trim((string)$menuitem->mask) : null;
            $includes = isset($menuitem->includes) ? trim((string)$menuitem->includes->children()) : array();
            $menuarray['items'][] = array(
                        'type' => $type,
                        'target' => $target,
                        'title' => $title,
                        'label' => $label,
                        'mask' => $mask,
                        'includes' => $includes,
                        );
        }
    } catch(Exception $e) {
    }
    return $menuarray;
}

?>
