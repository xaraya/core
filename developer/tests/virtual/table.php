<?php

/**
 * Entrypoint for experimenting with virtual objects
 */
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Xaraya\DataObject\Export\PhpExporter;
use Xaraya\Services\xar;

// initialize bootstrap
sys::init();
// initialize caching
xar::cache()->init();

// initialize database for itemid - if not already loaded
xar::db()->init();
// for hook calls - if not already loaded
//xar::mod()->init();

function test_get_items()
{
    //$descriptor = new TableObjectDescriptor(['table' => 'xar_module_vars']);
    //$objectlist = new DataObjectList($descriptor);
    $objectlist = VirtualObjectFactory::getObjectList(['table' => 'xar_module_vars']);
    $items = $objectlist->getItems(['where' => ['module_id = NULL'], 'fieldlist' => ['name', 'value']]);
    foreach ($items as $itemid => $item) {
        echo "$itemid: " . var_export($item, true) . "\n";
    }
}

function test_show_view()
{
    // for checkAccess to display links
    xar::user()->init();
    // for showView
    xar::tpl()->init();
    //$descriptor = new TableObjectDescriptor(['table' => 'xar_module_vars']);
    //$objectlist = new DataObjectList($descriptor);
    $objectlist = VirtualObjectFactory::getObjectList(['table' => 'xar_module_vars']);
    $items = $objectlist->getItems(['where' => ['module_id = NULL'], 'fieldlist' => ['name', 'value']]);
    //$objectlist->properties['value']->setDisplayStatus(DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE);
    $output = $objectlist->showView();
    // force utf-8 encoding
    $doc = new DOMDocument();
    $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $output);
    //echo $doc->saveHTML();
    $rows = $doc->getElementsByTagName('tr');
    foreach ($rows as $row) {
        echo $row->textContent . "\n";
    }
}

function test_generate_class()
{
    //$descriptor = new TableObjectDescriptor(['table' => 'xar_cache_data']);
    //$objectitem = new DataObject($descriptor);
    $objectitem = VirtualObjectFactory::getObject(['table' => 'xar_cache_data']);
    $exporter = new PhpExporter(0);
    $info = $exporter->addObjectDef('', $objectitem);
    echo $info;
}

test_get_items();
//test_show_view();
//test_generate_class();
