<?php
// $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
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
    pnSecAddSchema('adminpanels:adminmenublock:', 'Block title::');
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
    // 1. transfer all api operations to pnadminapi.php
    // 2. see if we can optimize or avoid some db queries

    // Security check
    if (!pnSecAuthAction(0,
                         'adminpanels:adminmenu:',
                         "$blockinfo[title]::",
                         ACCESS_ADMIN)) {
        return;
    }
    
    // are there any admin modules, then get their names
    // checking as early as possible :)
    $mods = pnModGetAdminMods();
	if(!$mods) {
	// there aren't any admin modules, dont display admin menus
	    return;
	}
    
    // we need to hide the adminmenu block 
    // if the current module is not an admin_capable and if we are not in the admin part..
    // we also want to hide other centre blocks
    // hack atm, because I couldn't find proper API functions for this situation
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $modulestable = $pntable['modules'];
//    $modulescolumn = &$pntable['modules_column'];
    $query = "SELECT pn_admin_capable
              FROM $modulestable
              WHERE pn_name ='". pnVarPrepForStore(pnModGetName()) ."'";

    $result = $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        return;
    }

    if ($result->EOF) {
        return false;
    }
    list($state) = $result->fields;
    $result->Close();
 
    // are we in the admin part of the module?
    $isadmin = preg_match("/admin/i", pnServerGetVar("REQUEST_URI"));
      /*  
    if ($state == 1 && $isadmin){

        // which centre blocks do we have active here?
        // there must be a more elegant way to achieve this..
        
        list($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();
        $blockstable = $pntable['blocks'];
//        $blockscolumn = &$pntable['blocks_column'];
        $query =   "SELECT pn_bid
                    FROM $blockstable
                    WHERE pn_active = 1
                    AND pn_position ='".pnVarPrepForStore('c')."'
                    AND pn_bkey !='".pnVarPrepForStore('adminmenu')."'
                    AND pn_bkey !='".pnVarPrepForStore('admintop')."'";
       
        $result = $dbconn->Execute($query);
            
        if($dbconn->ErrorNo() != 0) {
            return;
        }
        
        // deactivate user blocks

        // I'm the user atm
        $uid = pnUserGetVar('uid');
        
        list($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();
        $ublockstable = $pntable['userblocks'];
//        $column = &$pntable['userblocks_column'];
        
        $temp = array();
        
        while(list($bid) = $result->fields){
            $result->MoveNext();
            // temporary deactivate unwanted centre user blocks
            $sql=   "UPDATE $ublockstable 
                    SET pn_active = 0 
                    WHERE pn_uid = '".pnVarPrepForStore($uid)."' 
                    AND pn_bid = ".pnVarPrepForStore($bid);
            $dbconn->Execute($sql);
            $temp[] = $bid;
        }
        
        // set temp storage
        pnModSetVar('adminpanels', 'activeblocks', serialize($temp));
        
        $result->Close();
    } else {
        // activate the centre blocks that we had deactivated
        $temp = unserialize(pnModGetVar('adminpanels', 'activeblocks'));
        if(!empty($temp)){
            // I'm the user atm
            $uid = pnUserGetVar('uid');
            
            list($dbconn) = pnDBGetConn();
            $pntable = pnDBGetTables();
            $ublockstable = $pntable['userblocks'];
//            $column = &$pntable['userblocks_column'];
            foreach($temp as $bid){
                // activate
                $sql = "UPDATE $ublockstable
                        SET pn_active = 1
                        WHERE pn_uid = '".pnVarPrepForStore($uid)."' 
                        AND pn_bid = ".pnVarPrepForStore($bid);
                $dbconn->Execute($sql);
            
                if ($dbconn->ErrorNo() != 0) {
                    return;
                }
            }
            // unset temp var
            pnModSetVar('adminpanels', 'activeblocks', '');
        }
        return;
    } 
        
    // display adminmenu as a centre block? 
    // (centre is not implemented yet)
    if( 'r' == pnModGetVar('adminpanels','menuposition') || 'c' == pnModGetVar('adminpanels','menuposition')){

        // put our menu to the right side
        // probably need help... tried and tried again, but it has never worked here ;(
//            $query =   "UPDATE $blockstable
//                        SET $blockscolumn[position]='".pnVarPrepForStore('r')."'
//                        WHERE $blockscolumn[bkey]= ".pnVarPrepForStore('adminmenu');
//            $result = $dbconn->Execute($query);
//
//            if($dbconn->ErrorNo() != 0) {
//                return;
//            }
//        
//            if ($result->EOF) {
//                return false;
//            }
//            
//        } else {
    }             
    
    */
    // Get variables from content block
    $vars = unserialize($blockinfo['content']);

    // which module is currently loaded?
    $thismod = pnModGetName(); // moved to pnadminapi
    
    // display admintop centre block
    // but not for old style admin modules
    // (hack - donno how to do it cleaner, it will probably go away soon)
    // nasty global, how can we avoid using it in the future?
    global $index;
    $currmoddir =  pnModGetInfo(pnModGetIDFromName($thismod));
    if(file_exists("modules/".$currmoddir['directory']."/pnadmin.php")){
        if($index!=1) $index = 1;
    } else {
        $index = 0;
    }
    
    // TODO: display link to the manual (do we need it here?)
    // atm the manual is displayed in the admintop menu
    
    // Create output object
    $output = new pnHTML();
    $output->SetInputMode(_PNH_VERBATIMINPUT);

    // prepare the show
    pnModAPILoad('adminpanels', 'admin');
    
    // do we need to update the menu modules and categories in db table?
    if(!pnModAPIFunc('adminpanels', 'admin', 'updatemenudb')){
        echo 'error updating db';
    }
    
    // ToDo: move all non-gui functions and routines to pnadminapi.php
    // not showing old modules sometimes, right?
//    if(pnModGetVar('adminpanels', 'showold')){
//        $args = array('showold'=>true);
//    }else{
//        $args = array('showold'=>false);
//    }
    
    // Sort Order Status and Links Display
    if('byname' == pnModGetVar('adminpanels','menustyle')){
        // sort by name
        $data = pnModAPIFunc('adminpanels', 'admin', 'buildbyname');
        $output->Text('<font class="pn-sub">&nbsp;[ '.pnMLByKey('BYNAME').' ]</font>');
        $output->Linebreak();
        $output->Text($data);
    }else{
        // sort by categories
        $data = pnModAPIFunc('adminpanels', 'admin', 'buildbycat');
        $output->Text('<font class="pn-sub">&nbsp;[ '.pnMLByKey('BYCAT').' ]</font>');
        $output->Linebreak();
        $output->Text($data);
    }
    
    $output->SetInputMode(_PNH_PARSEINPUT);
    // Populate block info and pass to theme
    $blockinfo['content'] = $output->GetOutput();
    //return themesideblock($blockinfo);
    return $blockinfo;
}


/**
 * modify block settings
 */
function adminpanels_adminmenublock_modify($blockinfo)
{
    // Return - nothing to modify yet
    return $blockinfo;
}

/**
 * update block settings
 */
function adminpanels_adminmenublock_update($blockinfo)
{

    // Return - nothing to update yet
    return $blockinfo;
}

?>
