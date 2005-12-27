<?php
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2003 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Modified by: Nuncanada
// Modified by: marcinmilan
// Purpose of file:  Initialisation functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

include_once 'modules/xen/xarclasses/xenquery.php';
//Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * initialise the products module
 */
function products_init()
{
    $q = new xenQuery();
    $prefix = xarDBGetSiteTablePrefix();

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_categories";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_categories (
      categories_id int NOT NULL auto_increment,
      categories_image varchar(64),
      parent_id int DEFAULT '0' NOT NULL,
      categories_status TINYint (1)  UNSIGNED DEFAULT '1' NOT NULL,
      categories_template varchar(64),
      group_ids TEXT,
      listing_template varchar(64),
      sort_order int(3),
      products_sorting varchar(32),
      products_sorting2 varchar(32),
      date_added datetime,
      last_modified datetime,
      PRIMARY KEY (categories_id),
      KEY idx_categories_parent_id (parent_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_categories_description";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_categories_description (
      categories_id int DEFAULT '0' NOT NULL,
      language_id int DEFAULT '1' NOT NULL,
      categories_name varchar(32) NOT NULL,
      categories_heading_title varchar(255) NOT NULL,
      categories_description varchar(255) NOT NULL,
      categories_meta_title varchar(100) NOT NULL,
      categories_meta_description varchar(255) NOT NULL,
      categories_meta_keywords varchar(255) NOT NULL,
      PRIMARY KEY (categories_id, language_id),
      KEY idx_categories_name (categories_name)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_configuration";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_configuration (
      configuration_id int NOT NULL auto_increment,
      configuration_key varchar(64) NOT NULL,
      configuration_value varchar(255) NOT NULL,
      configuration_group_id int NOT NULL,
      sort_order int(5) NULL,
      last_modified datetime NULL,
      date_added datetime NOT NULL,
      use_function varchar(255) NULL,
      set_function varchar(255) NULL,
      PRIMARY KEY (configuration_id),
      KEY idx_configuration_group_id (configuration_group_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_configuration_group";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_configuration_group (
      configuration_group_id int NOT NULL auto_increment,
      configuration_group_title varchar(64) NOT NULL,
      configuration_group_description varchar(255) NOT NULL,
      sort_order int(5) NULL,
      visible int(1) DEFAULT '1' NULL,
      PRIMARY KEY (configuration_group_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products (
      products_id int NOT NULL auto_increment,
      products_quantity int(4) NOT NULL,
      products_shippingtime int(4) NOT NULL,
      products_model varchar(12),
      group_ids TEXT,
      products_sort int(4),
      products_image varchar(64),
      products_price decimal(15,4) NOT NULL,
      products_discount_allowed decimal(3,2) DEFAULT '0' NOT NULL,
      products_date_added datetime NOT NULL,
      products_last_modified datetime,
      products_date_available datetime,
      products_weight decimal(5,2) NOT NULL,
      products_status tinyint(1) NOT NULL,
      products_tax_class_id int NOT NULL,
      product_template varchar (64),
      options_template varchar (64),
      manufacturers_id int NULL,
      products_ordered int NOT NULL default '0',
      products_fsk18 int(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (products_id),
      KEY idx_products_date_added (products_date_added)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_attributes";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_attributes (
      products_attributes_id int NOT NULL auto_increment,
      products_id int NOT NULL,
      options_id int NOT NULL,
      options_values_id int NOT NULL,
      options_values_price decimal(15,4) NOT NULL,
      price_prefix char(1) NOT NULL,
      attributes_model varchar(12) NULL,
      attributes_stock int(4) NULL,
      options_values_weight decimal(15,4) NOT NULL,
      weight_prefix char(1) NOT NULL,
      PRIMARY KEY (products_attributes_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_attributes_download";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_attributes_download (
      products_attributes_id int NOT NULL,
      products_attributes_filename varchar(255) NOT NULL default '',
      products_attributes_maxdays int(2) default '0',
      products_attributes_maxcount int(2) default '0',
      PRIMARY KEY  (products_attributes_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_description";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_description (
      products_id int NOT NULL auto_increment,
      language_id int NOT NULL default '1',
      products_name varchar(64) NOT NULL default '',
      products_description text,
      products_short_description text,
      products_meta_title text NOT NULL,
      products_meta_description text NOT NULL,
      products_meta_keywords text NOT NULL,
      products_url varchar(255) default NULL,
      products_viewed int(5) default '0',
      PRIMARY KEY  (products_id,language_id),
      KEY products_name (products_name)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_notifications";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_notifications (
      products_id int NOT NULL,
      customers_id int NOT NULL,
      date_added datetime NOT NULL,
      PRIMARY KEY (products_id, customers_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_options";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_options (
      products_options_id int NOT NULL default '0',
      language_id int NOT NULL default '1',
      products_options_name varchar(32) NOT NULL default '',
      PRIMARY KEY  (products_options_id,language_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_options_values";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_options_values (
      products_options_values_id int NOT NULL default '0',
      language_id int NOT NULL default '1',
      products_options_values_name varchar(64) NOT NULL default '',
      PRIMARY KEY  (products_options_values_id,language_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_options_values_to_products_options";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_options_values_to_products_options (
      products_options_values_to_products_options_id int NOT NULL auto_increment,
      products_options_id int NOT NULL,
      products_options_values_id int NOT NULL,
      PRIMARY KEY (products_options_values_to_products_options_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_graduated_prices";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_graduated_prices (
      products_id int(11) NOT NULL default '0',
      quantity int(11) NOT NULL default '0',
      unitprice decimal(15,4) NOT NULL default '0.0000',
      KEY products_id (products_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_products_to_categories";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_products_to_categories (
      products_id int NOT NULL,
      categories_id int NOT NULL,
      PRIMARY KEY (products_id,categories_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_specials";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_specials (
      specials_id int NOT NULL auto_increment,
      products_id int NOT NULL,
      specials_new_products_price decimal(15,4) NOT NULL,
      specials_date_added datetime,
      specials_last_modified datetime,
      expires_date datetime,
      date_status_change datetime,
      status int(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (specials_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_products_content_manager";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_products_content_manager (
      content_id int(11) NOT NULL auto_increment,
      categories_id int(11) NOT NULL default '0',
      parent_id int(11) NOT NULL default '0',
      languages_id int(11) NOT NULL default '0',
      content_title varchar(32) NOT NULL default '',
      content_heading varchar(32) NOT NULL default '',
      content_text text NOT NULL,
      file_flag int(1) NOT NULL default '0',
      content_file varchar(64) NOT NULL default '',
      content_status int(1) NOT NULL default '0',
      content_group int(11) NOT NULL,
      content_delete int(1) NOT NULL default '1',
      PRIMARY KEY  (content_id)
    )";
    if (!$q->run($query)) return;

    # data

    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (1,0,0,1,'Shipping & Returns','Shipping & Returns','Put here your Shipping & Returns information.',1,'',1,1,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (2,0,0,1,'Privacy Notice','Privacy Notice','Put here your Privacy Notice information.',1,'',1,2,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (3,0,0,1,'Conditions of Use','Conditions of Use','Conditions of Use<br />Put here your Conditions of Use information. <br />1. Validity<br />2. Offers<br />3. Price<br />4. Dispatch and passage of the risk<br />5. Delivery<br />6. Terms of payment<br />7. Retention of title<br />8. Notices of defect, guarantee and compensation<br />9. Fair trading cancelling / non-acceptance<br />10. Place of delivery and area of jurisdiction<br />11. Final clauses',1,'',1,3,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (4,0,0,1,'Contact','Contact','Put here your Contact information.',1,'',1,4,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (5,0,0,1,'Index','Welcome','{\$greeting}<br><br> Dies ist die Standardinstallation des osCommerce Forking Projektes - XT-Commerce. Alle dargestellten Produkte dienen zur Demonstration der Funktionsweise. Wenn Sie Produkte bestellen, so werden diese weder ausgeliefert, noch in Rechnung gestellt. Alle Informationen zu den verschiedenen Produkten sind erfunden und daher kann kein Anspruch daraus abgeleitet werden.<br><br>Sollten Sie daran interessiert sein das Programm, welches die Grundlage für diesen Shop bildet, einzusetzen, so besuchen Sie bitte die Supportseite von XT-Commerce. Dieser Shop basiert auf der XT-Commerce Version Beta2.<br><br>Der hier dargestellte Text kann in der folgenden Datei einer jeden Sprache geändert werden: [Pfad zu catalog]/lang/catalog/[language]/index.php.<br><br>Das kann manuell geschehen, oder über das Administration Tool mit Sprache->[language]->Sprache definieren, oder durch Verwendung des Hilfsprogrammes->Datei Manager.',1,'',0,5,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (6,0,0,2,'Liefer- und Versandkosten','Liefer- und Versandkosten','Fügen Sie hier Ihre Informationen über Liefer- und Versandkosten ein.',1,'',1,1,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (7,0,0,2,'Privatsphäre und Datenschutz','Privatsphäre und Datenschutz','Fügen Sie hier Ihre Informationen über Privatsphäre und Datenschutz ein.',1,'',1,2,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (8,0,0,2,'Unsere AGB\'s','Allgemeine Geschäftsbedingungen','<strong>Allgemeine Gesch&auml;ftsbedingungen<br></strong><br>F&uuml;gen Sie hier Ihre allgemeinen Gesch&auml;ftsbedingungen ein.<br>1. Geltung<br>2. Angebote<br>3. Preis<br>4. Versand und Gefahr&uuml;bergang<br>5. Lieferung<br>6. Zahlungsbedingungen<br>7. Eigentumsvorbehalt <br>8. M&auml;ngelr&uuml;gen, Gew&auml;hrleistung und Schadenersatz<br>9. Kulanzr&uuml;cknahme / Annahmeverweigerung<br>10. Erf&uuml;llungsort und Gerichtsstand<br>11. Schlussbestimmungen',1,'',1,3,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (9,0,0,2,'Kontakt','Kontakt','Fügen Sie hier Ihre Informationen über Kontakt ein.',1,'',1,4,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (10,0,0,2,'Index','Willkommen','{\$greeting}<br><br> Dies ist die Standardinstallation des osCommerce Forking Projektes - XT-Commerce. Alle dargestellten Produkte dienen zur Demonstration der Funktionsweise. Wenn Sie Produkte bestellen, so werden diese weder ausgeliefert, noch in Rechnung gestellt. Alle Informationen zu den verschiedenen Produkten sind erfunden und daher kann kein Anspruch daraus abgeleitet werden.<br><br>Sollten Sie daran interessiert sein das Programm, welches die Grundlage für diesen Shop bildet, einzusetzen, so besuchen Sie bitte die Supportseite von XT-Commerce. Dieser Shop basiert auf der XT-Commerce Version Beta2.<br><br>Der hier dargestellte Text kann in der folgenden Datei einer jeden Sprache geändert werden: [Pfad zu catalog]/lang/catalog/[language]/index.php.<br><br>Das kann manuell geschehen, oder über das Administration Tool mit Sprache->[language]->Sprache definieren, oder durch Verwendung des Hilfsprogrammes->Datei Manager.',1,'',0,5,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (11,0,0,3,'Shipping & Returns','Shipping & Returns','Put here your Shipping & Returns information.',1,'',1,1,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (12,0,0,3,'Privacy Notice','Privacy Notice','Put here your Privacy Notice information.',1,'',1,2,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (13,0,0,3,'Conditions of Use','Conditions of Use','Conditions of Use<br />Put here your Conditions of Use information. <br />1. Validity<br />2. Offers<br />3. Price<br />4. Dispatch and passage of the risk<br />5. Delivery<br />6. Terms of payment<br />7. Retention of title<br />8. Notices of defect, guarantee and compensation<br />9. Fair trading cancelling / non-acceptance<br />10. Place of delivery and area of jurisdiction<br />11. Final clauses',1,'',1,3,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (14,0,0,3,'Contact','Contact','Put here your Contact information.',1,'',1,4,0)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_content_manager VALUES (15,0,0,3,'Index','Welcome','{\$greeting}<br><br> Dies ist die Standardinstallation des osCommerce Forking Projektes - XT-Commerce. Alle dargestellten Produkte dienen zur Demonstration der Funktionsweise. Wenn Sie Produkte bestellen, so werden diese weder ausgeliefert, noch in Rechnung gestellt. Alle Informationen zu den verschiedenen Produkten sind erfunden und daher kann kein Anspruch daraus abgeleitet werden.<br><br>Sollten Sie daran interessiert sein das Programm, welches die Grundlage für diesen Shop bildet, einzusetzen, so besuchen Sie bitte die Supportseite von XT-Commerce. Dieser Shop basiert auf der XT-Commerce Version Beta2.<br><br>Der hier dargestellte Text kann in der folgenden Datei einer jeden Sprache geändert werden: [Pfad zu catalog]/lang/catalog/[language]/index.php.<br><br>Das kann manuell geschehen, oder über das Administration Tool mit Sprache->[language]->Sprache definieren, oder durch Verwendung des Hilfsprogrammes->Datei Manager.',1,'',0,5,0)";
    if (!$q->run($query)) return;

    # configuration_group_id 1
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_NAME', 'XT-Commerce',  1, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_OWNER', 'XT-Commerce', 1, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_OWNER_EMAIL_ADDRESS', 'owner@your-shop.com', 1, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_FROM', 'XT-Commerce <owner@your-shop.com>',  1, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_COUNTRY', '81',  1, 6, NULL, '', 'products_userapi_get_country_name', 'products_adminapi_pull_down_country_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_ZONE', '', 1, 7, NULL, '', 'products_userapi_get_zone_name', 'products_adminapi_pull_down_zone_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EXPECTED_PRODUCTS_SORT', 'desc',  1, 8, NULL, '', NULL, 'products_adminapi_select_option(array(\'asc\', \'desc\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EXPECTED_PRODUCTS_FIELD', 'date_expected',  1, 9, NULL, '', NULL, 'products_adminapi_select_option(array(\'products_name\', \'date_expected\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'USE_DEFAULT_LANGUAGE_CURRENCY', 'false', 1, 10, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SEARCH_ENGINE_FRIENDLY_URLS', 'false',  16, 12, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DISPLAY_CART', 'true',  1, 13, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ALLOW_GUEST_TO_TELL_A_FRIEND', 'false', 1, 14, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ADVANCED_SEARCH_DEFAULT_OPERATOR', 'and', 1, 15, NULL, '', NULL, 'products_adminapi_select_option(array(\'and\', \'or\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_NAME_ADDRESS', 'Store Name\nAddress\nCountry\nPhone',  1, 16, NULL, '', NULL, 'products_adminapi_textarea(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHOW_COUNTS', 'true',  1, 17, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_CUSTOMERS_STATUS_ID_ADMIN', '0',  1, 20, NULL, '', 'products_userapi_get_customer_status_name', 'products_adminapi_pull_down_customers_status_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_CUSTOMERS_STATUS_ID_GUEST', '1',  1, 21, NULL, '', 'products_userapi_get_customer_status_name', 'products_adminapi_pull_down_customers_status_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_CUSTOMERS_STATUS_ID', '2',  1, 23, NULL, '', 'products_userapi_get_customer_status_name', 'products_adminapi_pull_down_customers_status_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ALLOW_ADD_TO_CART', 'false',  1, 24, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ALLOW_CATEGORY_DESCRIPTIONS', 'true', 1, 25, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CURRENT_TEMPLATE', 'xtc', 1, 26, NULL, '', NULL, 'products_adminapi_pull_down_template_sets(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'PRICE_IS_BRUTTO', 'false', 1, 27, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'PRICE_PRECISION', '2', 1, 28, NULL, '', NULL, '')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'USE_SPAW', 'true', 1, 29, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;

    # configuration_group_id 2
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_FIRST_NAME_MIN_LENGTH', '2',  2, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_LAST_NAME_MIN_LENGTH', '2',  2, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_DOB_MIN_LENGTH', '10',  2, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_EMAIL_ADDRESS_MIN_LENGTH', '6',  2, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_STREET_ADDRESS_MIN_LENGTH', '5',  2, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_COMPANY_MIN_LENGTH', '2',  2, 6, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_POSTCODE_MIN_LENGTH', '4',  2, 7, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_CITY_MIN_LENGTH', '3',  2, 8, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_STATE_MIN_LENGTH', '2', 2, 9, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_TELEPHONE_MIN_LENGTH', '3',  2, 10, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_PASSWORD_MIN_LENGTH', '5',  2, 11, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CC_OWNER_MIN_LENGTH', '3',  2, 12, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CC_NUMBER_MIN_LENGTH', '10',  2, 13, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'REVIEW_TEXT_MIN_LENGTH', '50',  2, 14, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MIN_DISPLAY_BESTSELLERS', '1',  2, 15, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MIN_DISPLAY_ALSO_PURCHASED', '1', 2, 16, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 3
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_ADDRESS_BOOK_ENTRIES', '5',  3, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_SEARCH_RESULTS', '20',  3, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_PAGE_LINKS', '5',  3, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_SPECIAL_PRODUCTS', '9', 3, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_NEW_PRODUCTS', '9',  3, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_UPCOMING_PRODUCTS', '10',  3, 6, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_MANUFACTURERS_IN_A_LIST', '0', 3, 7, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_MANUFACTURERS_LIST', '1',  3, 7, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_MANUFACTURER_NAME_LEN', '15',  3, 8, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_NEW_REVIEWS', '6', 3, 9, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_RANDOM_SELECT_REVIEWS', '10',  3, 10, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_RANDOM_SELECT_NEW', '10',  3, 11, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_RANDOM_SELECT_SPECIALS', '10',  3, 12, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_CATEGORIES_PER_ROW', '3',  3, 13, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_PRODUCTS_NEW', '10',  3, 14, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_BESTSELLERS', '10',  3, 15, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_ALSO_PURCHASED', '6',  3, 16, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_PRODUCTS_IN_ORDER_HISTORY_BOX', '6',  3, 17, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MAX_DISPLAY_ORDER_HISTORY', '10',  3, 18, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'PRODUCT_REVIEWS_VIEW', '5',  3, 19, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 4
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'CONFIG_CALCULATE_IMAGE_SIZE', 'true', 4, 1, NULL, '0000-00-00 00:00:00', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'IMAGE_REQUIRED', 'true', 4, 2, NULL, '0000-00-00 00:00:00', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'HEADING_IMAGE_WIDTH', '70', 4, 3, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'HEADING_IMAGE_HEIGHT', '50', 4, 4, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'SUBCATEGORY_IMAGE_WIDTH', '100', 4, 5, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'SUBCATEGORY_IMAGE_HEIGHT', '57', 4, 6, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_WIDTH', '120', 4, 7, '2003-12-15 12:10:45', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_HEIGHT', '80', 4, 8, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_WIDTH', '200', 4, 9, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_HEIGHT', '160', 4, 10, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_WIDTH', '300', 4, 11, '2003-12-15 12:11:00', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_HEIGHT', '240', 4, 12, '2003-12-15 12:11:09', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_BEVEL', '', 4, 13, '2003-12-15 13:14:39', '0000-00-00 00:00:00', '', '')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_GREYSCALE', '', 4, 14, '2003-12-15 13:13:37', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_ELLIPSE', '', 4, 15, '2003-12-15 13:14:57', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_ROUND_EDGES', '', 4, 16, '2003-12-15 13:19:45', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_MERGE', '', 4, 17, '2003-12-15 12:01:43', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_FRAME', '(FFFFFF,000000,3,EEEEEE)', 4, 18, '2003-12-15 13:19:37', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_DROP_SHADDOW', '', 4, 19, '2003-12-15 13:15:14', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_THUMBNAIL_MOTION_BLUR', '(4,FFFFFF)', 4, 20, '2003-12-15 12:02:19', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_BEVEL', '', 4, 21, '2003-12-15 13:42:09', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_GREYSCALE', '', 4, 22, '2003-12-15 13:18:00', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_ELLIPSE', '', 4, 23, '2003-12-15 13:41:53', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_ROUND_EDGES', '', 4, 24, '2003-12-15 13:21:55', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_MERGE', '(overlay.gif,10,-50,60,FF0000)', 4, 25, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_FRAME', '(FFFFFF,000000,3,EEEEEE)', 4, 26, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_DROP_SHADDOW', '(3,333333,FFFFFF)', 4, 27, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_INFO_MOTION_BLUR', '', 4, 28, '2003-12-15 13:21:18', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_BEVEL', '(8,FFCCCC,330000)', 4, 29, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_GREYSCALE', '', 4, 30, '2003-12-15 13:22:58', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_ELLIPSE', '', 4, 31, '2003-12-15 13:22:51', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_ROUND_EDGES', '', 4, 32, '2003-12-15 13:23:17', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_MERGE', '(overlay.gif,10,-50,60,FF0000)', 4, 33, NULL, '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_FRAME', '', 4, 34, '2003-12-15 13:22:43', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_DROP_SHADDOW', '', 4, 35, '2003-12-15 13:22:26', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES ('', 'PRODUCT_IMAGE_POPUP_MOTION_BLUR', '', 4, 36, '2003-12-15 13:22:32', '0000-00-00 00:00:00', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 5
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_GENDER', 'true',  5, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_DOB', 'true',  5, 2, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_COMPANY', 'true',  5, 3, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_SUBURB', 'true', 5, 4, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_STATE', 'true',  5, 5, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACCOUNT_OPTIONS', 'account',  5, 6, NULL, '', NULL, 'products_adminapi_select_option(array(\'account\', \'guest\', \'both\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DELETE_GUEST_ACCOUNT', 'true',  5, 7, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;

    # configuration_group_id 6
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_PAYMENT_INSTALLED', '', 6, 0, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_INSTALLED', 'ot_subtotal.php;ot_shipping.php;ot_tax.php;ot_total.php', 6, 0, '2003-07-18 03:31:55', '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_SHIPPING_INSTALLED', '',  6, 0, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_CURRENCY', 'EUR',  6, 0, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_LANGUAGE', 'de',  6, 0, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DEFAULT_ORDERS_STATUS_ID', '1',  6, 0, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SHIPPING_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SHIPPING_SORT_ORDER', '3',  6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING', 'false', 6, 3, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER', '50',  6, 4, NULL, '', 'currencies->format', NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SHIPPING_DESTINATION', 'national', 6, 5, NULL, '', NULL, 'products_adminapi_select_option(array(\'national\', \'international\', \'both\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SUBTOTAL_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SUBTOTAL_SORT_ORDER', '1',  6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_TAX_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_TAX_SORT_ORDER', '5',  6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_TOTAL_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_TOTAL_SORT_ORDER', '6',  6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_DISCOUNT_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_DISCOUNT_SORT_ORDER', '2', 6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SUBTOTAL_NO_TAX_STATUS', 'true',  6, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'MODULE_ORDER_TOTAL_SUBTOTAL_NO_TAX_SORT_ORDER','4',  6, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;


    # configuration_group_id 7
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHIPPING_ORIGIN_COUNTRY', '81',  7, 1, NULL, '', 'products_userapi_get_country_name', 'products_adminapi_pull_down_country_list(')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHIPPING_ORIGIN_ZIP', '',  7, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHIPPING_MAX_WEIGHT', '50',  7, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHIPPING_BOX_WEIGHT', '3',  7, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHIPPING_BOX_PADDING', '10',  7, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 8
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'PRODUCT_LIST_FILTER', '1', 8, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 9
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STOCK_CHECK', 'true',  9, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ATTRIBUTE_STOCK_CHECK', 'true',  9, 2, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STOCK_LIMITED', 'true', 9, 3, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STOCK_ALLOW_CHECKOUT', 'true',  9, 4, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STOCK_MARK_PRODUCT_OUT_OF_STOCK', '***',  9, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STOCK_REORDER_LEVEL', '5',  9, 6, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 10
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_PAGE_PARSE_TIME', 'false',  10, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_PAGE_PARSE_TIME_LOG', '/var/log/www/tep/page_parse_time.log',  10, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_PARSE_DATE_TIME_FORMAT', '%d/%m/%Y %H:%M:%S', 10, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DISPLAY_PAGE_PARSE_TIME', 'true',  10, 4, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'STORE_DB_TRANSACTIONS', 'false',  10, 5, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;

    # configuration_group_id 11
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'USE_CACHE', 'false',  11, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DIR_FS_CACHE', 'cache',  11, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CACHE_LIFETIME', '3600',  11, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CACHE_CHECK', 'true',  11, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;

    # configuration_group_id 12
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_TRANSPORT', 'sendmail',  12, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'sendmail\', \'smtp\', \'mail\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SENDMAIL_PATH', '/usr/sbin/sendmail', 12, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_MAIN_SERVER', 'localhost', 12, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_Backup_Server', 'localhost', 12, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_PORT', '25', 12, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_USERNAME', 'Please Enter', 12, 6, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_PASSWORD', 'Please Enter', 12, 7, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SMTP_AUTH', 'false', 12, 8, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_LINEFEED', 'LF',  12, 9, NULL, '', NULL, 'products_adminapi_select_option(array(\'LF\', \'CRLF\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_USE_HTML', 'false',  12, 10, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ENTRY_EMAIL_ADDRESS_CHECK', 'false',  12, 11, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SEND_EMAILS', 'true',  12, 12, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;

    # Constants for contact_us
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_EMAIL_ADDRESS', 'contact@your-shop.com', 12, 20, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_NAME', 'Mail send by Contact_us Form',  12, 21, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_REPLY_ADDRESS',  '', 12, 22, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_REPLY_ADDRESS_NAME',  '', 12, 23, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_EMAIL_SUBJECT',  '', 12, 24, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CONTACT_US_FORWARDING_STRING',  '', 12, 25, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # Constants for support system
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_ADDRESS', 'support@your-shop.com', 12, 26, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_NAME', 'Mail send by support systems',  12, 27, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_REPLY_ADDRESS',  '', 12, 28, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_REPLY_ADDRESS_NAME',  '', 12, 29, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_SUBJECT',  '', 12, 30, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_SUPPORT_FORWARDING_STRING',  '', 12, 31, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # Constants for billing system
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_ADDRESS', 'billing@your-shop.com', 12, 32, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_NAME', 'Mail send by billing systems',  12, 33, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_REPLY_ADDRESS',  '', 12, 34, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_REPLY_ADDRESS_NAME',  '', 12, 35, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_SUBJECT',  '', 12, 36, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_FORWARDING_STRING',  '', 12, 37, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'EMAIL_BILLING_SUBJECT_ORDER',  'Your order Nr:{\$nr} / {\$date}', 12, 38, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 13
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DOWNLOAD_ENABLED', 'false',  13, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DOWNLOAD_BY_REDIRECT', 'false',  13, 2, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DOWNLOAD_MAX_DAYS', '7',  13, 3, NULL, '', NULL, '')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DOWNLOAD_MAX_COUNT', '5',  13, 4, NULL, '', NULL, '')";
    if (!$q->run($query)) return;

    # configuration_group_id 14
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'GZIP_COMPRESSION', 'false',  14, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'GZIP_LEVEL', '5',  14, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 15
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_WRITE_DIRECTORY', '/tmp',  15, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_FORCE_COOKIE_USE', 'False',  15, 2, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_CHECK_SSL_SESSION_ID', 'False',  15, 3, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_CHECK_USER_AGENT', 'False',  15, 4, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_CHECK_IP_ADDRESS', 'False',  15, 5, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_BLOCK_SPIDERS', 'False',  15, 6, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SESSION_RECREATE', 'False',  15, 7, NULL, '', NULL, 'products_adminapi_select_option(array(\'True\', \'False\'),')";
    if (!$q->run($query)) return;

    # configuration_group_id 16
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DISPLAY_CONDITIONS_ON_CHECKOUT', 'true',16, 1, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_MIN_KEYWORD_LENGTH', '6', 16, 2, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_KEYWORDS_NUMBER', '5',  16, 3, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_AUTHOR', '',  16, 4, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_PUBLISHER', '',  16, 5, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_COMPANY', '',  16, 6, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_TOPIC', 'shopping',  16, 7, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_REPLY_TO', 'xx@xx.com',  16, 8, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_REVISIT_AFTER', '14',  16, 9, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_ROBOTS', 'index,follow',  16, 10, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_DESCRIPTION', '',  16, 11, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'META_KEYWORDS', '',  16, 12, NULL, '', NULL, NULL)";
    if (!$q->run($query)) return;

    # configuration_group_id 17
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'USE_SPAW', 'true', 17, 1, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACTIVATE_GIFT_SYSTEM', 'false', 17, 2, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SECURITY_CODE_LENGTH', '10', 17, 3, NULL, '2003-12-05 05:01:41', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'NEW_SIGNUP_GIFT_VOUCHER_AMOUNT', '0', 17, 4, NULL, '2003-12-05 05:01:41', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'NEW_SIGNUP_DISCOUNT_COUPON', '', 17, 5, NULL, '2003-12-05 05:01:41', NULL, NULL)";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'ACTIVATE_SHIPPING_STATUS', 'true', 17, 6, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'DISPLAY_CONDITIONS_ON_CHECKOUT', 'true',17, 7, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'CHECK_CLIENT_AGENT', 'false',17, 7, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'SHOW_IP_LOG', 'false',17, 8, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration (configuration_id,  configuration_key, configuration_value, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) VALUES   ('', 'GROUP_CHECK', 'false',  17, 9, NULL, '', NULL, 'products_adminapi_select_option(array(\'true\', \'false\'))')";
    if (!$q->run($query)) return;

    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('1', 'My Store', 'General information about my store', '1', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('2', 'Minimum Values', 'The minimum values for functions / data', '2', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('3', 'Maximum Values', 'The maximum values for functions / data', '3', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('4', 'Images', 'Image parameters', '4', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('5', 'Customer Details', 'Customer account configuration', '5', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('6', 'Module Options', 'Hidden from configuration', '6', '0')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('7', 'Shipping/Packaging', 'Shipping options available at my store', '7', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('8', 'Product Listing', 'Product Listing    configuration options', '8', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('9', 'Stock', 'Stock configuration options', '9', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('10', 'Logging', 'Logging configuration options', '10', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('11', 'Cache', 'Caching configuration options', '11', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('12', 'E-Mail Options', 'General setting for E-Mail transport and HTML E-Mails', '12', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('13', 'Download', 'Downloadable products options', '13', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('14', 'GZip Compression', 'GZip compression options', '14', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('15', 'Sessions', 'Session options', '15', '1')";
    if (!$q->run($query)) return;
    $query = "INSERT INTO " . $prefix . "_products_configuration_group VALUES ('16', 'Meta-Tags/Search engines', 'Meta-tags/Search engines', '16', '1')";
    if (!$q->run($query)) return;

# --------------------------------------------------------
#
# Register masks
#
    xarRegisterMask('ViewProductsBlocks','All','products','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadProductsBlock','All','products','Block','All:All:All','ACCESS_READ');
    xarRegisterMask('EditProductsBlock','All','products','Block','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddProductsBlock','All','products','Block','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteProductsBlock','All','products','Block','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminProductsBlock','All','products','Block','All:All:All','ACCESS_ADMIN');
    xarRegisterMask('ViewProducts','All','products','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadProducts','All','products','All','All','ACCESS_READ');
    xarRegisterMask('EditProducts','All','products','All','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddProducts','All','products','All','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteProducts','All','products','All','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminProducts','All','products','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up modvars
#
    xarModSetVar('products', 'itemsperpage', 20);

# --------------------------------------------------------
#
# Delete block details for this module (for now)
#
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'products')
    );

    // Delete block types.
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarModAPIfunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

# --------------------------------------------------------
#
# Register block types
#
    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'products',
                'blockType' => 'categories'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'products',
                'blockType' => 'best_sellers'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'products',
                'blockType' => 'product_notifications'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'products',
                'blockType' => 'search'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'products',
                'blockType' => 'specials'))) return;

# --------------------------------------------------------
#
# Register block instances
#
// Put a search block in the 'left' blockgroup
    $type = xarModAPIFunc('blocks', 'user', 'getblocktype', array('module' => 'products', 'type'=>'search'));
    $leftgroup = xarModAPIFunc('blocks', 'user', 'getgroup', array('name'=> 'left'));
    $bid = xarModAPIFunc('blocks','admin','create_instance',array('type' => $type['tid'],
                                                                  'name' => 'productssearch',
                                                                  'state' => 0,
                                                                  'groups' => array($leftgroup)));
// Put a categories block in the 'left' blockgroup
    $type = xarModAPIFunc('blocks', 'user', 'getblocktype', array('module' => 'products', 'type'=>'categories'));
    $leftgroup = xarModAPIFunc('blocks', 'user', 'getgroup', array('name'=> 'left'));
    $bid = xarModAPIFunc('blocks','admin','create_instance',array('type' => $type['tid'],
                                                                  'name' => 'productscategories',
                                                                  'state' => 0,
                                                                  'groups' => array($leftgroup)));

# --------------------------------------------------------
#
# Add this module to the list of installed commerce suite modules
#
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    $info = xarModGetInfo(xarModGetIDFromName('products'));
    $modules[$info['name']] = $info['regid'];
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

// Initialisation successful
    return true;
}

function products_activate()
{
    return true;
}

/**
 * upgrade the products module from an old version
 */
function products_upgrade($oldversion)
{
    switch($oldversion){
        case '0.3.0.1':

    }
// Upgrade successful
    return true;
}

/**
 * delete the products module
 */
function products_delete()
{
    $tablenameprefix = xarDBGetSiteTablePrefix() . '_products_';
    $tables = xarDBGetTables();
    $q = new xenQuery();
        foreach ($tables as $table) {
        if (strpos($table,$tablenameprefix) === 0) {
            $query = "DROP TABLE IF EXISTS " . $table;
            if (!$q->run($query)) return;
        }
    }

    xarModDelAllVars('products');
    xarRemoveMasks('products');

    // The modules module will take care of all the blocks

    // Remove from the list of commerce modules
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    unset($modules['products']);
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

	// Delete successful
	return true;
}
# --------------------------------------------------------

?>