<?php
/**
 * Categories Module
 * Add one or more new categories
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
     * Create new category in admin
     * 
     * @param void N/A
     * @return array|null Returns display data array on success, null on failure
     */
    function categories_admin_new()
    {
        if (!xarVar::fetch('return_url',  'isset',  $data['return_url'], NULL, xarVar::DONT_SET)) {return;}
        if(!xarVar::fetch('repeat','int:1:', $data['repeat'], 1, xarVar::NOT_REQUIRED)) {return;}

        if(!xarSecurity::check('AddCategories')) return;

        sys::import('modules.dynamicdata.class.objects.master');
        for ($i=1;$i<=$data['repeat'];$i++) {
            $data['objects'][$i] = DataObjectMaster::getObject(array('name' => xarModVars::get('categories','categoriesobject'), 'fieldprefix' => $i));
        }

        // Setting up necessary data.
        $categories = xarMod::apiFunc('categories', 'user', 'getcat',
                                            array('cid' => false,
                                                  'getchildren' => true));

        $catinfo = array();
        $catinfo['module'] = 'categories';
        $catinfo['itemid'] = '';
        $hooks = xarModHooks::call('item','new','',$catinfo);
        if (empty($hooks)) {
            $data['hooks'] = '';
        } else {
            $data['hooks'] = $hooks;
        }

        $data['category'] = Array('left'=>0,'right'=>0,'name'=>'','description'=>'', 'image' => '');
        $data['cid'] = NULL;

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
        $data['authid'] = xarSec::genAuthKey();
        return $data;
    }
?>