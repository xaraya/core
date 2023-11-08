<?php
/**
 * Categories Module
 * Modify one or more categories
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Function to modify category
 * 
 * @return array<mixed>|string|void Returns display data array on success, null on failure
 */
function categories_admin_modify()
{
    $data = [];
    if (!xarVar::fetch('return_url',  'isset',  $data['return_url'], NULL, xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('itemid', 'int', $data['itemid'], 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('itemtype',    'int',    $itemtype, 2, xarVar::NOT_REQUIRED)) return;
    
    // Support old cids for now
    if (!xarVar::fetch('cid','int::', $cid, NULL, xarVar::DONT_SET)) {return;}
    $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

    // Security check
    if(!xarSecurity::check('EditCategories',1,'All',"All:$cid")) return;
    
    // Root category cannot be modified except by the site admin
    if (($cid == 1) && (xarUser::getVar('id') != xarModVars::get('roles', 'admin')))
        return xarTpl::module('privileges','user','errors', array('layout' => 'no_privileges'));

    // Setting up necessary data.
    sys::import('modules.dynamicdata.class.objects.factory');
    $data['object'] = DataObjectFactory::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
    $data['object']->getItem(array('itemid' => $data['itemid']));

    $data['category'] = $data['object']->getFieldValues();

    $categories = xarMod::apiFunc('categories',
                                'user',
                                'getcat',
                                array('cid' => false,
                                      'eid' => $data['itemid'],
                                      'getchildren' => true));

    $catinfo = $data['category'];
    $catinfo['module'] = 'categories';
    $catinfo['itemtype'] = $itemtype;
    $catinfo['itemid'] = $data['itemid'];
    $hooks = xarModHooks::call('item','modify',$cid,$catinfo);
    if (empty($hooks)) $data['hooks'] = '';
    else $data['hooks'] = $hooks;

    $category_Stack = array ();

    foreach ($categories as $key => $category) {
        $categories[$key]['slash_separated'] = '';

        while ((count($category_Stack) > 0 ) &&
               ($category_Stack[count($category_Stack)-1]['indentation'] >= $category['indentation'])
              ) {
           array_pop($category_Stack);
        }

        foreach ($category_Stack as $stack_cat) {
                $categories[$key]['slash_separated'] .= $stack_cat['name'].'&#160;/&#160;';
        }

        array_push($category_Stack, $category);
        $categories[$key]['slash_separated'] .= $category['name'];
    }

    $data['categories'] = $categories;
    $data['itemtype'] = $itemtype;

    return $data;
}
