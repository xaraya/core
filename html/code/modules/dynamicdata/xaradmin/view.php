<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * View items
 * @return string output display string
 */
function dynamicdata_admin_view(Array $args=array())
{
    // Security
    if(!xarSecurityCheck('EditDynamicData')) return;

    if(!xarVarFetch('itemid',   'int',   $itemid,    1, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    // Override if needed from argument array
    extract($args);

    // Default number of items per page in user view
    if (empty($numitems)) {
        $numitems = xarModVars::get('dynamicdata', 'items_per_page');
    }

    // Note: we need to pass all relevant arguments ourselves here
    $object = DataObjectMaster::getObjectList(
                            array('objectid'  => $itemid,
                                  'name'      => $name,
                                  'startnum'  => $startnum,
                                  'numitems'  => $numitems,
                                  'sort'      => $sort,
                                  'catid'     => $catid,
                                  'layout'    => $layout,
                                  'tplmodule' => $tplmodule,
                                  'template'  => $template,
                                  ));

    if (!isset($object)) {
        return;
    }
    if (!$object->checkAccess('view'))
        return xarResponse::Forbidden(xarML('View #(1) is forbidden', $object->label));

    // Pass back the relevant variables to the template if necessary
    $data = $object->toArray();

    // Count the number of items matching the preset arguments - do this before getItems()
    $object->countItems();

    // Get the selected items using the preset arguments
    $object->getItems();

    // Pass the object list to the template
    $data['object'] = $object;

    // TODO: another stray
    $data['catid'] = $catid;
    // TODO: is this needed?
    $data = array_merge($data,xarMod::apiFunc('dynamicdata','admin','menu'));

    if (xarSecurityCheck('AdminDynamicData',0)) {
        if (!empty($data['table'])) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('table' => $data['table']));
        } elseif (!empty($data['join'])) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $objectid,
                                                 'join' => $data['join']));
        } else {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $data['objectid']));
        }
    }

    xarTpl::setPageTitle(xarML('Manage - View #(1)', $data['label']));

    if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-view.xt') ||
        file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-view-' . $data['template'] . '.xt')) {
        return xarTpl::module($data['tplmodule'],'admin','view',$data,$data['template']);
    } else {
        return xarTpl::module('dynamicdata','admin','view',$data);
    }
}

?>
