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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Generic check
    if (!xarSecAuthAction(0, 'base:Menublock', '::', ACCESS_READ)) {
        return;
    }

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);

    // Display style
    // style = 1 - simple list
    // style = 2 - drop-down list

    // Title
    $block['title'] = $blockinfo['title'];

    // Styling
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }
    $block['content'] = startMenuStyle($vars['style']);

    $content = 0;

    // nkame: must start with some blank line, otherwise we're not able to
    // chose the first option in case of a drop-down menu.
    // a better solution would be to detect where we are, and adjust the selected
    // option in the list, and only add a blank line in case of no recognition.
    if($vars['style'] == 2)
        $block['content'] .= addMenuStyledUrl($vars['style'], "", "", "");

    // Content
    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        foreach ($contentlines as $contentline) {
            list($url, $title, $comment) = explode('|', $contentline);
            if (xarSecAuthAction(0, 'base:Menublock', "$blockinfo[title]:$title:", ACCESS_READ)) {
                $block['content'] .= addMenuStyledUrl($vars['style'], xarVarPrepForDisplay($title), $url, xarVarPrepForDisplay($comment));
                $content = 1;
            }
        }
    }

    // Modules
    if (!empty($vars['displaymodules'])) {
        $mods = xarModGetList(array('UserCapable' => 1));

        // Separate from current content, if any
        if ($content == 1) {
            $block['content'] .= addMenuStyledUrl($vars['style'], "", "", "");
        }

        foreach($mods as $mod) {
// jgm - need to work back ML into modules table somehow
//            if (file_exists("modules/$mod/modname.php")) {
//                include "modules/$mod/modname.php";
//            } else {

            if (xarSecAuthAction(0, "$mod[name]::", "::", ACCESS_OVERVIEW)) {

/*                        $block['content'] .= addMenuStyledUrl($vars['style'],
                                                              xarVarPrepForDisplay($mod['displayname']),
                                                              xarModURL($mod['name'],
                                                                       'user',
                                                                       'main'),
                                                              xarVarPrepForDisplay($mod['description']));
*/
                        $block['content'] .= addMenuStyledUrl($vars['style'],
                                                              xarVarPrepForDisplay($mod['displayname']),
                                                              xarModURL($mod['name'],
                                                                       'user',
                                                                       'main'),
                                                                       '');
                        $content = 1;
               }
        }

    }

    // Styling
    $block['content'] .= endMenuStyle($vars['style']);

    if ($content) {
        $blockinfo['title'] = $block['title'];
        $blockinfo['content'] = $block['content'];
        //return themesideblock($blockinfo);
        return $blockinfo;
    }
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

    // What style of menu
    $output = '<tr><td class="xar-title">'.xarML('Menu Format').'</td><td></td></tr>';

    $output .= '<tr><td class="xar-normal">'.xarML('Menu as List').':</td><td><input type="radio" name="style" value="1"';
    if ($vars['style'] == 1) {
        $output .= ' checked';
    }
    $output .= '></td></tr><tr><td class="xar-normal">'.xarML('Menu as Dropdown').':</td><td><input type="radio" name="style" value="2"';
    if ($vars['style'] == 2) {
        $output .= ' checked';
    }
    $output .= ' /></td></tr>';

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
         $vars['displaywaiting'],
         $vars['style']) = xarVarCleanFromInput('displaymodules',
					       'displaywaiting',
					       'style');

    // Defaults
    if (empty($vars['displaymodules'])) {
        $vars['displaymodules'] = 0;
    }
    if (empty($vars['displaywaiting'])) {
        $vars['displaywaiting'] = 0;
    }
    if (empty($vars['style'])) {
        $vars['style'] = 1;
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

function startMenuStyle($style)
{
    // Nothing to do for style == 1 (bullet list)
    $content = "";

    if ($style == 2) {
        $content = xarTplBlock('base','startMenuStyle', array('style' => $style));
    }

    return $content;
}

function endMenuStyle($style)
{
    $content = "";

    if ($style == 2){
        $content = xarTplBlock('base','endMenuStyle', array('style' => $style));
    }

    return $content;
}

function addMenuStyledUrl($style, $name, $url, $comment)
{
    $content = xarTplBlock('base','MenuStyledUrl', array('style' => $style, 
                                                         'name' => $name,
                                                         'url' => $url,
                                                         'comment' => $comment));

    return $content;
}
?>