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

function commerce_admin_customers()
{
    include_once 'modules/xen/xarclasses/xenquery.php';
    include_once 'modules/commerce/xarclasses/object_info.php';
    include_once 'modules/commerce/xarclasses/split_page_results.php';
    $xartables = xarDBGetTables();

    if(!xarVarFetch('action', 'str',  $action, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('page',   'int',  $page, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('cID',    'int',  $cID, NULL, XARVAR_DONT_SET)) {return;}
    if (isset($action)) {
        switch ($action) {
            case 'update':
                if(!xarVarFetch('customers_firstname','str',  $customers_firstname)) {return;}
                if(!xarVarFetch('customers_lastname','str',  $customers_lastname)) {return;}
                if(!xarVarFetch('customers_email_address','atr',  $customers_email_address)) {return;}
                if(!xarVarFetch('customers_telephone','str',  $customers_telephone)) {return;}
                if(!xarVarFetch('customers_fax','str',  $customers_fax)) {return;}
                if(!xarVarFetch('customers_newsletter','atr ',  $customers_newsletter)) {return;}
                if(!xarVarFetch('customers_gender','str',  $customers_gender)) {return;}
                if(!xarVarFetch('customers_dob','str',  $customers_dob)) {return;}
                if(!xarVarFetch('default_address_id','atr',  $default_address_id)) {return;}
                if(!xarVarFetch('entry_street_address','str',  $entry_street_address)) {return;}
                if(!xarVarFetch('entry_suburb','str',  $entry_suburb)) {return;}
                if(!xarVarFetch('entry_city','atr ',  $entry_city)) {return;}
                if(!xarVarFetch('entry_country_id','str',  $entry_country_id)) {return;}
                if(!xarVarFetch('entry_company','str',  $entry_company)) {return;}
                if(!xarVarFetch('entry_state','atr',  $entry_state)) {return;}
                if(!xarVarFetch('entry_zone_id','str',  $entry_zone_id)) {return;}
                if(!xarVarFetch('memo_title','str',  $memo_title)) {return;}
                if(!xarVarFetch('memo_text','atr ',  $memo_text)) {return;}

                if ($memo_text != '' && $memo_title != '' ) {
                    $q = new xenQuery('INSERT', $xartables['commerce_customers_memo']);
                    $q->addfield('customers_id',$cID);
                    $q->addfield('memo_date',date("Y-m-d"));
                    $q->addfield('memo_title',$memo_title);
                    $q->addfield('memo_text',$memo_text);
                    $q->addfield('poster_id',$_SESSION['customer_id']);
                    if(!$q->run()) return;
                }
                $error = false; // reset error flag

                if (strlen($customers_firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
                    $error = true;
                    $entry_firstname_error = true;
                } else {
                    $entry_firstname_error = false;
                }

                if (strlen($customers_lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
                    $error = true;
                    $entry_lastname_error = true;
                } else {
                    $entry_lastname_error = false;
                }

                if (ACCOUNT_DOB == 'true') {
                    if (checkdate(substr(xtc_date_raw($customers_dob), 4, 2), substr(xtc_date_raw($customers_dob), 6, 2), substr(xtc_date_raw($customers_dob), 0, 4))) {
                        $entry_date_of_birth_error = false;
                    } else {
                        $error = true;
                        $entry_date_of_birth_error = true;
                    }
                }

                if (strlen($customers_email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
                    $error = true;
                    $entry_email_address_error = true;
                } else {
                    $entry_email_address_error = false;
                }

                if (!xarModAPIFunc('commerce','user','validate_email',array('email' =>$customers_email_address))) {
                    $error = true;
                    $entry_email_address_check_error = true;
                } else {
                    $entry_email_address_check_error = false;
                }

                if (strlen($entry_street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
                    $error = true;
                    $entry_street_address_error = true;
                } else {
                    $entry_street_address_error = false;
                }

                if (strlen($entry_postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
                    $error = true;
                    $entry_post_code_error = true;
                } else {
                    $entry_post_code_error = false;
                }

                if (strlen($entry_city) < ENTRY_CITY_MIN_LENGTH) {
                    $error = true;
                    $entry_city_error = true;
                } else {
                    $entry_city_error = false;
                }

                if ($entry_country_id == false) {
                    $error = true;
                    $entry_country_error = true;
                } else {
                    $entry_country_error = false;
                }

                if (ACCOUNT_STATE == 'true') {
                    if ($entry_country_error == true) {
                        $entry_state_error = true;
                    } else {
                        $zone_id = 0;
                        $entry_state_error = false;
                        $q = new xenQuery('SELECT',$xartables['commerce_zones']);
                        $q->addfield('count(*) as total');
                        $q->eq('zone_country_id',$entry_country_id);
                        if(!$q->run()) return;
                        $check_value = $q->row();
                        $entry_state_has_zones = ($check_value['total'] > 0);
                        if ($entry_state_has_zones == true) {
                        $q = new xenQuery('SELECT',$xartables['commerce_zones']);
                        $q->addfield('zone_id');
                        $q->eq('zone_country_id',$entry_country_id);
                        $q->eq('zone_name',$entry_state);
                        if(!$q->run()) return;
                        if ($zone_query->getrows() == 1) {
                                $zone_values = $q->row();
                                $entry_zone_id = $zone_values['zone_id'];
                            } else {
                                $q = new xenQuery('SELECT',$xartables['commerce_zones']);
                                $q->addfield('zone_id');
                                $q->eq('zone_country_id',$entry_country_id);
                                $q->eq('zone_code',$entry_state);
                                if(!$q->run()) return;
                                if ($zone_query->getrows() == 1) {
                                    $zone_values = $q->row();
                                    $zone_id = $zone_values['zone_id'];
                                } else {
                                    $error = true;
                                    $entry_state_error = true;
                                }
                            }
                        } else {
                            if ($entry_state == false) {
                                $error = true;
                                $entry_state_error = true;
                            }
                        }
                    }
                }

                if (strlen($customers_telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
                    $error = true;
                    $entry_telephone_error = true;
                } else {
                    $entry_telephone_error = false;
                }

                $q = new xenQuery('SELECT',$xartables['commerce_customers']);
                $q->addfield('customers_email_address');
                $q->eq('customers_email_address',$customers_email_address);
                $q->eq('customers_id',$customers_id);
                if(!$q->run()) return;
                if ($check_email->getrows()) {
                    $error = true;
                    $entry_email_address_exists = true;
                } else {
                    $entry_email_address_exists = false;
                }

                if ($error == false) {
                    $q = new xenQuery('UPDATE',$xartables['commerce_customers']);
                    $q->addfield('customers_firstname',$customers_firstname);
                    $q->addfield('customers_lastname',$customers_lastname);
                    $q->addfield('customers_email_address',$customers_email_address);
                    $q->addfield('customers_telephone',$customers_telephone);
                    $q->addfield('customers_fax',$customers_fax);
                    $q->addfield('customers_newsletter',$customers_newsletter);

                    if (ACCOUNT_GENDER == 'true') $q->addfield('customers_gender',$customers_gender);
                    if (ACCOUNT_DOB == 'true') $q->addfield('customers_dob',xtc_date_raw($customers_dob));

                    $q->eq('customers_id',$customers_id);
                    if(!$q->run()) return;

                    $q = new xenQuery('UPDATE',$xartables['commerce_customers_info']);
                    $q->addfield('customers_info_date_account_last_modified',mktime());
                    $q->eq('customers_info_id',$customers_id);
                    if(!$q->run()) return;

                    if ($entry_zone_id > 0) $entry_state = '';

                    $q = new xenQuery('UPDATE',$xartables['commerce_address_book']);
                    $q->addfield('entry_firstname',$customers_firstname);
                    $q->addfield('entry_lastname',$customers_lastname);
                    $q->addfield('entry_street_address',$entry_street_address);
                    $q->addfield('entry_postcode',$entry_postcode);
                    $q->addfield('entry_city',$entry_city);
                    $q->addfield('entry_country_id',$entry_country_id);

                    if (ACCOUNT_COMPANY == 'true') $q->addfield('entry_company',$entry_company);
                    if (ACCOUNT_SUBURB == 'true') $q->addfield('entry_suburb',$entry_suburb);

                    if (ACCOUNT_STATE == 'true') {
                        if ($entry_zone_id > 0) {
                            $q->addfield('entry_zone_id',$entry_zone_id);
                            $q->addfield('entry_state','');
                        } else {
                            $q->addfield('entry_zone_id','0');
                            $q->addfield('entry_state',$entry_state);
                        }
                    }

                    $q->eq('customers_id',$customers_id);
                    $q->eq('address_book_id',$default_address_id);
                    if(!$q->run()) return;

                    xarResponseRedirect(xarModURL('commerce','admin','customers',array('page' => $page,'cID' => $cID)));
                } elseif ($error == true) {
                    $cInfo = new objectInfo($_POST);
                    $processed = true;
                }

                break;
            case 'insert':
                if(!xarVarFetch('tax_zone_id','int',  $tax_zone_id)) {return;}
                if(!xarVarFetch('tax_class_id','int',  $tax_class_id)) {return;}
                if(!xarVarFetch('tax_rate','float',  $tax_rate)) {return;}
                if(!xarVarFetch('tax_description','str',  $tax_description)) {return;}
                if(!xarVarFetch('tax_priority','int',  $tax_priority)) {return;}
                if(!xarVarFetch('date_added','int ',  $date_added)) {return;}
                $q = new xenQuery('INSERT', $xartables['commerce_tax_rates']);
                $q->addfield('tax_zone_id',$tax_zone_id);
                $q->addfield('tax_class_id',$tax_class_id);
                $q->addfield('tax_rate',$tax_rate);
                $q->addfield('tax_description',$tax_description);
                $q->addfield('tax_priority',$tax_priority);
                $q->addfield('date_added',mktime());
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('commerce','admin','tax_rates'));
                break;
            case 'save':
                if(!xarVarFetch('tax_zone_id','int',  $tax_zone_id)) {return;}
                if(!xarVarFetch('tax_class_id','int',  $tax_class_id)) {return;}
                if(!xarVarFetch('tax_rate','float',  $tax_rate)) {return;}
                if(!xarVarFetch('tax_description','str',  $tax_description)) {return;}
                if(!xarVarFetch('tax_priority','int',  $tax_priority)) {return;}
                if(!xarVarFetch('last_modified','int ',  $last_modified)) {return;}
                $q = new xenQuery('UPDATE', $xartables['commerce_tax_rates']);
                $q->addfield('tax_zone_id',$tax_zone_id);
                $q->addfield('tax_class_id',$tax_class_id);
                $q->addfield('tax_rate',$tax_rate);
                $q->addfield('tax_description',$tax_description);
                $q->addfield('tax_priority',$tax_priority);
                $q->addfield('last_modified',mktime());
                $q->eq('tax_rates_id',$cID);
                if(!$q->run()) return;
                xarResponseRedirect(xarModURL('commerce','admin','tax_rates',array('page' => $page,'cID' => $cID)));

            case 'deleteconfirm':
                if(!xarVarFetch('delete_reviews', 'str',  $delete_reviews, NULL, XARVAR_NOT_REQUIRED)) {return;}
                if($delete_reviews == 'on') {
                    $q = new xenQuery('SELECT',$xartables['commerce_reviews'],array('reviews_id'));
                    $q->eq('customers_id',$customers_id);
                    if(!$q->run()) return;
                    while ($reviews = $q->output()) {
                        $q = new xenQuery('DELETE',$xartables['commerce_reviews_description']);
                        $q->eq('reviews_id',$reviews['reviews_id']);
                        if(!$q->run()) return;
                    }
                    $q = new xenQuery('DELETE',$xartables['commerce_reviews']);
                    $q->eq('customers_id',$customers_id);
                    if(!$q->run()) return;
                }
                else {
                    $q = new xenQuery('UPDATE',$xartables['commerce_reviews']);
                    $q->addfield('customers_id','');
                    $q->eq('customers_id',$cID);
                    if(!$q->run()) return;
                }

                $q = new xenQuery('DELETE',$xartables['commerce_address_book']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers_info']);
                $q->eq('customers_info_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers_basket']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers_basket_attributes']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_whos_online']);
                $q->eq('customer_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers_status_history']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;
                $q = new xenQuery('DELETE',$xartables['commerce_customers_ip']);
                $q->eq('customers_id',$cID);
                if(!$q->run()) return;

                xarResponseRedirect(xarModURL('commerce','admin','customers',array('page' => $page)));
                break;
        }
    }
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];

    $q = new xenQuery('SELECT');

    $q->addtable($xartables['commerce_customers'],'c');
    $q->addtable($xartables['commerce_address_book'],'a');
    $q->addtable($xartables['commerce_customers_info'],'ci');
    $q->addtable($xartables['commerce_countries'],'co');

    $q->addfields(array('c.account_type','c.customers_id', 'c.customers_lastname', 'c.customers_firstname', 'c.customers_email_address'));
    $q->addfields(array('a.entry_country_id', 'c.customers_status', 'c.member_flag'));
    $q->addfields(array('ci.customers_info_date_account_created as date_account_created', 'ci.customers_info_date_account_last_modified as date_account_last_modified', 'ci.customers_info_date_of_last_logon as date_last_logon', 'ci.customers_info_number_of_logons as number_of_logons'));
    $q->addfields(array('co.countries_name'));

    $q->join('c.customers_id','a.customers_id');
    $q->join('c.customers_default_address_id','a.address_book_id');
    $q->join('ci.customers_info_id','c.customers_id');
    $q->join('co.countries_id','a.entry_country_id');

    $q->setorder('c.customers_lastname');
    $q->addorder('c.customers_firstname');

    $q->setrowstodo(xarModGetVar('commerce', 'itemsperpage'));
    $q->setstartat(($page - 1) * xarModGetVar('commerce', 'itemsperpage') + 1);

    if(!xarVarFetch('status',   'int',  $status, 99, XARVAR_NOT_REQUIRED)) {return;}
    if ($status != 99 || $status == 0) {
        $q->eq('c.customers_status',$status);
    }

    if(!xarVarFetch('search',   'str',  $search, '', XARVAR_NOT_REQUIRED)) {return;}
    if ($search != '') {
        $q->like('c.customers_lastname','%" . $search . "%');
        $q->like('c.customers_firstname','%" . $search . "%');
        $q->like('c.customers_email_address','%" . $search . "%');
    }

//    $q->qecho();exit;
    if(!$q->run()) return;


    $pager = new splitPageResults($page,
                                  $q->getrows(),
                                  xarModURL('commerce','admin','customers'),
                                  xarModGetVar('commerce', 'itemsperpage')
                                 );
    $data['pagermsg'] = $pager->display_count('Displaying #(1) to #(2) (of #(3) customers)');
    $data['displaylinks'] = $pager->display_links();

    $items =$q->output();
    $limit = count($items);
    for ($i=0;$i<$limit;$i++) {
        $q = new xenQuery('SELECT',$xartables['commerce_reviews'],array('count(*) as number_of_reviews'));
        $q->eq('customers_id',$items[$i]['customers_id']);
        if(!$q->run()) return;
        $row = $q->row();
        $items[$i]['number_of_reviews'] = $row['number_of_reviews'];

        if ((!isset($cID) || $cID == $items[$i]['customers_id']) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
           $cInfo = new objectInfo($items[$i]);
            $items[$i]['url'] = xarModURL('commerce','admin','customers',array('page' => $page,'cID' => $cInfo->customers_id, 'action' => 'edit'));
        }
        else {
            $items[$i]['url'] = xarModURL('commerce','admin','customers',array('page' => $page, 'cID' => $items[$i]['customers_id']));
        }
    }
    $data['items'] = $items;
    $data['cInfo'] = isset($cInfo) ? get_object_vars($cInfo) : '';
    $data['page'] = $page;
    $data['action'] = $action;

    $select_data=array();
    $select_data=array(array('id' => '99', 'text' => xarML('-Select-')),array('id' => '100', 'text' => xarML('All Groups')));
    $customers_statuses_array = xarModAPIFunc('commerce','user','get_customers_statuses');
    $data['select_data'] = array_merge($select_data,$customers_statuses_array);
    return $data;
}
?>