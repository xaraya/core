<?php
/**
 * Show products categories screen
 *
 * @package modules
 * @copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage products
 * @author marcinmilan
 *
 *  based on:
 * (c) 2003 XT-Commerce
 * (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
 * (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
 * (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
 */

/**
 * Show products categories screen
 */
function products_admin_categories_screen()
{
    include_once 'modules/commerce/xarclasses/object_info.php';

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('cID',    'int',  $data['cID'],   0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cPath',  'int',  $data['cPath'], 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('current_category_id',  'int',  $data['current_category_id'], '', XARVAR_NOT_REQUIRED)) {return;}

    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $languages = xarModAPIFunc('commerce','user','get_languages');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));
    $data['languages'] = $languages;
    $data['currentlang'] = $currentlang;
    if(!xarVarFetch('langid',    'int',  $data['langid'], $currentlang['id'], XARVAR_NOT_REQUIRED)) {return;}
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    if (isset($action)) {
        $q = new xenQuery();
        $q1 = new xenQuery();
        $q2 = new xenQuery();
        if(!xarVarFetch('categories_image',    'str',  $categories_image, '', XARVAR_NOT_REQUIRED)) {return;}
        $q->addfield('xar_image',$categories_image);
        switch ($action) {
            case 'insert_category':
            case 'update_category':
                if(!xarVarFetch('sort_order',    'str',  $sort_order, 'ASC', XARVAR_NOT_REQUIRED)) {return;}
                if(!xarVarFetch('categories_status',    'int',  $categories_status, 0, XARVAR_NOT_REQUIRED)) {return;}

                if (isset($edit_x) || isset($edit_y)) {
                    $action = 'edit_category_ACD';
                }
                else {
                    if (!isset($categories_id)) {
                        $categories_id = $data['cID'];
                    }
                    $q2->addfield('sort_order',$sort_order);
                    $q2->addfield('categories_status',$categories_status);

                    $q->addtable($xartables['categories']);
                    $q1->addtable($xartables['products_categories_description']);
                    $q2->addtable($xartables['products_categories']);
                    if ($action == 'insert_category') {
                        $q->settype('INSERT');
                        $q1->settype('INSERT');
                        $q2->settype('INSERT');
                        $q->addfield('xar_parent',$categories_id);
                        $q2->addfield('date_added',mktime());
                    }
                    elseif ($action == 'update_category') {
                        $q->settype('UPDATE');
                        $q1->settype('UPDATE');
                        $q2->settype('UPDATE');
                        $q2->addfield('last_modified',mktime());
                        $q->eq('xar_cid',$categories_id);
                    }

                    if(!xarVarFetch('categories_name',    'array',  $categories_name, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_heading_title',    'array',  $categories_heading_title, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_description',    'array',  $categories_description, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_title',    'array',  $categories_meta_title, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_description',    'array',  $categories_meta_description, NULL, XARVAR_DONT_SET)) {return;}
                    if(!xarVarFetch('categories_meta_keywords',    'array',  $categories_meta_keywords, NULL, XARVAR_DONT_SET)) {return;}

                    for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                        $language_id = $languages[$i]['id'];
                        if (isset($categories_name[$language_id])) {
                            $q1->addfield('categories_name',$categories_name[$language_id]);
                            if ($configuration['allow_category_descriptions'] == true) {
                                $q1->addfield('categories_heading_title',$categories_heading_title[$language_id]);
                                $q1->addfield('categories_description',$categories_description[$language_id]);
                                $q1->addfield('categories_meta_title',$categories_meta_title[$language_id]);
                                $q1->addfield('categories_meta_description',$categories_meta_description[$language_id]);
                                $q1->addfield('categories_meta_keywords',$categories_meta_keywords[$language_id]);
                            }
                            if ($action == 'insert_category') {
                                $q->addfield('xar_cid',$q->nextid($xartables['categories'],'xar_cid'));
                                $q->run();
                                $q1->addfield('categories_id',$q->lastid($xartables['categories'],'xar_cid'));
                                $q1->addfield('language_id',$language_id);
                                $categories_id = $q->lastid($xartables['categories'],'xar_cid');
                                $q2->addfield('categories_id',$q->nextid($xartables['categories'],'xar_cid'));
                            }
                            elseif ($action == 'update_category') {
                                $q->run();
                                $q1->eq('categories_id',$categories_id);
                                $q1->eq('language_id',$language_id);
                                $q2->eq('categories_id',$categories_id);
                            }
                            $q1->run();
                            $q2->run();
                        }

                    }

/*            $q->qecho();
            echo "<br /><br />";
            $q1->qecho();
            echo "<br /><br />";
            $q2->qecho();exit;

                    if ($categories_image = new upload('categories_image', DIR_FS_CATALOG_IMAGES)) {
                        $q = new xenQuery('SELECT','commerce_categories');
                        $q->addfield('categories_image',$categories_image->filename);
                        $q->eq('categories_id',$categories_id);
                        if(!$q->run()) return;
                    }
*/
                }
                xarResponseRedirect(xarModURL('products','admin','categories', array('cPath' => $data['cPath'], 'cID' => $categories_id)));
            }
    }

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_categories_description'],'cd');
    $q->addfield('cd.categories_name');
    $q->eq('cd.language_id',$data['langid']);
    $q->eq('cd.categories_id',$data['cID']);
    if(!$q->run()) return;

    if($q->row() == array()) {
        $q1 = new xenQuery('INSERT',$xartables['products_categories_description']);
        $q1->addfield('language_id',$data['langid']);
        $q1->addfield('categories_id',$data['cID']);
        if(!$q1->run()) return;
    }

    $q->addtable($xartables['categories'],'xc');
    $q->addtable($xartables['products_categories'],'c');
    $q->addfields(array('cd.categories_heading_title',
                        'cd.categories_description',
                        'cd.categories_meta_title',
                        'cd.categories_meta_description',
                        'cd.categories_meta_keywords',
                        'c.categories_id',
                        'c.categories_image',
                        'c.parent_id',
                        'c.sort_order',
                        'c.date_added',
                        'c.last_modified',
                        'c.categories_status',
                        ));
//TODO cInfo is apparently taking care of the table names. xarQuery can do the same an then cInfo no longer needs to be an object?
/*    $q->addfields(array('c.categories_id AS categories_id',
        'cd.categories_name AS categories_id',
        'xc.xar_image AS categories_image',
        'c.parent_id AS parent_id',
        'c.sort_order AS sort_order',
        'c.date_added AS date_added',
        'c.last_modified AS categories_id',
        'c.categories_status AS categories_status'));
*/
    $q->join('c.categories_id','cd.categories_id');
    $q->join('xc.xar_cid','c.categories_id');
//    $q->qecho();
    if(!$q->run()) return;
    $cInfo = $q->row();

    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $data['configuration'] = $configuration;

    if(!xarVarFetch('product_template',     'str',  $cInfo['product_template'],   '', XARVAR_NOT_REQUIRED)) {return;}
    $default_array=array();
    // set default value in dropdown!
    if (isset($content['content_file']) && $content['content_file'] == '') {
        $default_array[]=array('id' => 'default','text' => xarML('--Select--'));
    } else {
        $default_array[]=array('id' => 'default','text' => xarML('--No files available--'));
    }
    $data['producttemplatefiles'] = $default_array;
    $dirname = 'modules/products/xartemplates/product_listing/';
    if (isset($dirname) && $dir = opendir($dirname)){
        $files = array();
        while  (($file = readdir($dir)) !==false) {
            if (is_file('modules/products/xartemplates/product_listing/'.$file) and ($file !="index.html")){
            $files[]=array(
                        'id' => $file,
                        'text' => $file);
            }
        }
        closedir($dir);
        $data['producttemplatefiles'] = array_merge($default_array,$files);
    }

    if(!xarVarFetch('category_template',     'str',  $cInfo['category_template'],   '', XARVAR_NOT_REQUIRED)) {return;}
    $default_array=array();
    // set default value in dropdown!
    if (isset($content['content_file']) && $content['content_file'] == '') {
        $default_array[]=array('id' => 'default','text' => xarML('--Select--'));
    } else {
        $default_array[]=array('id' => 'default','text' => xarML('--No files available--'));
    }
    $data['categorytemplatefiles'] = $default_array;
    $dirname = 'modules/products/xartemplates/category_listing/';
    if (isset($dirname) && $dir = opendir($dirname)){
        $files = array();
        while  (($file = readdir($dir)) !==false) {
            if (is_file('modules/products/xartemplates/category_listing/'.$file) and ($file !="index.html")){
            $files[]=array(
                        'id' => $file,
                        'text' => $file);
            }
        }
        closedir($dir);
        $data['categorytemplatefiles'] = array_merge($default_array,$files);
    }
    $data['cInfo'] = $cInfo;

    $data['order_array'] = array(array('id' => 'p.products_price','text' => xarML('Price')),
                       array('id' => 'pd.products_name','text' => xarML('Product Name')),
                       array('id' => 'p.products_ordered','text' => xarML('Products Ordered')),
                       array('id' => 'p.products_sort','text' => xarML('Sorting')),
                       array('id' => 'p.products_weight','text' => xarML('Weight')),
                       array('id' => 'p.products_quantity','text' => xarML('On Stock')));
    $data['order_value'] = 'pd.products_name';
    $data['sort_array'] = array(array('id' => 'ASC','text' => xarML('ASC (1 first)')),
                       array('id' => 'DESC','text' => xarML('DESC (1 last)')));
    return $data;
}
?>