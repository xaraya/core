<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Patrick Kellum
// Purpose of file: Display the text content of the block
// ----------------------------------------------------------------------

/**
 * init func
 */
function adminpanels_waitingcontentblock_init()
{
    // Security
    xarSecAddSchema('adminpanels:Waitingcontentblock', 'Block title::');
}
/**
 * Block info array
 */
function adminpanels_waitingcontentblock_info()
{
    return array('text_type' => 'Waiting Content',
         'text_type_long' => 'Displays Waiting Content for All Modules',
         'module' => 'adminpanels',
         'allow_multiple' => false,
         'form_content' => false,
         'form_refresh' => false,
         'show_preview' => true);
}
/**
 * Display func.
 */
function adminpanels_waitingcontentblock_display($blockinfo)
{
// Security Check
    if(!xarSecurityCheck('EditPanel',0,'Waitingcontentblock','$blockinfo[title]::')) return;

    $moditems = array();

    $modlist = xarModAPIFunc('adminpanels','admin','getmoduleswc');
    foreach ($modlist as $modid => $numitems) {
        $modinfo = xarModGetInfo($modid);
        $moditem = array();
        $moditem['name'] = $modinfo['name'];
        $moditem['numitems'] = $numitems;
        $moditem['link'] = xarModURL($modinfo['name'],'admin','main');

        $moditems[] = $moditem;
    }

    $data = xarTplBlock('adminpanels','waitingcontent', array('moditems' => $moditems));

    // Populate block info and pass to BlockLayout.

    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('Waiting Content');
    }

    $blockinfo['content'] = $data;


    return $blockinfo;

}

?>
