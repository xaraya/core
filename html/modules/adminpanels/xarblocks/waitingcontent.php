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
    if (!xarSecAuthAction(0,
                         'adminpanels:Waitingcontentblock:',
                         "$blockinfo[title]::",
                         ACCESS_EDIT)) {
        return;
    }

    // Load API
    if (!xarModAPILoad('adminpanels', 'user')) return;

    $data['moditems'] = array();

    $modlist = xarModAPIFunc('adminpanels','admin','getmodules');
    foreach ($modlist as $modid => $numitems) {
        $modinfo = xarModGetInfo($modid);
        $moditem = array();
        $moditem['name'] = $modinfo['name'];
        $moditem['numitems'] = $numitems;
        $moditem['link'] = xarModURL($modinfo['name'],'admin','main');

        $data['moditems'][] = $moditem;
    }

    $data = xarTplBlock('adminpanels','waitingcontent', array('link'     => $moditem['link'],
                                                              'items'    => $moditem['numitems'],
                                                              'modname'  => $moditem['name']));

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;

}

?>