<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of template file: Jim McDonald
// Original Author of this file: Andy Varganov
// Purpose of file: Show adminmenu items
// ----------------------------------------------------------------------

/**
 * initialise block
 */
function adminpanels_adminmenublock_init(){
    // Security
    xarSecAddSchema('adminpanels:adminmenublock:', 'Block title::');
}

/**
 * get information on block
 */
function adminpanels_adminmenublock_info(){
    // Values
    return array('text_type' => 'adminmenu',
                 'module' => 'adminpanels',
                 'text_type_long' => 'Admin Menu',
                 'allow_multiple' => false,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => false);
}

/**
 * display block
 */
function adminpanels_adminmenublock_display($blockinfo){
    // ToDo: 
    // 1. Convert to BL
    // 2. Implement left, centre and right position

    // Security check
    if (!xarSecAuthAction(0,
                         'adminpanels:adminmenu:',
                         "$blockinfo[title]::",
                         ACCESS_ADMIN)) {
        return;
    }
    
    // are there any admin modules, then get their names
    // checking as early as possible :)
    $mods = xarModGetList(array('AdminCapable' => 1));
	if(empty($mods)) {
	// there aren't any admin modules, dont display admin menus
	    return;
	}
    
    // TODO: display content sensitive link to the manual-online_help
    
    // are we marking currently loaded module? why not..
    $marker = xarModGetVar('adminpanels', 'marker');
    if(!isset($marker)){
        xarModSetVar('adminpanels' ,'marker', '[x]');
        $marker = '[x]';
    }
    
    if(!xarModGetVar('adminpanels', 'showold')){
        $marker = '';
    }
    
    // which module is loaded atm?
    // not sure why this function should be private, how else can we do it?
    // TODO: check with Marco about it
    list($modName) = xarRequestGetInfo();
    
    // Sort Order Status and Links Display.
    $menustyle = xarModGetVar('adminpanels','menustyle');
    if($menustyle == 'byname'){
        // sort by name
//        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyname'); // obsolete
        foreach($mods as $mod){
            $label = $mod['name'];
            $link = xarModURL($mod['name'] ,'admin', 'main', array());
            // depending on which module is currently loaded we display accordingly
            if($label == $modName){
                $adminmods[] = array('label' => $label, 'link' => '', 'marker' => $marker);
            }else{
                $adminmods[] = array('label' => $label, 'link' => $link, 'marker' => '');
            }
        }
        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
        $data = xarTplBlock('adminpanels','sidemenu', array('adminmods' => $adminmods, 'menustyle' => $menustyle));
    }else if ($menustyle == 'bycat'){
        // sort by categories
        xarModAPILoad('adminpanels', 'admin');
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');
    }else if ($menustyle == 'byweight'){
        // sort by weight
        xarModAPILoad('adminpanels', 'admin');
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyweight');
    }else if ($menustyle == 'bygroup'){
        // sort by group
        xarModAPILoad('adminpanels', 'admin');
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbygroup');
    } else {
        // default view by categories
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');
    }
    
    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;
}


/**
 * modify block settings
 */
function adminpanels_adminmenublock_modify($blockinfo)
{
    // Return - nothing to modify
    return $blockinfo;
}

/**
 * update block settings
 */
function adminpanels_adminmenublock_update($blockinfo)
{

    // Return - nothing to update
    return $blockinfo;
}