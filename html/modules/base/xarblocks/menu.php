<?php 
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: Display menu, with lots of options
// ----------------------------------------------------------------------

/**
 * init
 */
function base_menublock_init()
{
    xarSecAddSchema('base:Menublock', 'Block title:Link name:');
}
/**
 * Block info array
 */
function base_menublock_info()
{
    return array('text_type' => 'Menu',
		 'text_type_long' => 'Generic menu',
		 'module' => 'base',
		 'func_update' => 'base_menublock_insert',
		 'allow_multiple' => true,
		 'form_content' => false,
		 'form_refresh' => false,
		 'show_preview' => true);
}
/**
 * Display func
 */
function base_menublock_display($blockinfo)
{
    // ToDo: 
    // Major Clean-Up, need to add back menu items manually added, as well as support for a top menu.
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    if (!xarSecAuthAction(0, 'base:Menublock', 
                             "$blockinfo[title]::",
                              ACCESS_READ)) {
        return;
    }

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);
    
    // are there any admin modules, then get their names
    // checking as early as possible :)
    $mods = xarModGetList(array('UserCapable' => 1));
	if(empty($mods)) {
	// there aren't any admin modules, dont display adminmenu
	    return;
	}
    
    // TODO this is how we are marking the currently loaded module <-- Borrowing from the admin panels, 
    // need to break this out to the block update function

    $marker = xarModGetVar('adminpanels', 'marker');
    if(!isset($marker)){
        xarModSetVar('adminpanels' ,'marker', '[x]');
        $marker = '[x]';
    }
    
    // which module is loaded atm?
    // we need it's name and type - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype) = xarRequestGetInfo();
    
    // Sort Order, Status, Common Labels and Links Display preparation
    //$menustyle = xarModGetVar('adminpanels','menustyle');
    $logoutlabel = xarVarPrepForDisplay(xarML('logout'));
    $logouturl = xarModURL('users' ,'user', 'logout', array());
    $loggedin = xarUserIsLoggedIn();

    // Dirty right now, need to do a block group check and fix.
    $menustyle = 'side';

    // Ensure we have a title for the block.
    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('Main Menu');
    }

    switch(strtolower($menustyle)) {
        default:
        case 'side':
                // Added Content For non-modules list.
                if (!empty($vars['content'])) {
                    $usercontent = array();
                    $contentlines = explode("LINESPLIT", $vars['content']);
                    foreach ($contentlines as $contentline) {
                        list($url, $title, $comment) = explode('|', $contentline);
                        if (xarSecAuthAction(0, 'base:Menublock', "$blockinfo[title]:$title:", ACCESS_READ)) {
                            $title = xarVarPrepForDisplay($title);
                            $url = xarVarPrepForDisplay($url);
                            $comment = xarVarPrepForDisplay($comment);
                            $usercontent[] = array('title' => $title, 'url' => $url, 'comment' => $comment);
                        }
                    }
                } else {
                    $usercontent = '';
                }

                // Added list of modules if selected.
                if (!empty($vars['displaymodules'])) {
                    foreach($mods as $mod){
                        $label = $mod['name'];
                        $link = xarModURL($mod['name'] ,'user', 'main', array());
                        // depending on which module is currently loaded we display accordingly
                        if($label == $thismodname && $thismodtype == 'user'){
                            // Get list of links for modules
                            $usermods[] = array('label' => $label, 'link' => '', 'desc' => '', 'marker' => $marker);
/*
                            // Load API for individual links. 
                            if (!xarModAPILoad($label, 'user')) return; // throw back


                            // The user API function is called.
                            $menulinks = xarModAPIFunc($label,
                                       'user',
                                       'getmenulinks');

                            if (!empty($menulinks)) {
                                $indlinks = array();
                                foreach($menulinks as $menulink){
                                    $indlinks[] = array('userlink' => $menulink['userlink'], 'userlabel' => $menulink['userlabel'], 'usertitle' => $menulink['usertitle']);
                                } 
                            } else {
                                $indlinks= '';
                            }
*/
                        }else{
                            $modid = xarModGetIDFromName($mod['name']);
                            $modinfo = xarModGetInfo($modid);
                            if($modinfo){
                                $desc = $modinfo['description'];
                            }
                            $usermods[] = array('label' => $label, 'link' => $link, 'desc' => $desc, 'marker' => '');
                        }
                    }
                } else {
                    $usermods = '';
                }

                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
                $data = xarTplBlock('base','sidemenu', array('usermods'     => $usermods, 
                                                             'menustyle'     => $menustyle,
                                                             'logouturl'     => $logouturl,
                                                             'logoutlabel'   => $logoutlabel,
                                                             'loggedin'      => $loggedin,
                                                             'usercontent'   => $usercontent
                                                             ));
                // this should do for now
                break;
    }

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;

}

function base_menublock_modify($blockinfo)
{
    // TODO --> Send output to template.  Template somewhat complete.
    global $xartheme;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);
    $blockinfo['content'] = '';

    // Defaults
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }

    // What to display
    $output .= '<tr><td class="xar-title">'.xarML('Display').'</td><td></td></tr>';

    $output .= '<tr><td class="xar-normal">'.xarML('Display Modules').':</td><td><input type="checkbox" value="1" name="displaymodules"';
    if (!empty($vars['displaymodules'])) {
        $output .= ' checked';
    }

    $output .= ' /></td></tr><tr><td class="xar-normal">'.xarML('Display Waiting Content').':</td><td><input type="checkbox" value="1" name="displaywaiting"';
    if (!empty($vars['displaywaiting'])) {
        $output .= ' checked';
    }
    $output .= ' /></td></tr>';

    // Content
    $c=1;
    $output .= "</table><table>";
    $output .= "<tr><td valign=\"top\" class=\"xar-title\">".xarML('Menu Content')
    .":</td><td><table border=\"1\"><tr><td align=\"center\" class=\"xar-normal\" style=\"color:$xartheme[table_header_text]; background-color:$xartheme[table_header]; text-align:center\"><b>"
    .xarML('Title')."</b></td><td align=\"center\" class=\"xar-normal\" style=\"color:$xartheme[table_header_text]; background-color:$xartheme[table_header]; text-align:center\"><b>"
    .xarML('URL')."</b></td><td align=\"center\" class=\"xar-normal\" style=\"color:$xartheme[table_header_text]; background-color:$xartheme[table_header]; text-align:center\"><b>"
    .xarML('Description')."&nbsp;</b><span class=\"xar-sub\"><b>(".xarML('Optional').")</b></span></td><td align=\"center\" class=\"xar-normal\" style=\"color:$xartheme[table_header_text]; background-color:$xartheme[table_header]; text-align:center\"><b>"
    .xarML('Delete')."</b></td><td align=\"center\" class=\"xar-normal\" style=\"color:$xartheme[table_header_text]; background-color:$xartheme[table_header]; text-align:center\"><b>".xarML('Insert Blank After')."</b></td></tr>";
    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        foreach ($contentlines as $contentline) {
            $link = explode('|', $contentline);
            $output .= "<tr><td valign=\"top\"><input type=\"text\" name=\"linkname[$c]\" size=\"30\" maxlength=\"255\" value=\"" . xarVarPrepForDisplay($link[1]) . "\" class=\"xar-normal\"></td><td valign=\"top\"><input type=\"text\" name=\"linkurl[$c]\" size=\"30\" maxlength=\"255\" value=\"" . xarVarPrepForDisplay($link[0]) . "\" class=\"xar-normal\"></td><td valign=\"top\"><input type=\"text\" name=\"linkdesc[$c]\" size=\"30\" maxlength=\"255\" value=\"" . xarVarPrepForDisplay($link[2]) . "\" class=\"xar-normal\" /></td><td valign=\"top\"><input type=\"checkbox\" name=\"linkdelete[$c]\" value=\"1\" class=\"xar-normal\"></td><td valign=\"top\"><input type=\"checkbox\" name=\"linkinsert[$c]\" value=\"1\" class=\"xar-normal\" /></td></tr>\n";
            $c++;
        }
    }

    $output .= "<tr><td><input type=\"text\" name=\"new_linkname\" size=\"30\" maxlength=\"255\" class=\"xar-normal\" /></td><td><input type=\"text\" name=\"new_linkurl\" size=\"30\" maxlength=\"255\" class=\"xar-normal\" /></td><td class=\"xar-normal\"><input type=\"text\" name=\"new_linkdesc\" size=\"30\" maxlength=\"255\" class=\"xar-normal\" /></td><td class=\"xar-normal\">".xarML('New Line')."</td><td class=\"xar-normal\"><input type=\"checkbox\" name=\"new_linkinsert\" value=\"1\" class=\"xar-normal\" /></td></tr>\n";
    $output .= '</table></td></tr>';

    return $output;

}

function base_menublock_insert($blockinfo)
{
    list($vars['displaymodules'],
         $vars['displaywaiting']) = xarVarCleanFromInput('displaymodules',
					                                     'displaywaiting');

    // Defaults
    if (empty($vars['displaymodules'])) {
        $vars['displaymodules'] = 0;
    }
    if (empty($vars['displaywaiting'])) {
        $vars['displaywaiting'] = 0;
    }

    // User links
    $content = array();
    $c = 1;
    if (isset($blockinfo['linkname'])) {
        list($linkurl, $linkname, $linkdesc) = xarVarCleanFromInput('linkurl', 'linkname', 'linkdesc');
        foreach ($blockinfo['linkname'] as $v) {
            if (!isset($blockinfo['linkdelete'][$c])) {
                $content[] = "$linkurl[$c]|$linkname[$c]|$linkdesc[$c]";
            }
            if (isset($blockinfo['linkinsert'][$c])) {
                $content[] = "||";
            }
            $c++;
        }
    }
    if ($blockinfo['new_linkname']) {
       $content[] = xarVarCleanFromInput('new_linkurl').'|'.xarVarCleanFromInput('new_linkname').'|'.xarVarCleanFromInput('new_linkdesc');
    }
    $vars['content'] = implode("LINESPLIT", $content);

    $blockinfo['content']= serialize($vars);

    return($blockinfo);
}

?>