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
    // 1. Implement centre and right position

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
	// there aren't any admin modules, dont display adminmenu
	    return;
	}
    
    // TODO: display content sensitive link to the manual-online_help
    
    // this is how we are marking the currently loaded module
    $marker = xarModGetVar('adminpanels', 'marker');
    if(!isset($marker)){
        xarModSetVar('adminpanels' ,'marker', '[x]');
        $marker = '[x]';
    }
    
    if(!xarModGetVar('adminpanels', 'showold')){
        $marker = '';
    }
    
    // which module is loaded atm?
    // we need it's name and type - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype) = xarRequestGetInfo();
    
    // Sort Order, Status and Links Display preparation
    $menustyle = xarModGetVar('adminpanels','menustyle');
    if($menustyle == 'byname'){
        // sort by name
        foreach($mods as $mod){
            $label = $mod['name'];
            $link = xarModURL($mod['name'] ,'admin', 'main', array());
            // depending on which module is currently loaded we display accordingly
            if($label == $thismodname && $thismodtype == 'admin'){
                    $adminmods[] = array('label' => $label, 'link' => '', 'marker' => $marker);
            }else{
                $adminmods[] = array('label' => $label, 'link' => $link, 'marker' => '');
            }
        }
        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
        $data = xarTplBlock('adminpanels','sidemenu', array('adminmods' => $adminmods, 'menustyle' => $menustyle));
        // this should do for now
        
    }else if ($menustyle == 'bycat'){
        // sort by categories
        xarModAPILoad('adminpanels', 'admin');
        // check if we need to update the table
        xarModAPIFunc('adminpanels', 'admin', 'updatemenudb');
        
        $catmods = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');
        foreach($catmods as $mod){
            $label = $mod;
            $link = xarModURL($mod ,'admin', 'main', array());
            // depending on which module is currently loaded we display accordingly
            // also we are treating category lables in ML fasion
            if($label == $thismodname && $thismodtype == 'admin'){
                $adminmods[] = array('label' => $label, 'link' => '', 'marker' => $marker);
            }elseif($label == 'Global'){
                $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
            }elseif($label == 'Content'){
                $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
            }elseif($label == 'Users & Groups'){
                $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
            }elseif($label == 'Miscellaneous'){
                $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
            }else{
                $adminmods[] = array('label' => $label, 'link' => $link, 'marker' => '');
            }
        }
        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by category]'));
        $data = xarTplBlock('adminpanels','sidemenu', array('adminmods' => $adminmods, 'menustyle' => $menustyle));
        
    }else if ($menustyle == 'byweight'){
        // sort by weight
        xarModAPILoad('adminpanels', 'admin');
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyweight');
        
        $adminmods = 'not implemented';
        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by weight]'));
        $data = xarTplBlock('adminpanels','sidemenu', array('adminmods' => $adminmods, 'menustyle' => $menustyle));
    }else if ($menustyle == 'bygroup'){
        // sort by group
        xarModAPILoad('adminpanels', 'admin');
        $data = xarModAPIFunc('adminpanels', 'admin', 'buildbygroup');
        
        $adminmods = 'not implemented';
        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by group]'));
        $data = xarTplBlock('adminpanels','sidemenu', array('adminmods' => $adminmods, 'menustyle' => $menustyle));
    }
    // default view is by categories
    
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