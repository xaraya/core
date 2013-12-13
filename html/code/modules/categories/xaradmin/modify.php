<?php
/**
 * Categories Module
 * Modify one or more categories
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/**
 * Function to modify category
 * 
 * @param void N/A
 * @return array|null Returns display data array on success, null on failure
 */
function categories_admin_modify()
{
    if (!xarVarFetch('return_url',  'isset',  $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('itemid', 'int', $data['itemid'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',    $itemtype, 2, XARVAR_NOT_REQUIRED)) return;
    
    // Support old cids for now
    if (!xarVarFetch('cid','int::', $cid, NULL, XARVAR_DONT_SET)) {return;}
    $data['itemid'] = !empty($data['itemid']) ? $data['itemid'] : $cid;

    // Security check
    if(!xarSecurityCheck('EditCategories',1,'All',"All:$cid")) return;

    // Setting up necessary data.
    sys::import('modules.dynamicdata.class.objects.master');
    $data['object'] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject')));
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
    $hooks = xarModCallHooks('item','modify',$cid,$catinfo);
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
?>