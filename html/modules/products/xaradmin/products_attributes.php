<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function products_admin_products_attributes()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    include_once 'modules/commerce/xarclasses/object_info.php';
    include_once 'modules/commerce/xarclasses/split_page_results.php';
    $xartables = xarDBGetTables();

    $languages = xarModAPIFunc('commerce','user','get_languages');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
//    if(!xarVarFetch('page',   'int',  $page, 1, XARVAR_NOT_REQUIRED)) {return;}
//    if(!xarVarFetch('cID',    'int',  $cID, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('option_id',   'int',  $option_id, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('value_id',   'int',  $value_id, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('option_page',   'int',  $option_page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('value_page',   'int',  $value_page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('attribute_page',   'int',  $attribute_page, 1, XARVAR_NOT_REQUIRED)) {return;}
//                if(!xarVarFetch('option_name','array',$data['option_name'])) {return;}
//                echo var_dump($_POST['option_name']);exit;
//                echo var_dump($data['option_name']);exit;

    if (isset($action)) {
        switch ($action) {
            case 'add_product_options':
                if(!xarVarFetch('products_options_id','id',$products_options_id)) {return;}
                if(!xarVarFetch('option_name','array',$option_name)) {return;}
                for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
                    $q = new xenQuery('INSERT',$xartables['products_products_options']);
                    $q->addfield('products_options_id',$products_options_id);
                    $q->addfield('products_options_name',$option_name[$i+1]);
                    $q->addfield('language_id',$languages[$i]['id']);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'add_product_option_values':
                if(!xarVarFetch('value_name','array',$value_name)) {return;}
                for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
                    $q = new xenQuery('INSERT',$xartables['products_products_options_values']);
                    $q->addfield('products_options_values_id',$value_id);
                    $q->addfield('products_options_values_name',$value_name[$languages[$i]['id']]);
                    $q->addfield('language_id',$languages[$i]['id']);
                    if(!$q->run()) return;
                }
                $q = new xenQuery('INSERT',$xartables['products_products_options_values_to_products_options']);
                $q->addfield('products_options_values_id',$value_id);
                $q->addfield('products_options_id',$option_id);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'add_product_attributes':
                $q = new xenQuery('INSERT',$xartables['products_products_attributes']);
                $q->addfield('products_id',$products_id);
                $q->addfield('options_id',$option_id);
                $q->addfield('values_id',$values_id);
                $q->addfield('value_price',$value_price);
                $q->addfield('price_prefix',$price_prefix);
        $products_attributes_id = xtc_db_insert_id();
                if(!$q->run()) return;
                if(!xarVarFetch('products_attributes_filename','str',$products_attributes_filename)) {return;}
                if (($configuration['download_enabled'] == 'true') && $products_attributes_filename != '') {
                    $q = new xenQuery('INSERT',$xartables['products_products_attributes_download']);
                    $q->addfield('products_attributes_id',$products_attributes_id);
                    $q->addfield('products_attributes_filename',$products_attributes_filename);
                    $q->addfield('products_attributes_maxdays',$products_attributes_maxdays);
                    $q->addfield('products_attributes_maxcount',$products_attributes_maxcount);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'update_option':
                $q = new xenQuery('SELECT');
                $q->addtable($xartables['products_products_options'],'o');
                $q->addtable($xartables['commerce_languages'],'l');
                $q->addfields(array('o.language_id','l.code', 'o.products_options_name'));
                $q->eq('products_options_id',$option_id);
                $q->join('l.languages_id','o.language_id');
                if(!$q->run()) return;
                $data['options_name'] = $q->output();
                break;
            case 'update_option_value':
                $q = new xenQuery('SELECT');
                $q->addtable($xartables['products_products_options_values'],'ov');
                $q->addtable($xartables['commerce_languages'],'l');
                $q->addfields(array('ov.language_id','l.code', 'ov.products_options_values_name'));
                $q->eq('ov.products_options_values_id',$value_id);
                $q->join('l.languages_id','ov.language_id');
                if(!$q->run()) return;
                $data['options_value_name'] = $q->output();
                break;
            case 'update_option_name':
                if(!xarVarFetch('option_name','array',$option_name)) {return;}
                for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
                    $q = new xenQuery('UPDATE',$xartables['products_products_options']);
                    $q->addfield('products_options_name',$option_name[$languages[$i]['id']]);
                    $q->eq('products_options_id',$option_id);
                    $q->eq('language_id',$languages[$i]['id']);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'update_value':
                if(!xarVarFetch('value_name','array',$value_name)) {return;}
                for ($i = 0, $n = sizeof($languages); $i < $n; $i ++) {
                    $q = new xenQuery('UPDATE',$xartables['products_products_options_values']);
                    $q->addfield('products_options_values_name',$value_name[$languages[$i]['id']]);
                    $q->eq('language_id',$languages[$i]['id']);
                    $q->eq('products_options_values_id',$value_id);
                    if(!$q->run()) return;
                }
                $q = new xenQuery('UPDATE',$xartables['products_products_options_values_to_products_options']);
                $q->addfield('products_options_id',$option_id);
                $q->eq('products_options_values_id',$value_id);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'update_product_attribute':
                if(!xarVarFetch('attribute_id','str',$attribute_id)) {return;}
                $q = new xenQuery('UPDATE',$xartables['products_products_attributes']);
                $q->addfield('products_id',$products_id);
                $q->addfield('options_id',$option_id);
                $q->addfield('options_values_id',$values_id);
                $q->addfield('options_values_price',$value_price);
                $q->addfield('price_prefix',$price_prefix);
                $q->eq('products_attributes_id',$attribute_id);
                if (($configuration['download_enabled'] == 'true') && $products_attributes_filename != '') {
                    $q = new xenQuery('UPDATE',$xartables['products_products_attributes_download']);
                    $q->addfield('products_attributes_filename',$products_attributes_filename);
                    $q->addfield('products_attributes_maxdays',$products_attributes_maxdays);
                    $q->addfield('products_attributes_maxcount',$products_attributes_maxcount);
                    $q->eq('products_attributes_id',$attribute_id);
                    if(!$q->run()) return;
                }
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'delete_option':
                $q = new xenQuery('DELETE', $xartables['products_products_options']);
                $q->eq('products_options_id',$option_id);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'delete_value':
                $q = new xenQuery('DELETE', $xartables['products_products_options_values']);
                $q->eq('products_options_values_id',$value_id);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE', $xartables['products_products_options_values_to_products_options']);
                $q->eq('products_options_values_id',$value_id);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'delete_attribute':
                $q = new xenQuery('DELETE', $xartables['products_products_attributes']);
                $q->eq('products_attributes_id',$attribute_id);
                if(!$q->run()) return;
// Added for DOWNLOAD_ENABLED. Always try to remove attributes, even if downloads are no longer enabled
                $q = new xenQuery('DELETE', $xartables['products_products_attributes_download']);
                $q->eq('products_attributes_id',$attribute_id);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('products','admin','products_attributes', array('option_page' => $option_page, 'value_page' => $value_page, 'attribute_page' => $attribute_page)));
                break;
            case 'delete_product_option':
                $q = new xenQuery('SELECT',$xartables['products_products_options']);
                $q->addfields(array('products_options_id', 'products_options_name'));
                $q->eq('products_options_id', $option_id);
                $q->eq('language_id', $currentlang['id']);
                $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
                $q->setstartat(($option_page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);
                if(!$q->run()) return;
                $data['options_values'] = $q->row();
                break;
            case 'delete_option_value':
                $q = new xenQuery('SELECT',$xartables['products_products_options_values']);
                $q->addfields(array('products_options_values_id', 'products_options_values_name'));
                $q->eq('products_options_values_id', $value_id);
                $q->eq('language_id', $currentlang['id']);
                if(!$q->run()) return;
                $data['delete_value'] = $q->row();
                break;
        }
    }

//    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
//    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_products'],'p');
    $q->addtable($xartables['products_products_options_values'],'pov');
    $q->addtable($xartables['products_products_attributes'],'pa');
    $q->addtable($xartables['products_products_description'],'pd');
    $q->addfields(array('p.products_id', 'pd.products_name', 'pov.products_options_values_name'));
    $q->join('pd.products_id','p.products_id');
    $q->join('pa.products_id','p.products_id');
    $q->join('pov.products_options_values_id','pa.options_values_id');
    $q->eq('pov.language_id',$currentlang);
    $q->eq('pd.language_id',$currentlang);
//    $q->eq('pa.options_id',$option_id);
    $q->setorder('pd.products_name');
//    if(!$q->run()) return;
    $data['products'] = $q->output();

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_products'],'p');
    $q->addtable($xartables['products_products_options'],'po');
    $q->addtable($xartables['products_products_attributes'],'pa');
    $q->addtable($xartables['products_products_description'],'pd');
    $q->addfields(array('p.products_id', 'pd.products_name', 'po.products_options_name'));
    $q->join('pd.products_id','p.products_id');
    $q->join('pa.products_id','p.products_id');
    $q->join('po.products_options_id','pa.options_id');
    $q->eq('pd.language_id',$currentlang['id']);
    $q->eq('po.language_id',$currentlang['id']);
//    $q->eq('pa.options_values_id',$value_id);
    $q->setorder('pd.products_name');
//    if(!$q->run()) return;
    $data['products_values'] = $q->output();

    if(!xarVarFetch('option_order_by', 'str',  $option_order_by, 'products_options_id', XARVAR_DONT_SET)) {return;}
    $q = new xenQuery('SELECT',$xartables['products_products_options']);
    $q->eq('language_id',$currentlang['id']);
    $q->setorder($option_order_by);
    $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
    $q->setstartat(($option_page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);
    if(!$q->run()) return;
    $data['option_values'] = $q->output;

    $selection['startnum'] = '%%';
    $data['option_pager'] = xarTplGetPager($q->getstartat(),
                            $q->getrows(),
                            xarModURL('ledger', 'user', 'arcustomerlist',$selection),
                            $q->getrowstodo());

    $q = new xenQuery('SELECT',$xartables['products_products_options']);
    $q->addfield('max(products_options_id) AS next_id');
    if(!$q->run()) return;
    $max_options_id_values = $q->row();
    $data['next_id'] = isset($max_options_id_values['next_id']) ? $max_options_id_values['next_id'] + 1: 1;

    $q = new xenQuery('SELECT',$xartables['products_products_options_values']);
    $q->addfield('max(products_options_values_id) AS next_id');
    if(!$q->run()) return;
    $max_options_id_values = $q->row();
    $data['next_value_id'] = isset($max_options_id_values['next_id']) ? $max_options_id_values['next_id'] + 1: 1;

    if(!xarVarFetch('option_order_by', 'str',  $option_order_by, 'products_options_id', XARVAR_DONT_SET)) {return;}

    $q = new xenQuery('SELECT',$xartables['products_products_options_values']);
    $q->addfields(array('products_options_values_id', 'products_options_values_name'));
    $q->eq('language_id',$currentlang['id']);
    $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
    $q->setstartat(($option_page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);
//            $q->setstatement();
//            echo $q->getstatement();exit;
    if(!$q->run()) return;
    $data['values_values'] = $q->output;
//    echo var_dump($data['values_values']);exit;

    $q = new xenQuery('SELECT',$xartables['products_products_options']);
    $q->addfields(array('products_options_id','products_options_name'));
    $q->eq('language_id',$currentlang['id']);
    $q->setorder('products_options_name');
    if(!$q->run()) return;
    $data['option_list'] = $q->output();

    $q = new xenQuery('SELECT',$xartables['products_products_options'],'products_options_name');
    $q->eq('products_options_name',$option_id);
    $q->eq('language_id',$currentlang['id']);
    if(!$q->run()) return;
    $option = $q->row();
    if($option == array()) $data['option_name'] = '';
    else $data['option_name'] = $option['products_options_name'];

    $q = new xenQuery('SELECT');
    $q->addtable($xartables['products_products'],'p');
    $q->addtable($xartables['products_products_options'],'po');
    $q->addtable($xartables['products_products_attributes'],'pa');
    $q->addtable($xartables['products_products_description'],'pd');
    $q->addfields(array('p.products_id', 'pd.products_name', 'po.products_options_name'));
    $q->join('pd.products_id','p.products_id');
    $q->join('pa.products_id','p.products_id');
    $q->join('po.products_options_id','pa.options_id');
    $q->eq('pd.language_id',$currentlang['id']);
    $q->eq('po.language_id',$currentlang['id']);
    $q->setorder('pd.products_name');
    if(!$q->run()) return;

    $data['action'] = $action;
    $data['option_page'] = $option_page;
    $data['value_page'] = $value_page;
    $data['attribute_page'] = $attribute_page;
    $data['option_id'] = $option_id;
    $data['value_id'] = $value_id;
    $data['languages'] = $languages;
//    echo var_dump($data['languages']);exit;
    return $data;
}
?>