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
 * initialise the carts module
 */
function carts_init()
{
    $q = new xenQuery();
    $prefix = xarDBGetSiteTablePrefix();

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_configuration";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_configuration (
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

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_configuration_group";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_configuration_group (
      configuration_group_id int NOT NULL auto_increment,
      configuration_group_title varchar(64) NOT NULL,
      configuration_group_description varchar(255) NOT NULL,
      sort_order int(5) NULL,
      visible int(1) DEFAULT '1' NULL,
      PRIMARY KEY (configuration_group_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_counter";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_counter (
      startdate char(8),
      counter int(12)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_counter_history";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_counter_history (
      month char(8),
      counter int(12)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_customers_basket";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_customers_basket (
      customers_basket_id int NOT NULL auto_increment,
      customers_id int NOT NULL,
      products_id tinytext NOT NULL,
      customers_basket_quantity int(2) NOT NULL,
      final_price decimal(15,4) NOT NULL,
      customers_basket_date_added char(8),
      PRIMARY KEY (customers_basket_id)
    )";
    if (!$q->run($query)) return;

    $query = "DROP TABLE IF EXISTS " . $prefix . "_carts_customers_basket_attributes";
    if (!$q->run($query)) return;
    $query = "CREATE TABLE " . $prefix . "_carts_customers_basket_attributes (
      customers_basket_attributes_id int NOT NULL auto_increment,
      customers_id int NOT NULL,
      products_id tinytext NOT NULL,
      products_options_id int NOT NULL,
      products_options_value_id int NOT NULL,
      PRIMARY KEY (customers_basket_attributes_id)
    )";
    if (!$q->run($query)) return;

    # data

# --------------------------------------------------------
#
# Register masks
#
    xarRegisterMask('ViewCartsBlocks','All','carts','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadCartsBlock','All','carts','Block','All:All:All','ACCESS_READ');
    xarRegisterMask('EditCartsBlock','All','carts','Block','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddCartsBlock','All','carts','Block','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteCartsBlock','All','carts','Block','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminCartsBlock','All','carts','Block','All:All:All','ACCESS_ADMIN');
    xarRegisterMask('ViewCarts','All','carts','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadCarts','All','carts','All','All','ACCESS_READ');
    xarRegisterMask('EditCartsBlock','All','carts','Block','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddCartsBlock','All','carts','Block','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteCartsBlock','All','carts','Block','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminCarts','All','carts','All','All','ACCESS_ADMIN');

# --------------------------------------------------------
#
# Set up modvars
#
    xarModSetVar('carts', 'itemsperpage', 20);

# --------------------------------------------------------
#
# Delete block details for this module (for now)
#
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'carts')
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
            array('modName' => 'carts',
                'blockType' => 'shopping_cart'))) return;

    if (!xarModAPIFunc('blocks',
            'admin',
            'register_block_type',
            array('modName' => 'carts',
                'blockType' => 'order_history'))) return;

# --------------------------------------------------------
#
# Register block instances
#
// Put a shopping cart block in the 'right' blockgroup
    $type = xarModAPIFunc('blocks', 'user', 'getblocktype', array('module' => 'carts', 'type'=>'shopping_cart'));
    $rightgroup = xarModAPIFunc('blocks', 'user', 'getgroup', array('name'=> 'right'));
    $bid = xarModAPIFunc('blocks','admin','create_instance',array('type' => $type['tid'],
                                                                  'name' => 'cartscart',
                                                                  'state' => 0,
                                                                  'groups' => array($rightgroup)));

# --------------------------------------------------------
#
# Add this module to the list of installed commerce suite modules
#
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    $info = xarModGetInfo(xarModGetIDFromName('carts'));
    $modules[$info['name']] = $info['regid'];
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

	// Initialisation successful
    return true;
}

function carts_activate()
{
    return true;
}

/**
 * upgrade the carts module from an old version
 */
function carts_upgrade($oldversion)
{
    switch($oldversion){
        case '0.3.0.1':

    }
// Upgrade successful
    return true;
}

/**
 * delete the carts module
 */
function carts_delete()
{
    $tablenameprefix = xarDBGetSiteTablePrefix() . '_carts_';
    $tables = xarDBGetTables();
    $q = new xenQuery();
        foreach ($tables as $table) {
        if (strpos($table,$tablenameprefix) === 0) {
            $query = "DROP TABLE IF EXISTS " . $table;
            if (!$q->run($query)) return;
        }
    }

    xarModDelAllVars('carts');
    xarRemoveMasks('carts');

    // The modules module will take care of all the blocks

    // Remove from the list of commerce modules
    $modules = unserialize(xarModGetVar('commerce', 'ice_modules'));
    unset($modules['carts']);
    $result = xarModSetVar('commerce', 'ice_modules', serialize($modules));

	// Delete successful

	return true;
}
# --------------------------------------------------------

?>