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

function commerce_admin_customers_status()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    include_once 'modules/commerce/xarclasses/object_info.php';
    include_once 'modules/commerce/xarclasses/split_page_results.php';
    $xartables = xarDBGetTables();
    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $data['configuration'] = $configuration;

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('page',   'int',  $page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cID',    'int',  $cID, NULL, XARVAR_DONT_SET)) {return;}
    $languages = xarModAPIFunc('commerce','user','get_languages');

  $data['customers_status_ot_discount_flag_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_graduated_prices_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_public_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_show_price_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_show_price_tax_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_discount_attributes_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
  $data['customers_status_add_tax_ot_array'] = array(array('id' => '0', 'text' => xarML('no')), array('id' => '1', 'text' => xarML('yes')));
    if (isset($action)) {
        switch ($action) {
            case 'insert':
            case 'save':
                for ($i=0; $i<sizeof($languages); $i++) {
                    $customers_status_name = $_POST['customers_status_name'];
                    if(!xarVarFetch('customers_status_show_price','int',$customers_status_show_price,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_show_price_tax','int',$customers_status_show_price_tax,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_public','int',$customers_status_public,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_discount','str',$customers_status_discount,'',XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_ot_discount_flag','int',$customers_status_ot_discount_flag,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_ot_discount','str',$customers_status_ot_discount,'',XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_graduated_prices','int',$customers_status_graduated_prices,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_discount_attributes','int',$customers_status_discount_attributes,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_add_tax_ot','int',$customers_status_add_tax_ot,0,XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_payment_unallowed','str',$customers_status_payment_unallowed,'',XARVAR_NOT_REQUIRED)) {return;}
                    if(!xarVarFetch('customers_status_shipping_unallowed','str',$customers_status_shipping_unallowed,'',XARVAR_NOT_REQUIRED)) {return;}

                    $language_id = $languages[$i]['id'];

                    $q = new xenQuery();
                    $q->addtable($xartables['commerce_customers_status']);
                    $q->addfield('customers_status_name',$customers_status_name[$language_id]);
                    $q->addfield('customers_status_public',$customers_status_public);
                    $q->addfield('customers_status_show_price',$customers_status_show_price);
                    $q->addfield('customers_status_show_price_tax',$customers_status_show_price_tax);
                    $q->addfield('customers_status_discount',$customers_status_discount);
                    $q->addfield('customers_status_ot_discount_flag',$customers_status_ot_discount_flag);
                    $q->addfield('customers_status_ot_discount',$customers_status_ot_discount);
                    $q->addfield('customers_status_graduated_prices',$customers_status_graduated_prices);
                    $q->addfield('customers_status_add_tax_ot',$customers_status_add_tax_ot);
                    $q->addfield('customers_status_payment_unallowed',$customers_status_payment_unallowed);
                    $q->addfield('customers_status_shipping_unallowed',$customers_status_shipping_unallowed);
                    $q->addfield('customers_status_discount_attributes',$customers_status_discount_attributes);

                    if ($action == 'insert') {
                        if (!isset($cID)) {
                            $q1 = new xenQuery('SELECT',$xartables['commerce_customers_status'],array('max(customers_status_id) as customers_status_id'));
                            $q1->run();
                            $next_id = $q1->row();
                            $cID = $next_id['customers_status_id'] + 1;

                            // We want to create a personal offer table corresponding to each customers_status
//                            $q1 = new xenQuery("create table personal_offers_by_customers_status_" . $customers_status_id . " (price_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, products_id int NOT NULL, quantity int, personal_offer decimal(15,4))");
//                    $q1->setstatement();
//                    echo "ss".$q1->getstatement();
//                    echo "ss".$cID;exit;
//                            $q1->run();
                        }
                        $q->settype('INSERT');
                        $q->addtable($xartables['commerce_customers_status']);
                        $q->addfield('language_id',$language_id);
                        $q->addfield('customers_status_id',$cID);
                        if(!$q->run()) return;
                    }
                    else {
                        $q->settype('UPDATE');
                        $q->eq('language_id',$language_id);
                        $q->eq('customers_status_id',$cID);
                        if(!$q->run()) return;
                    }
                }
//      if ($customers_status_image = new upload('customers_status_image', DIR_WS_ICONS)) {
//        new xenQuery("update " . TABLE_CUSTOMERS_STATUS . " set customers_status_image = '" . $customers_status_image->filename . "' where customers_status_id = '" . xtc_db_input($customers_status_id) . "'");
//      }
                if(!xarVarFetch('default','str',$default,'off',XARVAR_NOT_REQUIRED)) {return;}
                if ($default == 'on') {
                    $q = new xenQuery('UPDATE', $xartables['commerce_configuration']);
                    $q->addfield('configuration_value', $customers_status_id);
                    $q->eq('configuration_key','DEFAULT_CUSTOMERS_STATUS_ID');
                    if(!$q->run()) return;
                }

                xarResponseRedirect(xarModURL('commerce','admin','customers_status',array('page' => $page,'cID' => $cID)));
                break;
            case 'deleteconfirm':
                $q = new xenQuery('SELECT', $xartables['commerce_configuration'],array('configuration_value'));
                $q->eq('configuration_key',DEFAULT_CUSTOMERS_STATUS_ID);
                if(!$q->run()) return;
                $customers_status = $q->row();
                if ($customers_status['configuration_value'] == $cID) {
                    $q = new xenQuery('UPDATE', $xartables['commerce_configuration']);
                    $q->addfield('configuration_value', '');
                    $q->eq('configuration_key',DEFAULT_CUSTOMERS_STATUS_ID);
                    if(!$q->run()) return;
                }

                $q = new xenQuery('DELETE', $xartables['commerce_customers_status']);
                $q->eq('customers_status_id',$cID);
                if(!$q->run()) return;

                // We want to drop the existing corresponding personal_offers table
                $q = new xenQuery("drop table IF EXISTS personal_offers_by_customers_status_" . $cID);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('commerce','admin','customers_status',array('page' => $page)));
                break;
            case 'delete':
                $q = new xenQuery('SELECT', $xartables['commerce_customers'],array('count(*) as count'));
                $q->eq('customers_status',$cID);
                if(!$q->run()) return;

                $status = $q->row();
                $remove_status = true;

                if (($cID == $configuration['default_customers_status_id']) || ($cID == $configuration['default_customers_status_id_guest']) || ($cID == $configuration['default_customers_status_id_admin'])) {
                    $remove_status = false;
//                    $messageStack->add(ERROR_REMOVE_DEFAULT_CUSTOMERS_STATUS, 'error');
                } elseif ($status['count'] > 0) {
                    $remove_status = false;
//                    $messageStack->add(ERROR_STATUS_USED_IN_CUSTOMERS, 'error');
                } else {
//                    $q = new xenQuery("select count(*) as count from " . $xartables['commerce_customers_status_history'] . " where '" . $cID . "' in (new_value, old_value)");
//                    if(!$q->run()) return;
//                    $history = $q->row();
//                    if ($history['count'] > 0) {
                      $remove_status = false;
//                      $messageStack->add(ERROR_STATUS_USED_IN_HISTORY, 'error');
//                    }
                }
                break;
        }
    }

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    $currentlang = xarModAPIFunc('commerce','user','get_language',array('locale' => $data['language']));

    $q = new xenQuery('SELECT',$xartables['commerce_customers_status']);
    $q->eq('language_id',$currentlang['id']);
    $q->setorder('customers_status_id');
    $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
    $q->setstartat(($page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);
    if(!$q->run()) return;

    $pager = new splitPageResults($page,
                                  $q->getrows(),
                                  xarModURL('commerce','admin','customers_status'),
                                  xarModGetVar('commerce', 'itemsperpage')
                                 );
    $data['pagermsg'] = $pager->display_count('Displaying #(1) to #(2) (of #(3) customer groups)');
    $data['displaylinks'] = $pager->display_links();

    $items =$q->output();
    $limit = count($items);
    for ($i=0;$i<$limit;$i++) {
        if ((!isset($cID) || $cID == $items[$i]['customers_status_id']) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
            $cInfo = new objectInfo($items[$i]);
            $items[$i]['url'] = xarModURL('commerce','admin','customers_status',array('page' => $page,'cID' => $cInfo->customers_status_id, 'action' => 'edit'));
        }
        else {
            $items[$i]['url'] = xarModURL('commerce','admin','customers_status',array('page' => $page, 'cID' => $items[$i]['customers_status_id']));
        }
    }
    $data['items'] = $items;
    $data['cInfo'] = isset($cInfo) ? get_object_vars($cInfo) : '';
    $data['page'] = $page;
    $data['action'] = $action;

    $data['languages'] = $languages;
    return $data;
}
?>