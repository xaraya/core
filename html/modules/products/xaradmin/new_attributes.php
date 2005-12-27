<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//   Third Party contributions:
//   New Attribute Manager v4b                Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com
//   copy attributes                          Autor: Hubi | http://www.netz-designer.de
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function products_admin_new_attributes()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    if(!xarVarFetch('action', 'str',  $action, '', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('copy_product_id',  'int',  $copy_product_id, 0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pID',    'int',  $pID, NULL, XARVAR_DONT_SET)) {return;}

    require('modules/products/xarincludes/new_attributes_config.php');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));
//    $language_id = $currentlang['id'];
    $language = $currentlang['code'];

    $adminImages = $language ."/admin/images/buttons/";
    $backLink = "<a href=\"javascript:history.back()\">";

  if ( isset($pID) && $action == 'change') {
    include('modules/products/xarincludes/new_attributes_change.php');

//    xtc_redirect( './' . FILENAME_CATEGORIES . '?cPath=' . $cPathID . '&pID=' . $_POST['current_product_id'] );
  }


    switch($action) {
        case 'edit':
            if ($copy_product_id != 0) {
                $q = new xenQuery('SELECT',$xartables['products_product_attributes']);
                $q->addfields('products_id', 'options_id', 'options_values_id', 'options_values_price', 'price_prefix', 'attributes_model', 'attributes_stock', 'options_values_weight', 'weight_prefix');
                $q->eq('copy_product_id', $copy_product_id);
                if(!$q->run()) return;
                foreach ($q->output() as $attrib_res) {
                    $q = new xenQuery('INSERT',$xartables['products_product_attributes']);
                    $q->addfield('products_id', $pID);
                    $q->addfield('options_id', $attrib_res['options_id']);
                    $q->addfield('options_values_id', $attrib_res['options_values_id']);
                    $q->addfield('options_values_price', $attrib_res['options_values_price']);
                    $q->addfield('price_prefix', $attrib_res['price_prefix']);
                    $q->addfield('attributes_model', $attrib_res['attributes_model']);
                    $q->addfield('attributes_stock', $attrib_res['attributes_stock']);
                    $q->addfield('options_values_weight', $attrib_res['options_values_weight']);
                    $q->addfield('weight_prefix', $attrib_res['weight_prefix']);
                    if(!$q->run()) return;
                }
            }
            $pageTitle = 'Edit Attributes -> ' . xarModAPIFunc('products','user','findtitle', array('pID' => $pID, 'language_id' => $language_id));
            include('modules/products/xarincludes/new_attributes_include.php');
            break;

        case 'change':
            $pageTitle = 'Product Attributes Updated.';
            include('modules/products/xarincludes/new_attributes_change.php');
            include('modules/products/xarincludes/new_attributes_select.php');
            break;

        default:
            $pageTitle = 'Edit Attributes';
            include('modules/products/xarincludes/new_attributes_select.php');
            break;
    }
}
?>