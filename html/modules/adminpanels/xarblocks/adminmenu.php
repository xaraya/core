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
 * @author  Andy Varganov <andyv@yaraya.com>
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
 * @author  Andy Varganov <andyv@yaraya.com>
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
 * @author  Andy Varganov <andyv@yaraya.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    implement centre and right menu position
*/
function adminpanels_adminmenublock_display($blockinfo){

    // Security check
    if (!xarSecAuthAction(0, 'adminpanels:adminmenu:', "$blockinfo[title]::", ACCESS_ADMIN)){
        // not admin? tough luck.. bye bye baby
        return;
    }
    
    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    $mods = xarModGetList(array('AdminCapable' => 1), NULL, NULL, 'class');
	if(empty($mods)) {
        // there aren't any admin modules, dont display adminmenu
	    return;
	}
        
    // this is how we are marking the currently loaded module
    $marker = xarModGetVar('adminpanels', 'marker');
    
    // TODO: put in init
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
    
    // Sort Order, Status, Common Labels and Links Display preparation
    $menustyle = xarModGetVar('adminpanels','menustyle');
    $logoutlabel = xarVarPrepForDisplay(xarML('admin logout'));
    $logouturl = xarModURL('adminpanels' ,'admin', 'confirmlogout', array());
    
    switch(strtolower($menustyle)) {
        case 'byname':
                // sort by name
                foreach($mods as $mod){
                    $label = $mod['name'];
                    $link = xarModURL($mod['name'] ,'admin', 'main', array());

                    // depending on which module is currently loaded we display accordingly
                    if($label == $thismodname && $thismodtype == 'admin'){
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array('label' => $labelDisplay, 'link' => '', 'marker' => $marker);

                        // Load API for individual links. 
                        xarModAPILoad($label, 'admin'); // throw back

                        // The user API function is called.
                        $menulinks = xarModAPIFunc($label,
                                                   'admin',
                                                   'getmenulinks');
                        if (!empty($menulinks)) {
                            $indlinks = array();
                            foreach($menulinks as $menulink){
                                $indlinks[] = array('adminlink' => $menulink['url'], 'adminlabel' => $menulink['label'], 'admintitle' => $menulink['title']);
                            } 
                        } else {
                            $indlinks= '';
                        }
                    }else{
                        $modid = xarModGetIDFromName($mod['name']);
                        $modinfo = xarModGetInfo($modid);
                        if($modinfo){
                            $desc = $modinfo['description'];
                        }
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array('label' => $labelDisplay, 'link' => $link, 'desc' => $desc, 'marker' => '');
                    }
                }
                // prepare the data for template(s)
                if (empty($indlinks)){
                    $indlinks = '';
                }

                $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
                $data = xarTplBlock('adminpanels','sidemenu', array('adminmods'     => $adminmods,
                                                                    'indlinks'     => $indlinks,
                                                                    'menustyle'     => $menustyle,
                                                                    'logouturl'     => $logouturl,
                                                                    'logoutlabel'   => $logoutlabel));
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
                        $labelDisplay = ucwords($label);
                        $adminmods[] = array('label' => $labelDisplay, 'link' => '', 'marker' => $marker);

                        // Load API for individual links. 
                        xarModAPILoad($label, 'admin'); // throw back

                        // The user API function is called.
                        $menulinks = xarModAPIFunc($label,
                                                   'admin',
                                                   'getmenulinks');
                        if (!empty($menulinks)) {
                            $indlinks = array();
                            foreach($menulinks as $menulink){
                                $indlinks[] = array('adminlink' => $menulink['url'], 'adminlabel' => $menulink['label'], 'admintitle' => $menulink['title']);
                            } 
                        } else {
                            $indlinks= '';
                        }
                    } else {
                        switch (strtolower($label)) {
                            case 'global':
                                    $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
                                    break;
                            case 'content':
                                    $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
                                    break;
                            case 'users & groups':
                                    $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
                                    break;
                            case 'miscellaneous':
                                    $adminmods[] = array('label' => xarML($label), 'link' => '', 'marker' => '');
                                    break;
                            default:
                                    $modid = xarModGetIDFromName($label);
                                    $modinfo = xarModGetInfo($modid);
                                    if($modinfo){
                                        $desc = $modinfo['description'];
                                    }
                                    $labelDisplay = ucwords($label);
                                    $adminmods[] = array('label' => $labelDisplay, 'link' => $link, 'desc' => $desc, 'marker' => '');
                                    break;

                        }
                    }
                }
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by category]'));
                if (empty($indlinks)){
                    $indlinks = '';
                }
                $data = xarTplBlock('adminpanels','sidemenu', array('adminmods'     => $adminmods, 
                                                                    'indlinks'     => $indlinks,
                                                                    'menustyle'     => $menustyle,
                                                                    'logouturl'     => $logouturl,
                                                                    'logoutlabel'   => $logoutlabel));
                break;

        case 'byweight':
                // sort by weight
                xarModAPILoad('adminpanels', 'admin');
                $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyweight');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by weight]'));
                $data = xarTplBlock('adminpanels','sidemenu', array('adminmods'     => $adminmods, 
                                                                    'menustyle'     => $menustyle,
                                                                    'logouturl'     => $logouturl,
                                                                    'logoutlabel'   => $logoutlabel));
                break;

        case 'bygroup':
                // sort by group
                xarModAPILoad('adminpanels', 'admin');
                $data = xarModAPIFunc('adminpanels', 'admin', 'buildbygroup');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by group]'));
                $data = xarTplBlock('adminpanels','sidemenu', array('adminmods'     => $adminmods, 
                                                                    'menustyle'     => $menustyle,
                                                                    'logouturl'     => $logouturl,
                                                                    'logoutlabel'   => $logoutlabel));
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
 * @author  Andy Varganov <andyv@yaraya.com>
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
 * @author  Andy Varganov <andyv@yaraya.com>
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