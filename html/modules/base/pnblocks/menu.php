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
    pnSecAddSchema('base:Menublock', 'Block title:Link name:');
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
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Generic check
    if (!pnSecAuthAction(0, 'base:Menublock', '::', ACCESS_READ)) {
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
            if (pnSecAuthAction(0, 'base:Menublock', "$blockinfo[title]:$title:", ACCESS_READ)) {
                $block['content'] .= addMenuStyledUrl($vars['style'], pnVarPrepForDisplay($title), $url, pnVarPrepForDisplay($comment));
                $content = 1;
            }
        }
    }

    // Modules
    if (!empty($vars['displaymodules'])) {
        $mods = pnModGetList(array('UserCapable' => 1));

        // Separate from current content, if any
        if ($content == 1) {
            $block['content'] .= addMenuStyledUrl($vars['style'], "", "", "");
        }

        foreach($mods as $mod) {
// jgm - need to work back ML into modules table somehow
//            if (file_exists("modules/$mod/modname.php")) {
//                include "modules/$mod/modname.php";
//            } else {

            if (pnSecAuthAction(0, "$mod[name]::", "::", ACCESS_OVERVIEW)) {

/*                        $block['content'] .= addMenuStyledUrl($vars['style'],
                                                              pnVarPrepForDisplay($mod['displayname']),
                                                              pnModURL($mod['name'],
                                                                       'user',
                                                                       'main'),
                                                              pnVarPrepForDisplay($mod['description']));
*/
                        $block['content'] .= addMenuStyledUrl($vars['style'],
                                                              pnVarPrepForDisplay($mod['displayname']),
                                                              pnModURL($mod['name'],
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
    global $pntheme;

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);
    $blockinfo['content'] = '';

    // Defaults
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }

    // What style of menu
    $output = '<tr><td class="pn-title">'._MENU_FORMAT.'</td><td></td></tr>';

    $output .= '<tr><td class="pn-normal">'._MENU_AS_LIST.':</td><td><input type="radio" name="style" value="1"';
    if ($vars['style'] == 1) {
        $output .= ' checked';
    }
    $output .= '></td></tr><tr><td class="pn-normal">'._MENU_AS_DROPDOWN.':</td><td><input type="radio" name="style" value="2"';
    if ($vars['style'] == 2) {
        $output .= ' checked';
    }
    $output .= ' /></td></tr>';

    // What to display
    $output .= '<tr><td class="pn-title">'._DISPLAY.'</td><td></td></tr>';

    $output .= '<tr><td class="pn-normal">'._MENU_MODULES.':</td><td><input type="checkbox" value="1" name="displaymodules"';
    if (!empty($vars['displaymodules'])) {
        $output .= ' checked';
    }

    $output .= ' /></td></tr><tr><td class="pn-normal">'._WAITINGCONT.':</td><td><input type="checkbox" value="1" name="displaywaiting"';
    if (!empty($vars['displaywaiting'])) {
        $output .= ' checked';
    }
    $output .= ' /></td></tr>';

    // Content
    $c=1;
    $output .= "</table><table>";
    $output .= "<tr><td valign=\"top\" class=\"pn-title\">"._MENU_CONTENT
    .":</td><td><table border=\"1\"><tr><td align=\"center\" class=\"pn-normal\" style=\"color:$pntheme[table_header_text]; background-color:$pntheme[table_header]; text-align:center\"><b>"
    ._TITLE."</b></td><td align=\"center\" class=\"pn-normal\" style=\"color:$pntheme[table_header_text]; background-color:$pntheme[table_header]; text-align:center\"><b>"
    ._URL."</b></td><td align=\"center\" class=\"pn-normal\" style=\"color:$pntheme[table_header_text]; background-color:$pntheme[table_header]; text-align:center\"><b>"
    ._MENU_DESCRIPTION."&nbsp;</b><span class=\"pn-sub\"><b>("._OPTIONAL.")</b></span></td><td align=\"center\" class=\"pn-normal\" style=\"color:$pntheme[table_header_text]; background-color:$pntheme[table_header]; text-align:center\"><b>"
    ._DELETE."</b></td><td align=\"center\" class=\"pn-normal\" style=\"color:$pntheme[table_header_text]; background-color:$pntheme[table_header]; text-align:center\"><b>"._INSERT_BLANK_AFTER."</b></td></tr>";
    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        foreach ($contentlines as $contentline) {
            $link = explode('|', $contentline);
            $output .= "<tr><td valign=\"top\"><input type=\"text\" name=\"linkname[$c]\" size=\"30\" maxlength=\"255\" value=\"" . pnVarPrepForDisplay($link[1]) . "\" class=\"pn-normal\"></td><td valign=\"top\"><input type=\"text\" name=\"linkurl[$c]\" size=\"30\" maxlength=\"255\" value=\"" . pnVarPrepForDisplay($link[0]) . "\" class=\"pn-normal\"></td><td valign=\"top\"><input type=\"text\" name=\"linkdesc[$c]\" size=\"30\" maxlength=\"255\" value=\"" . pnVarPrepForDisplay($link[2]) . "\" class=\"pn-normal\" /></td><td valign=\"top\"><input type=\"checkbox\" name=\"linkdelete[$c]\" value=\"1\" class=\"pn-normal\"></td><td valign=\"top\"><input type=\"checkbox\" name=\"linkinsert[$c]\" value=\"1\" class=\"pn-normal\" /></td></tr>\n";
            $c++;
        }
    }

    $output .= "<tr><td><input type=\"text\" name=\"new_linkname\" size=\"30\" maxlength=\"255\" class=\"pn-normal\" /></td><td><input type=\"text\" name=\"new_linkurl\" size=\"30\" maxlength=\"255\" class=\"pn-normal\" /></td><td class=\"pn-normal\"><input type=\"text\" name=\"new_linkdesc\" size=\"30\" maxlength=\"255\" class=\"pn-normal\" /></td><td class=\"pn-normal\">"._NEWONE."</td><td class=\"pn-normal\"><input type=\"checkbox\" name=\"new_linkinsert\" value=\"1\" class=\"pn-normal\" /></td></tr>\n";
    $output .= '</table></td></tr>';

    return $output;

}

function base_menublock_insert($blockinfo)
{
    list($vars['displaymodules'],
         $vars['displaywaiting'],
         $vars['style']) = pnVarCleanFromInput('displaymodules',
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
        list($linkurl, $linkname, $linkdesc) = pnVarCleanFromInput('linkurl', 'linkname', 'linkdesc');
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
       $content[] = pnVarCleanFromInput('new_linkurl').'|'.pnVarCleanFromInput('new_linkname').'|'.pnVarCleanFromInput('new_linkdesc');
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
        $content = "<br><center><form method=\"post\" action=\"index.php\"><select class=\"pn-text\" name=\"newlanguage\" onChange=\"top.location.href=this.options[this.selectedIndex].value\">";
    }

    return $content;
}

function endMenuStyle($style)
{
    // Nothing to do for style == 1 (bullet list)
    $content = "";
    if ($style == 2) {
        $content = "</select></form></center>";
    }

    return $content;
}

function addMenuStyledUrl($style, $name, $url, $comment)
{
    if ($style == 1) {
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
            $content = "<strong><big>&middot;</big></strong>&nbsp;<a class=\"pn-normal\" href=\"$url\" title=\"$comment\">$name</a><br />";
        }
    } else if ($style == 2) {
        // Drop-down lilst
        if (empty($url)) {
            // Separator
            $content = "<option>-----</option>";
            if (!empty($name)) {
                $content .= "<option>$name</option>";
                $content .= "<option>-----</option>";
            }
        } else {
            $content = "<option value=\"$url\">$name</option>";
        }
    }
    return $content;
}
?>