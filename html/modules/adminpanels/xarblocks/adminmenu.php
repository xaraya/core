<?php
/**
 * File: $Id: s.adminmenu.php 1.69 03/07/13 11:22:33+02:00 marcel@hsdev.com $
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
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
function adminpanels_adminmenublock_init(){
    return true;
}

/**
 * Get information on block
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
 * Display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  data array on success or void on failure
 * @todo    implement centre menu position
*/
function adminpanels_adminmenublock_display($blockinfo){

    // Security Check
    if(!xarSecurityCheck('AdminPanel',0,'adminmenu',"$blockinfo[title]:All:All")) return;

    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    $mods = xarModAPIFunc('modules', 'admin', 'GetList', array('filter' => array('AdminCapable' => 1)));
    
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
    $dec = '';
    // dont show marker unless specified
    if(!xarModGetVar('adminpanels', 'showold')){
        $marker = '';
    } elseif ($marker === 'x09' || $marker === '900' || $marker === '0900') {
        // TODO: remove after beta testing's done
        $en = "3c6120687265663d22687474703a2f2f7861726179612e636f6d2f7e616e6479762f73616d706c65732f22207461726765743d225f626c616e6b223e3c696d67207372633d226d6f64756c65732f61646d696e70616e656c732f786172696d616765732f6d61726b65722e676966222077696474683d22313222206865696768743d223132223e3c2f613e";
        for ($i=0; $i<strlen($en)/2; $i++) { 
            $dec.=chr(base_convert(substr($en,$i*2,2),16,10)); 
        }
        $marker = $dec;
    }

    // which module is loaded atm?
    // we need it's name, type and function - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();

    // Sort Order, Status, Common Labels and Links Display preparation
    $menustyle = xarModGetVar('adminpanels','menustyle');
    $logoutlabel = xarVarPrepForDisplay(xarML('admin logout'));
    $logouturl = xarModURL('adminpanels' ,'admin', 'confirmlogout', array());

    // Get current URL for later comparisons
    // because we need to compare xhtml compliant url, we replace '&' instances with '&amp;'
    $currenturl = str_replace('&', '&amp;', xarServerGetCurrentURL());

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
                    // NOTE: it has been changed a bit to satisfy users logic (bug/feature request #472) 
                    // OLD WAY: 1. blank label 2. no link 3. no alt text 4. links to module functions
                    // NEW WAY: a) as above, when users looking at default main function
                    // NEW WAY: b) main module link becomes active with alt text when user is looking at another screen of this module
                    // lets also add clear identifier for the template that this module is the active one
                    $labelDisplay = ucwords($label);
                    if ($thisfuncname != 'main'){
                        $adminmods[] = array(   'label'     => $labelDisplay,
                                                'link'      => $link,
                                                'modactive' => 1,
                                                'maintitle' => xarML('View default screen for module ').$labelDisplay);
                    } else {
                        $adminmods[] = array(   'label'     => $labelDisplay,
                                                'link'      => '',
                                                'modactive' => 1,
                                                'maintitle' => '');
                    }

                    // Call the admin menu links function, but don't raise an exception if it's not there
                    $menulinks = xarModAPIFunc($label,'admin','getmenulinks',array(),false);
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
                        $indlinks= array();
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
            // TPL override
            if (empty($blockinfo['template'])) {
                $template = 'sidemenu';
            } else {
                $template = $blockinfo['template'];
            }
            $data = xarTplBlock('adminpanels',
                                $template,
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
                
            // check if we need to update the table, return error if check has failed           
            if(!xarModAPIFunc('adminpanels', 'admin', 'updatemenudb')) return;
            
            // get an array of modules sorted by categories from db 
            $catmods = xarModAPIFunc('adminpanels', 'admin', 'buildbycat');

            // scan the array and set labels and states
            foreach($catmods as $cat=>$mods){
                
                // display label for each category
                // TODO: make them ML
                $label = $cat;
                
                foreach($mods as $modname=>$mod){
                    
                    // if this module is loaded we probably want to display it with -current css rule in the menu
                    if($modname == $thismodname && $thismodtype == 'admin'){
                        
                        // get URL to module's main function
                        $link = xarModURL($modname ,'admin', 'main', array());
                        
                        // this module is currently loaded (active), we need to display
                        // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                        // 5. URL with title text, when user is looking at other than default function of this module
                        $labelDisplay = ucwords($modname);
                        
                        // adding attributes and flags to each module link for the template
                        if ($thisfuncname == 'main'){
                            $catmods[$cat][$modname]['features'] = array( 	'label'     => $labelDisplay,
																			'link'      => $link,
																			'modactive' => 1,
																			'overview' 	=> 0,
																			'maintitle' => xarML('Show administration options for module ').$labelDisplay);
                        } else {
                            $catmods[$cat][$modname]['features'] = array( 	'label'     => $labelDisplay,
                                                                			'link'      => $link,
                                                                			'modactive' => 1,
                                                                			'overview' 	=> 1,
                                                                			'maintitle' => xarML('Display overview information for module ').$labelDisplay);
                        }			
                        // For active module we need to display the mod functions links
                        // call the api function to obtain function links, but don't raise an exception if it's not there
                        $menulinks = xarModAPIFunc($modname, 'admin', 'getmenulinks', array(), false);
                        // scan array and prepare the links
                        if (!empty($menulinks)) {
                            foreach($menulinks as $menulink){
                                
                                // please note how we place the marker against active function link
                                if ($menulink['url'] == $currenturl) {
                                    $funcactive = 1;
                                }else{
                                    $funcactive = 0;
                                }

                                $catmods[$cat][$modname]['indlinks'][] = array(	'adminlink' 	=> $menulink['url'],
                                                    							'adminlabel'    => $menulink['label'],
                                                    							'admintitle'    => $menulink['title'],
                                                    							'funcactive'    => $funcactive);
                            }							
                        }else{
                            // not sure if we need this
                            $indlinks= array();
                        }
                    }else{
                       $link = xarModURL($modname ,'admin', 'main', array());
                       $labelDisplay = ucwords($modname);
                       $catmods[$cat][$modname]['features'] = array('label'     => $labelDisplay,
                                                           			'link'      => $link,
                                                           			'modactive' => 0,
                                                           			'overview' 	=> 0,
                                                           			'maintitle' => xarML('Show administration options for module ').$labelDisplay);
                    }
                }
            }
            // prepare the data for template(s)
            $menustyle = xarVarPrepForDisplay(xarML('[by category]'));
            if (empty($indlinks)){
                $indlinks = '';
            }
            if (empty($blockinfo['template'])) {
                $template = 'verticallistbycats';
            } else {
                $template = $blockinfo['template'];
            }
            $tpldata = array(  	'catmods'     	=> $catmods,
								'logouturl'     => $logouturl,
								'logoutlabel'   => $logoutlabel,
								'marker'        => $marker);
	
            $data = xarTplBlock('adminpanels', $template, $tpldata);
            // this should do for now
            break;

        case 'byweight':
                // sort by weight
                // $data = xarModAPIFunc('adminpanels', 'admin', 'buildbyweight');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by weight]'));
                if (empty($blockinfo['template'])) {
                    $template = 'sidemenu';
                } else {
                    $template = $blockinfo['template'];
                }
                $data = xarTplBlock('adminpanels',
                                    $template,
                                    array(  'adminmods'     => $adminmods = array(),
                                            'indlinks'      => $indlinks ='',
                                            'menustyle'     => $menustyle,
                                            'logouturl'     => $logouturl ='index.php?module=adminpanels&amp;type=admin&amp;func=modifyconfig',
                                            'logoutlabel'   => $logoutlabel ='not implemented',
                                            'marker'        => $marker));
                break;

        case 'bygroup':
                // sort by group
                $data = xarModAPIFunc('adminpanels', 'admin', 'buildbygroup');

                $adminmods = 'not implemented';
                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by group]'));
                if (empty($blockinfo['template'])) {
                    $template = 'sidemenu';
                } else {
                    $template = $blockinfo['template'];
                }
                $data = xarTplBlock('adminpanels',
                                    $template,
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


?>