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

function commerce_admin_configuration()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    $table =& xarDBGetTables();

    if(!xarVarFetch('action',   'str',  $action, "", XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('gID',      'int',  $gID,    1,  XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('configuration_value', 'str',   $configuration_value,  '',    XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_STORE)) {return;}

    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];

    if(isset($action)) {
        switch ($action) {
          case 'save':
            if(!xarVarFetch('cID', 'int',  $cID, NULL,  XARVAR_DONT_SET)) {return;}
            $q = new xenQuery('UPDATE', $table['commerce_configuration']);
            $tablefields = array(
                array('name' => 'configuration_value','value' => $configuration_value),
                array('name' => 'last_modified','value' => mktime()),
            );
            $q->addfields($tablefields);
            $q->eq('configuration_id',$cID);
            if (!$q->run()) return;
        }
    }

    $q = new xenQuery('SELECT',
                      $table['commerce_configuration_group'],
                      'configuration_group_title');
    $q->eq('configuration_group_id',$gID);
    $cfg_group = $q->run();
    if (!$cfg_group) return;
    $data['cfg_group'] = $q->row();

    $q = new xenQuery('SELECT', $table['commerce_configuration']);
    $tablefields = array(
        'configuration_key',
        'configuration_value',
        'configuration_id',
        'use_function'
    );
    $q->addfields($tablefields);
    $q->eq('configuration_group_id',$gID);
    $q->addorders('sort_order');
    if (!$q->run()) return;

    $configuration = $q->row();
    if(!xarVarFetch('cID',      'int',  $cID,    $configuration['configuration_id'])) {return;}

    $configurations = $q->output();
    foreach ($configurations as $configuration) {
        if ($gID == 6) {
          switch ($configuration['configuration_key']) {
            case 'MODULE_PAYMENT_INSTALLED':
              if ($configuration['configuration_value'] != '') {
                $payment_installed = explode(';', $configuration['configuration_value']);
//                for ($i = 0, $n = sizeof($payment_installed); $i < $n; $i++) {
//                  include(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/payment/' . $payment_installed[$i]);
//                }
              }
              break;

            case 'MODULE_SHIPPING_INSTALLED':
              if ($configuration['configuration_value'] != '') {
                $shipping_installed = explode(';', $configuration['configuration_value']);
//                for ($i = 0, $n = sizeof($shipping_installed); $i < $n; $i++) {
//                  include(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/shipping/' . $shipping_installed[$i]);
//                }
              }
              break;

            case 'MODULE_ORDER_TOTAL_INSTALLED':
              if ($configuration['configuration_value'] != '') {
                $ot_installed = explode(';', $configuration['configuration_value']);
//                for ($i = 0, $n = sizeof($ot_installed); $i < $n; $i++) {
//                  include(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/order_total/' . $ot_installed[$i]);
//                }
              }
              break;
          }
        }
        if (!empty($configuration['use_function'])) {
            $use_function = $configuration['use_function'];
//            include_once 'modules/commerce/includes/general.php';
            if (ereg('->', $use_function)) {
                $class_method = explode('->', $use_function);
                if (!is_object(${$class_method[0]})) {
                  include('modules/commerce/xarclasses/' . $class_method[0] . '.php');
                  ${$class_method[0]} = new $class_method[0]();
                }
                $cfgValue = xarModAPIFunc('commerce','admin','call_function',array(
                                            'function' => $class_method[1],
                                            'parameter' => $configuration['configuration_value'],
                                            'object' => ${$class_method[0]})
                                        );
            } else {
               $cfgValue = xarModAPIFunc('commerce','admin','call_function',array(
                                            'function' => $use_function,
                                            'parameter' => $configuration['configuration_value'])
                                        );
            }
        } else {
            $cfgValue = $configuration['configuration_value'];
        }

        if (($cID == $configuration['configuration_id']) && (!isset($cInfo)) && (substr($action, 0, 3) != 'new')) {
            $q = new xenQuery('SELECT', $table['commerce_configuration']);
            $tablefields = array(
                'configuration_key',
                'date_added',
                'last_modified',
                'use_function',
                'set_function'
            );
            $q->addfields($tablefields);
            $q->eq('configuration_id',$configuration['configuration_id']);
            if (!$q->run()) return;
            $cfg_extra = $q->row();

            $cInfo = array_merge($configuration, $cfg_extra);
            include 'modules/commerce/xarclasses/object_info.php';
//            include_once 'modules/commerce/inc/xtc_db_prepare_input.inc.php';
            $data['cInfo'] = $cInfo;
        }

        $data['configurations'][] = $configuration;
      }

    $data['messageStack'] = array();
    $data['gID'] = $gID;
    $data['action'] = $action;
    $data['thisurl'] = xarModURL('commerce','admin','configuration',array('gID'=>$gID));
    xarTplSetPageTitle('Configuration Administration');
    return $data;
  }
?>