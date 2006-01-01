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
function vendors_manufacturersblock_init()
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
function vendors_manufacturersblock_info()
{
    return array(
        'text_type' => 'Content',
        'text_type_long' => 'Generic Content Block',
        'module' => 'vendors',
        'func_update' => '',
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
function vendors_manufacturersblock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('ViewVendorsBlocks', 0, 'Block', "content:$blockinfo[title]:All")) {return;}

    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();
    $configuration = xarModAPIFunc('vendors','admin','load_configuration');
    if(!xarVarFetch('manufacturers_id',    'int',  $manufacturers_id, 0, XARVAR_NOT_REQUIRED)) {return;}

    $q = new xenQuery("SELECT",$xartables['vendors_manufacturers'],array('manufacturers_id', 'manufacturers_name'));
    $q->setorder('manufacturers_name');
    if(!$q->run()) return;
    if ($q->getrows() == 0) return;
    $dropdown = '';
    $list = '';
    if ($q->getrows() <= $configuration['max_display_manufacturers_in_a_list']) {
        // Display a list
        foreach ($q->output() as $manufacturers) {
            $manufacturers_name = ((strlen($manufacturers['manufacturers_name']) > $configuration['max_display_manufacturer_name_len']) ? substr($manufacturers['manufacturers_name'], 0, $configuration['max_display_manufacturer_name_len']) . '..' : $manufacturers['manufacturers_name']);
            if ($manufacturers_id && $manufacturers_id == $manufacturers['manufacturers_id']) $manufacturers_name = '<b>' . $manufacturers_name .'</b>';
            $list .= '<a href="' . xarModURL('vendors','user','default',array('manufacturers_id'  => $manufacturers['manufacturers_id'])) . '">' . $manufacturers_name . '</a><br>';
        }

    }
    else {
        // Display a drop-down
        $manufacturers_array = array();
        if ($configuration['max_manufacturers_list'] < 2) {
            $manufacturers_array[] = array('id' => '', 'text' => xarML('Please make a choice'));
        }
        foreach ($q->output() as $manufacturers) {
            $manufacturers_name = ((strlen($manufacturers['manufacturers_name']) > $configuration['max_display_manufacturer_name_len']) ? substr($manufacturers['manufacturers_name'], 0, $configuration['max_display_manufacturer_name_len']) . '..' : $manufacturers['manufacturers_name']);
            $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],                                     'text' => $manufacturers_name);
        }
        $dropdown = xarModAPIFunc('vendors','user','draw_pull_down_menu',array(
                'name' =>'manufacturers_id',
                'values' => $manufacturers_array,
                'default' => xarSessionGetVar('currency'),
                'parameters' => 'onChange="this.form.submit();" size="' . $configuration['max_manufacturers_list'] . '" style="width: 100%"'
            )
        );
    }

    $data['dropdown'] = $dropdown;
    $data['list'] = $list;
    $blockinfo['content'] = $data;
    return $blockinfo;
}
?>