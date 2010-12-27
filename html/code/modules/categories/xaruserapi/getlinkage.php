<?php

function categories_userapi_getlinkage($args)
{
    extract($args);

    // Requires: module, itemtype, itemid (but not validated)

    if (!isset($itemid)) return array();
    if (empty($module)) {
        $module = xarModGetName();
    }

    $modid = xarMod::getID($module);

    $tables = xarDB::getTables();
    sys::import('xaraya.structures.query');
    $q = new Query('SELECT');
    $q->addtable($tables['categories_linkage'],'cl');
    $q->addtable($tables['categories'],'c');
    $q->join('c.id','cl.category_id');
    $q->eq('module_id',$modid);
    if (!empty($itemtype)) {
        if (is_array($itemtype)) {
            $q->in('itemtype',$itemtype);
        } else {
            $q->eq('itemtype',$itemtype);
        }
    }
    if (!empty($itemid)) {
        if (is_array($itemid)) {
            $q->in('item_id',$itemid);
        } else {
            $q->eq('item_id',$itemid);
        }
    }
    if (!empty($basecid)) {
        if (is_array($basecid)) {
            $q->in('basecategory',$basecid);
        } else {
            $q->eq('basecategory',$basecid);
        }
    }
    if (!empty($categoryid)) {
        if (is_array($categoryid)) {
            $q->in('category_id',$categoryid);
        } else {
            $q->eq('category_id',$categoryid);
        }
    }
    $q->addfield('c.id AS id');
    $q->addfield('cl.child_category_id AS childid');
    $q->addfield('c.name AS name');
    $q->addfield('cl.basecategory AS basecategory_id');
    $q->addfield('cl.module_id AS module_id');
    $q->addfield('cl.item_id AS item_id');
    $q->addfield('cl.itemtype AS itemtype');
//    $q->qecho();
    if (!$q->run()) return array();
    return $q->output();
}

?>