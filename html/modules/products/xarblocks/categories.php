<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003-4 Xaraya
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

/**
 * Initialise the block
 */
function commerce_categoriesblock_init()
{
    return array(
        'content_text' => '',
        'content_type' => 'text',
        'expire' => 0,
        'hide_empty' => true,
        'custom_format' => '',
        'hide_errors' => true,
        'start_date' => '',
        'end_date' => ''
    );
}

/**
 * Get information on the block ($blockinfo array)
 */
function commerce_categoriesblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'commerce',
        'func_update' => 'commerce_categories_update',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true,
        'notes' => "content_type can be 'text', 'html', 'php' or 'data'"
    );
}

/**
 * Display function
 * @param $blockinfo array
 * @returns $blockinfo array
 */
function commerce_categoriesblock_display($blockinfo)
{
    if (!xarModIsAvailable('categories')) return '';
    // Security Check
    if (!xarSecurityCheck('ViewCommerceBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}

    if(!xarVarFetch('cPath',  'str',  $cPath, '', XARVAR_NOT_REQUIRED)) {return;}
    include_once 'modules/xen/xarclasses/xenquery.php';
    xarModAPILoad('categories');
    $xartables = xarDBGetTables();

    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    $box_content='';

    $categories_string = '';

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['categories'],'xc');
    $q->addtable($xartables['commerce_categories'],'c');
    $q->addtable($xartables['commerce_categories_description'],'cd');
    $q->addfields(array('xc.xar_cid AS cid',
                        'cd.categories_name AS categories_name',
                        'xc.xar_parent AS parent'));
//    if ($configuration['group_check'] == true) {
//        $q->like('c.group_ids', "'%c_".$_SESSION['customers_status']['customers_status_id']."_group%'");
//    }
    $q->eq('c.categories_status', 1);
    $q->eq('xc.xar_parent', 0);
    $q->eq('cd.language_id', $currentlang['id']);
    $q->join('c.categories_id', 'xc.xar_cid');
    $q->join('c.categories_id', 'cd.categories_id');
    $q->setorder('c.sort_order');
    $q->addorder('cd.categories_name');
    if(!$q->run()) return;

    //if no active categories found don't show the block
    if ($q->output() == array()) return;

/*    $foo[0] = array('name' => '',
                    'parent' => 0,
                    'level' => 0,
                    'path' => 0,
                    'next_id' => false);
*/
    foreach ($q->output() as $categories)  {
        $foo[$categories['cid']] = array(
                                            'name' => $categories['categories_name'],
                                            'parent' => $categories['parent'],
                                            'level' => 0,
                                            'path' => $categories['cid'],
                                            'next_id' => false);

        if (isset($prev_id)) {
            $foo[$prev_id]['next_id'] = $categories['cid'];
        }

        $prev_id = $categories['cid'];

        if (!isset($first_element)) {
            $first_element = $categories['cid'];
        }
    }
    if (!isset($first_element)) $first_element = 0;

  //------------------------
    $id = 0;
    if (!empty($cPath)) {
        $new_path = '';
        $id = split('_', $cPath);
        reset($id);
        while (list($key, $value) = each($id)) {
            unset($prev_id);
            unset($first_id);
            $q = new xenQuery('SELECT');
            $q->addtable($xartables['categories'],'xc');
            $q->addtable($xartables['commerce_categories'],'c');
            $q->addtable($xartables['commerce_categories_description'],'cd');
            $q->addfields(array('xc.xar_cid AS cid',
                                'cd.categories_name AS categories_name',
                                'xc.xar_parent AS parent'));
            $q->eq('c.categories_status', 1);
            $q->eq('xc.xar_parent', $value);
            $q->eq('cd.language_id', $currentlang['id']);
            $q->join('c.categories_id', 'xc.xar_cid');
            $q->join('c.categories_id', 'cd.categories_id');
            $q->setorder('c.sort_order');
            $q->addorder('cd.categories_name');
            if(!$q->run()) return;

            if ($q->getrows() > 0) {
                $new_path .= $value;
                foreach ($q->output() as $row) {
                    $foo[$row['cid']] = array(
                                                      'name' => $row['categories_name'],
                                                      'parent' => $row['parent'],
                                                      'level' => $key+1,
                                                      'path' => $new_path . '_' . $row['cid'],
                                                      'next_id' => false);

                    if (isset($prev_id)) $foo[$prev_id]['next_id'] = $row['cid'];
                    $prev_id = $row['cid'];
                    if (!isset($first_id)) $first_id = $row['cid'];
                    $last_id = $row['cid'];
                }
                $foo[$last_id]['next_id'] = $foo[$value]['next_id'];
                $foo[$value]['next_id'] = $first_id;
                $new_path .= '_';
            } else {
                break;
            }
        }
    }
    $data['tree'] = xarModAPIFunc('commerce','user','show_category', array('counter' => $first_element, 'cid' => $id, 'foo' => $foo));
    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>