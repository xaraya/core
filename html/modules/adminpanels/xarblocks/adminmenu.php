<?php
/**
 * File: $Id$
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/

/**
 * initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminmenublock_init(){
    // Security
    xarSecAddSchema('adminpanels:adminmenublock:', 'Block title::');
}

/**
 * get information on block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
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
 * display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    implement centre menu position
*/
function adminpanels_adminmenublock_display($blockinfo){

    // Security check
    if (!xarSecAuthAction(0, 'adminpanels:adminmenu:', "$blockinfo[title]::", ACCESS_ADMIN)){
        // not admin? tough luck.. bye bye baby
        return;
    }
    
    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    $mods = xarModGetList(array('AdminCapable' => 1), NULL, NULL, 'name');
	if(empty($mods)) {
        // there aren't any admin modules, dont display adminmenu
	    return;
	}
	
	// due to shortcomings of modules module, we need this workaround
	// if our module deactivated intentionally or by accident
	// we just switch to the block mode that is not dependent on the module's api
	// the only such mode at the moment is sort by name
	// TODO: eradicate dependency on module api for other sort orders too
	if(!xarModIsAvailable('adminpanels')){
	     xarModSetVar('adminpanels', 'menustyle', 'byname');
	}
        
    // this is how we are marking the currently loaded module
    $marker = xarModGetVar('adminpanels', 'marker');
    
    // dont show marker unless specified
    if(!xarModGetVar('adminpanels', 'showold')){
        $marker = '';
    }
    
    // which module is loaded atm?
    // we need it's name, type and function - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();
    
    // Sort Order, Status, Common Labels and Links Display preparation
    $menustyle = xarModGetVar('adminpanels','menustyle');
    $logoutlabel = xarVarPrepForDisplay(xarML('admin logout'));
    $logouturl = xarModURL('adminpanels' ,'admin', 'confirmlogout', array());

    // Get current URL
    $currenturl = xarServerGetCurrentURL();
    
    switch(strtolower($menustyle)){
        case 'byname':
                // sort by name
                foreach($mods as $mod){
                    $label = $mod['name'];
                    $link = xarModURL($label ,'admin', 'main', array());

                    // depending on which module is currently loaded, prepare display data
                    if($label == $thismodname && $thismodtype == 'admin'){
                        // clarification (to avoid new template bugs)
                        // this module is currently loaded (active), we need to display
                        // 1. blank label 2. no link 3. no alt text 4. links to module functions
                        // lets also add clear identifier for the template that this module is the active one
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array(   'label'     => $labelDisplay, 
                                                'link'      => '', 
                                                'modactive' => 1);
                                                
                        // For active module we need to display the mod functions links
                        xarModAPILoad($label, 'admin');
                        // call the api function to obtain function links
                        $menulinks = xarModAPIFunc($label, 'admin', 'getmenulinks');
                        // scan array and prepare the links
                        if (!empty($menulinks)){
                            $indlinks = array();
                            foreach($menulinks as $menulink){
                                // please note how we place the marker against active function link
                                if ($menulink['url'] == $currenturl) {
                                    $funcactive = 1;
                                }else{
                                    $funcactive = 0;
                                }
                                
                                $indlinks[] = array('adminlink'     => $menulink['url'], 
                                                    'adminlabel'    => $menulink['label'], 
                                                    'admintitle'    => $menulink['title'],
                                                    'funcactive'    => $funcactive);
                            } 
                        }else{
                            // not sure if we need this
                            // JC -- You do for E_ALL Errors.
                            $indlinks= '';
                        }
                    }else{
                        // clarification (to avoid new template bugs)
                        // this module is currently not loaded (inactive), we need to display
                        // 1. link 2. label 3. alt text ($desc var in this case)
                        // lets also add clear identifier for the template that this module is not the active one
                        $modid = xarModGetIDFromName($label);
                        $modinfo = xarModGetInfo($modid);
                        if($modinfo){
                            // is this in the legacy now?
                            $desc = $modinfo['description'];
                        }
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array(   'label'     => $labelDisplay, 
                                                'link'      => $link,
                                                'desc'      => $desc,
                                                'modactive' => 0);
                    }
                }
                // prepare the data for template(s)
                
                // not sure if we need this
                // JC -- For E_ALL Errors
                if (empty($indlinks)){
                    $indlinks = '';
                }

                $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
                $data = xarTplBlock('adminpanels',
                                    'sidemenu', 
                                    array(  'adminmods'     => $adminmods,  
                                            'indlinks'      => $indlinks,
                                            'menustyle'     => $menustyle,
                                            'logouturl'     => $logouturl,
                                            'logoutlabel'   => $logoutlabel,
                                            'marker'        => $marker));
                // this should do for now
                break;

        default:
        case 'bycat':
                // sort by categories
                xarModAPILoad('adminpanels', 'admin');
                
                // check if we need to update the table
                if(!xarModAPIFunc('adminpanels', 'admin', 'updatemenudb')){
                    // if we fail lets have at least an error displayed
                    return;
                }

                $catmods = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');
                foreach($catmods as $mod){
                    $label = $mod;
                    $link = xarModURL($mod ,'admin', 'main', array());
                    // depending on which module is currently loaded we display accordingly
                    // also we are treating category lables in ML fasion
                    if($label == $thismodname && $thismodtype == 'admin'){
                        // clarification (to avoid new template bugs)
                        // this module is currently loaded (active), we need to display
                        // 1. blank label 2. no link 3. no alt text 4. links to module functions
                        // lets also add clear identifier for the template that this module is the active one
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array(   'label'     => $labelDisplay, 
                                                'link'      => '', 
                                                'modactive' => 1);
                        
                        // For active module we need to display the mod functions links
                        xarModAPILoad($label, 'admin');
                        // call the api function to obtain function links
                        $menulinks = xarModAPIFunc($label, 'admin', 'getmenulinks');
                        // scan array and prepare the links
                        if (!empty($menulinks)) {
                            $indlinks = array();
                            foreach($menulinks as $menulink){
                                // please note how we place the marker against active function link
                                if ($menulink['url'] == $currenturl) {
                                    $funcactive = 1;
                                }else{
                                    $funcactive = 0;
                                }
                                
                                $indlinks[] = array('adminlink'     => $menulink['url'], 
                                                    'adminlabel'    => $menulink['label'], 
                                                    'admintitle'    => $menulink['title'],
                                                    'funcactive'    => $funcactive);
                            } 
                        }else{
                            // not sure if we need this
                            $indlinks= '';
                        }
                    }else{
                        switch (strtolower($label)) {
                            case 'global':
                                    $adminmods[] = array(   'label' => xarML($label), 
                                                            'link'  => false, 
                                                            'modactive' => 0);
                                    break;
                            case 'content':
                                    $adminmods[] = array(   'label' => xarML($label), 
                                                            'link'  => false, 
                                                            'modactive' => 0);
                                    break;
                            case 'users & groups':
                                    $adminmods[] = array(   'label' => xarML($label), 
                                                            'link'  => false, 
                                                            'modactive' => 0);
                                    break;
                            case 'miscellaneous':
                                    $adminmods[] = array(   'label' => xarML($label), 
                                                            'link'  => false, 
                                                            'modactive' => 0);
                                    break;
                            default:
                                    $modid = xarModGetIDFromName($label);
                                    $modinfo = xarModGetInfo($modid);
                                    if($modinfo){
                                        $desc = $modinfo['description'];
                                    }
                                    $labelDisplay = ucwords($label);
                                    $adminmods[] = array(   'label'     => $labelDisplay, 
                                                'link'      => $link,
                                                'desc'      => $desc,
                                                'modactive' => 0);
                                    break;

                        }
                    }
                }
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by category]'));
                if (empty($indlinks)){
                    $indlinks = '';
                }
                $data = xarTplBlock('adminpanels',
                                    'sidemenu', 
                                    array(  'adminmods'     => $adminmods,  
                                            'indlinks'      => $indlinks,
                                            'menustyle'     => $menustyle,
                                            'logouturl'     => $logouturl,
                                            'logoutlabel'   => $logoutlabel,
                                            'marker'        => $marker));
                // this should do for now
                break;

        case 'byweight':
                // sort by weight
                xarModAPILoad('adminpanels', 'admin');
                // $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyweight');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by weight]'));
                $data = xarTplBlock('adminpanels',
                                    'sidemenu', 
                                    array(  'adminmods'     => $adminmods = array(),  
                                            'indlinks'      => $indlinks ='',
                                            'menustyle'     => $menustyle,
                                            'logouturl'     => $logouturl ='index.php?module=adminpanels&amp;type=admin&amp;func=modifyconfig',
                                            'logoutlabel'   => $logoutlabel ='not implemented',
                                            'marker'        => $marker));
                break;

        case 'bygroup':
                // sort by group
                xarModAPILoad('adminpanels', 'admin');
                $data = xarModAPIFunc('adminpanels', 'admin', 'buildbygroup');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by group]'));
                $data = xarTplBlock('adminpanels',
                                    'sidemenu', 
                                    array(  'adminmods'     => $adminmods = array(),  
                                            'indlinks'      => $indlinks ='',
                                            'menustyle'     => $menustyle,
                                            'logouturl'     => $logouturl ='index.php?module=adminpanels&amp;type=admin&amp;func=modifyconfig',
                                            'logoutlabel'   => $logoutlabel ='not implemented',
                                            'marker'        => $marker));
                break;

    }

    // default view is by categories

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;
}

/**
 * modify block settings
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminmenublock_modify($blockinfo)
{
    // Return - nothing to modify
    return $blockinfo;
}

/**
 * update block settings
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_adminmenublock_update($blockinfo)
{

    // Return - nothing to update
    return $blockinfo;
}

?>
