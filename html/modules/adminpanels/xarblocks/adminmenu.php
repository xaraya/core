<?php
/**
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @TODO: provide admin functions for this block - not site global settings
 * @TODO: this script seems to be the same code repeated over and over - let's remove that duplication
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
function adminpanels_adminmenublock_init()
{
    // Nothing to configure...
    // TODO: ...yet
    return array();
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
function adminpanels_adminmenublock_info()
{
    // Values
    return array(
        'text_type' => 'adminmenu',
        'module' => 'adminpanels',
        'text_type_long' => 'Admin Menu',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => false
    );
}

/**
 * Display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  data array on success or void on failure
 * @todo    implement centre menu position
*/
function adminpanels_adminmenublock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('AdminPanel', 0, 'adminmenu', "$blockinfo[title]:All:All")) {return;}

    // due to shortcomings of modules module, we need this workaround
    // if our module deactivated intentionally or by accident
    // we just switch to the block mode that is not dependent on the module's api
    // the only such mode at the moment is sort by name
    // TODO: eradicate dependency on module api for other sort orders too
    if (!xarModIsAvailable('adminpanels')) {
        xarModSetVar('adminpanels', 'menustyle', 'byname');
    }
    
    // Sort Order, Status, Common Labels and Links Display preparation
    // TODO: pick these up from block settings.
    $menustyle = xarModGetVar('adminpanels', 'menustyle');
    
    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    $mods = xarModAPIFunc('modules', 'admin', 'getlist', array('filter' => array('AdminCapable' => 1)));
    
    // there aren't any admin modules, dont display adminmenu
    // <mrb> How would this happen? adminpanels is here :-)
    if (empty($mods)) return;

    // this is how we are marking the currently loaded module
    // dont show marker unless specified
    $marker = '';
    if (xarModGetVar('adminpanels', 'showmarker')) {
        $marker = xarModGetVar('adminpanels', 'marker');
    }

    // which module is loaded atm?
    // we need it's name, type and function - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();

    // TODO: prep for display in the template, not here.
    $logoutlabel = xarVarPrepForDisplay(xarML('Admin Logout'));
    $logouturl = xarModURL('adminpanels' ,'admin', 'confirmlogout', array());

    // Get current URL for later comparisons because we need to compare
    // xhtml compliant url, we fetch the default 'XML'-formatted URL.
    $currenturl = xarServerGetCurrentURL();

    // Admin types
    $admintypes = array('admin', 'util');

    // TODO: why isn't the menustyle part of the block admin?
    // Set up like it is, means we are forced to use global menu style settings site-wide.
    switch(strtolower($menustyle)){
        case 'byname':
            // display by name
            foreach($mods as $mod){
                $modname = $mod['name'];
                $labelDisplay = $mod['displayname'];
                // get URL to module's main function
                $link = xarModURL($modname, 'admin', 'main', array());
                // if this module is loaded we probably want to display it with -current css rule in the menu
                $adminmods[$modname]['features'] = array(
                    'label'     => $labelDisplay,
                    'link'      => $link,
                    'modactive' => 0,
                    'overview'  => 0,
                    'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));
                
                if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                    // this module is currently loaded (active), we need to display
                    // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                    // 5. URL with title text, when user is looking at other than default function of this module

                    // adding attributes and flags to each module link for the template
                    if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                        $adminmods[$modname]['features']['overview'] = 1;
                        $adminmods[$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                    }

                    // For active module we need to display the mod functions links
                    // call the api function to obtain function links, but don't raise an exception if it's not there
                    $menulinks = xarModAPIFunc($modname, 'admin', 'getmenulinks', array(), false);

                    // scan array and prepare the links
                    if (!empty($menulinks)) {
                        foreach($menulinks as $menulink) {
                            // please note how we place the marker against active function link
                            $adminmods[$modname]['indlinks'][] = array(
                                'adminlink'     => $menulink['url'],
                                'adminlabel'    => $menulink['label'],
                                'admintitle'    => $menulink['title'],
                                'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                            );
                        }
                    } else {
                        // not sure if we need this
                        $indlinks = array();
                    }
                }
            }
            // TODO: move prep to template
            $menustyle = xarVarPrepForDisplay(xarML('[by name]'));

            $template = 'verticallistbyname';
            $data = array(
                'adminmods'     => $adminmods,
                'menustyle'     => $menustyle,
                'logouturl'     => $logouturl,
                'logoutlabel'   => $logoutlabel,
                'marker'        => $marker
            );
            // this should do for now
            break;

        default:
        case 'bycat':
            // sort by categories

            // check if we need to update the table, return error if check has failed
            if (!xarModAPIFunc('adminpanels', 'admin', 'updatemenudb')) {return;}

            // get an array of modules sorted by categories from db
            $catmods = xarModAPIFunc('adminpanels', 'admin', 'buildmenu',array('menustyle' => 'bycat'));

            // scan the array and set labels and states
            foreach ($catmods as $cat => $mods) { 
                // display label for each category
                // TODO: make them ML
                $label = $cat;
                
                foreach ($mods as $modname=>$mod){
                    // get URL to module's main function
                    $link = xarModURL($modname, 'admin', 'main', array());
                    $labelDisplay = $mod['displayname'];
                    // if this module is loaded we probably want to display it with -current css rule in the menu
                    $catmods[$cat][$modname]['features'] = array(
                        'label'     => $labelDisplay,
                        'link'      => $link,
                        'modactive' => 0,
                        'overview'  => 0,
                        'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));
                    if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                        // this module is currently loaded (active), we need to display
                        // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                        // 5. URL with title text, when user is looking at other than default function of this module
 
                        // adding attributes and flags to each module link for the template
                        $catmods[$cat][$modname]['features']['modactive'] = 1;
                        if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                            $catmods[$cat][$modname]['features']['overview'] = 1;
                            $catmods[$cat][$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                        }
                        // For active module we need to display the mod functions links
                        // call the api function to obtain function links, but don't raise an exception if it's not there
                        $menulinks = xarModAPIFunc($modname, 'admin', 'getmenulinks', array(), false);

                        // scan array and prepare the links
                        if (!empty($menulinks)) {
                            foreach($menulinks as $menulink) {
                                // please note how we place the marker against active function link
                                $catmods[$cat][$modname]['indlinks'][] = array(
                                    'adminlink'     => $menulink['url'],
                                    'adminlabel'    => $menulink['label'],
                                    'admintitle'    => $menulink['title'],
                                    'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                                );
                            }
                        }else{
                            // not sure if we need this
                            $indlinks= array();
                        }
                    } else {
                       // Why is this needed?
                       unset($mod['displayname']);
                    }
                }
            }
            // prepare the data for template(s)
            // TODO: move prepare to template.
            $menustyle = xarVarPrepForDisplay(xarML('[by category]'));

            if (empty($indlinks)){
                $indlinks = '';
            }

            $template = 'verticallistbycats';
            $data = array(
                'catmods'       => $catmods,
                'logouturl'     => $logouturl,
                'logoutlabel'   => $logoutlabel,
                'marker'        => $marker
            );
            break;

        case 'byweight':
                // sort by weight
                // $data = xarModAPIFunc('adminpanels', 'admin', 'buildmenu', array('menustyle' => 'byweight');

                $adminmods = xarML('not implemented');

                // prepare the data for template(s)
                // TODO: move prep to template
                $menustyle = xarVarPrepForDisplay(xarML('[by weight]'));

                $template = 'sidemenu';
                $data = array(
                    'adminmods'     => $adminmods = array(),
                    'indlinks'      => $indlinks ='',
                    'menustyle'     => $menustyle,
                    'logouturl'     => $logouturl = xarModURL('adminpanels', 'admin', 'modifyconfig'),
                    'logoutlabel'   => $logoutlabel ='not implemented',
                    'marker'        => $marker
                );
                break;

        case 'bygroup':
                // sort by group
                //$data = xarModAPIFunc('adminpanels', 'admin', 'buildmenu', array('menustyle' => 'bygroup'));

                $adminmods = xarML('not implemented');

                // prepare the data for template(s)
                // TODO: move prep to template
                $menustyle = xarVarPrepForDisplay(xarML('[by group]'));

                $template = 'sidemenu';
                $data = array(
                    'adminmods'     => $adminmods = array(),
                    'indlinks'      => $indlinks ='',
                    'menustyle'     => $menustyle,
                    'logouturl'     => $logouturl = xarModURL('adminpanels', 'admin', 'modifyconfig'),
                    'logoutlabel'   => $logoutlabel = xarML('not implemented'),
                    'marker'        => $marker
                );
                break;

    }

    // Set template base.
    // FIXME: not allowed to set private variables of BL directly
    $blockinfo['_bl_template_base'] = $template;

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;
}

?>
