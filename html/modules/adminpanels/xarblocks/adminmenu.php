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
    
    // we need to hide the adminmenu block 
    // if the current module is not an admin_capable and if we are not in the admin part..
    // we also want to hide other centre blocks
    // hack atm, because I couldn't find proper API functions for this situation
    // NOTE_TO_SELF: prolly need to move this to adminapi
//    list($dbconn) = xarDBGetConn();
//    $xartable = xarDBGetTables();
//    $modulestable = $xartable['modules'];
//
//    $query = "SELECT xar_admin_capable
//              FROM $modulestable
//              WHERE xar_name ='". xarVarPrepForStore(xarModGetName()) ."'";
//
//    $result = $dbconn->Execute($query);
//
//    if($dbconn->ErrorNo() != 0) {
//        return;
//    }
//
//    if ($result->EOF) {
//        return false;
//    }
//    list($state) = $result->fields;
//    $result->Close();
// 
    // are we in the admin part of the module?
    // NOTE_TO_SELF: will it hold water with new php versions?
//    $isadmin = preg_match("/admin/i", xarServerGetVar("REQUEST_URI"));

    // removed lots of commented out stuff below

    // Get variables from content block
//    $vars = unserialize($blockinfo['content']);

    // which module is currently loaded?
//    $thismod = xarModGetName(); // moved to xaradminapi
    
    
    // TODO: display content sensitive link to the manual-online_help
    
    // prepare the show
    xarModAPILoad('adminpanels', 'admin');
    
    // do we need to update the menu modules and categories in db table?
    if(!xarModAPIFunc('adminpanels', 'admin', 'updatemenudb')){
        return; // we're outa luck
    }
    
    // Sort Order Status and Links Display.
    $menustyle = xarModGetVar('adminpanels','menustyle');
    if($menustyle == 'byname'){
        // sort by name
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyname');
    }else if ($menustyle == 'bycat'){
        // sort by categories
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');
    }else if ($menustyle == 'byweight'){
        // sort by weight
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyname');
    }else if ($menustyle == 'bygroup'){
        // sort by group
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyname');
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

/**
 * utility function to add module links
 */
function addMenuStyledUrl($name, $url, $comment)
{
        // Bullet list
        if (empty($url)) {
            // Separator
            if (empty($name)) {
                $content = "<br />";
            } else {
                $content = "<br /><b>$name</b><br />";
            }
        } else {
	    // End Bracket Linking
            $content = "<strong><big>&middot;</big></strong>&nbsp;<a class=\"xar-normal\" href=\"$url\" title=\"$comment\">$name</a><br />";
        }

    return $content;
}