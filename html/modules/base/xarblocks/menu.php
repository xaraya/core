<?php
/**
 * File: $Id$
 *
 * Initialise Block
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage base module
 * @author Patrick Kellum, Jim McDonald, Greg Allan, John Cox
*/

/**
 * initialise block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_init()
{
    return true;
}

/**
 * get information on block
 *
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
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
 * display usermenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    implement centre and right menu position
*/
function base_menublock_display($blockinfo)
{

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

// Security Check
    if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$blockinfo[title]:All")) return;

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);

    // are there any user modules, then get their names
    // checking as early as possible :)
    $mods = xarModAPIFunc('modules', 
                          'admin', 
                          'GetList', 
                          array('filter'     => array('UserCapable' => 1)));
    if(empty($mods)) {
    // there aren't any admin modules, dont display adminmenu
        return;
    }

    // Get the marker for the main menu
    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

    $marker = $vars['marker'];


    // which module is loaded atm?
    // we need it's name, type and function - dealing only with user type mods, aren't we?
    // This needs to be deprecated for multi-modules setups later on
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();

    // Sort Order, Status, Common Labels and Links Display preparation
    //$menustyle = xarModGetVar('adminpanels','menustyle');
    $logoutlabel = xarVarPrepForDisplay(xarML('logout'));
    $logouturl = xarModURL('roles' ,'user', 'logout', array());
    $loggedin = xarUserIsLoggedIn();

    // Get current URL
    $currenturl = preg_replace('/&/', '&amp;', xarServerGetCurrentURL());

    // Dirty right now, need to do a block group check and fix.
    $menustyle = 'side';

    switch(strtolower($menustyle)) {
        default:
        case 'side':
                // Added Content For non-modules list.
                if (!empty($vars['content'])) {
                    $usercontent = array();
                    $contentlines = explode("LINESPLIT", $vars['content']);
                    foreach ($contentlines as $contentline) {
                        //list($url, $title, $comment, $child) = explode('|', $contentline);
                    // FIXME: make sure we don't generate content lines with missing pieces elsewhere
                        $parts = explode('|', $contentline);
                        $url = $parts[0];
                        if (!empty($url)){
                            switch ($url[0])
                            {
                                case '[': // module link
                                {
                                    $url = explode(':', substr($url, 1,  - 1));
                                    $url = xarModUrl($url[0], 'user', 'main');
                                    break;
                                }
                                case '{': // article link
                                {
                                    $url = explode(':', substr($url, 1,  - 1));
                                    $url = xarModUrl('articles', 'user', 'view', array('ptid' => $url[0]));
                                    break;
                                }
                                case '(': // category link
                                {
                                    $url = explode(':', substr($url, 1,  - 1));
                                    $url = xarModUrl('articles', 'user', 'view', array('catid' => $url[0]));
                                    break;
                                }
                            }
                        }
                        $title = $parts[1];
                        $comment = $parts[2];
                        $child = isset($parts[3]) ? $parts[3] : '';
                        // Security Check
                        if (xarSecurityCheck('ReadBaseBlock',0,'Block',"$blockinfo[title]:$title:All")) {
                            $title = xarVarPrepForDisplay($title);
                            $comment = xarVarPrepForDisplay($comment);
                            $child = xarVarPrepForDisplay($child);
                            $usercontent[] = array('title' => $title, 'url' => $url, 'comment' => $comment, 'child'=> $child, 'here'=> $currenturl);
                        }
                    }
                } else {
                    $usercontent = '';
                }

                // Added list of modules if selected.
                if (!empty($vars['displaymodules'])) {
                    if (xarSecurityCheck('ReadBaseBlock',0,'Block',"menu:$blockinfo[title]:All")) {
                        foreach($mods as $mod){
                            $label = $mod['name'];
                            $link = xarModURL($mod['name'] ,'user', 'main', array());
                            // depending on which module is currently loaded we display accordingly
                            if($label == $thismodname && $thismodtype == 'user'){

                                // Get list of links for modules
                                $labelDisplay = ucwords($label);
                                $usermods[] = array(   'label'     => $labelDisplay,
                                                       'link'      => '',
                                                       'modactive' => 1);

                                // Lets check to see if the function exists and just skip it if it doesn't
                                // with the new api load, it causes some problems.  We need to load the api
                                // in order to do it right.
                                xarModAPILoad($label, 'user');
                                if (function_exists($label.'_userapi_getmenulinks') ||
                                    file_exists("modules/$mod[osdirectory]/xaruserapi/getmenulinks.php")){
                                    // The user API function is called.
                                    $menulinks = xarModAPIFunc($label,
                                                               'user',
                                                               'getmenulinks');

                                } else {
                                    $menulinks = '';
                                }

                                if (!empty($menulinks)) {
                                    $indlinks = array();
                                    foreach($menulinks as $menulink){

                                        // Compare with current URL
                                        if ($menulink['url'] == $currenturl) {
                                            $funcactive = 1;
                                        } else {
                                            $funcactive = 0;
                                        }

                            // Security Check
//                                        if (xarSecurityCheck('ReadBaseBlock',0,'Block',"$blockinfo[title]:$menulink[title]:All")) {
                                            $indlinks[] = array('userlink'      => $menulink['url'],
                                                                'userlabel'     => $menulink['label'],
                                                                'usertitle'     => $menulink['title'],
                                                                'funcactive'    => $funcactive);
                                        }
//                                    }
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
                                $usermods[] = array('label' => $labelDisplay,
                                                    'link' => $link,
                                                    'desc' => $desc,
                                                    'modactive' => 0);
                            }
                        }
                    } else {
                        $modid = xarModGetIDFromName('roles');
                        $modinfo = xarModGetInfo($modid);
                        if($modinfo){
                            $desc = $modinfo['description'];
                        }
                        $usermods[] = array('label' => 'roles',
                            'link' => xarModUrl('roles', 'user', 'main'),
                            'desc' => $desc,
                            'modactive' => 0);
                    }
                } else {
                    $usermods = '';
                }

                // prepare the data for template(s)
                $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
                if (empty($indlinks)){
                    $indlinks = '';
                }

                // we dont want to show logout link if the user is anonymous or admin
                // admins have their own logout method, which is more robust
                // Security Check
                if (xarSecurityCheck('AdminPanel',0,'adminmenu',"$blockinfo[title]:All:All") or !xarUserIsLoggedIn()){
                    $showlogout = false;
                }else{
                    $showlogout = true;
                }

                //$meta['activepage'] = preg_replace('/&[^amp;]/', '&amp;', xarServerGetCurrentURL());
                $rssurl         = preg_replace('/&/', "&amp;$1", xarServerGetCurrentURL(array('theme' => 'rss')));
                $printurl       = preg_replace('/&/', "&amp;$1", xarServerGetCurrentURL(array('theme' => 'print')));
                $rssurl         = preg_replace('/&/', "&amp;$1", xarServerGetCurrentURL(array('theme' => 'rss')));
                $printurl       = preg_replace('/&/', "&amp;$1", xarServerGetCurrentURL(array('theme' => 'print')));

                if (isset($vars['displayprint'])) {
                    $displayprint = $vars['displayprint'];
                } else {
                    $displayprint = 0;
                }
                if (isset($vars['displayrss'])) {
                    $displayrss = $vars['displayrss'];
                } else {
                    $displayrss = 0;
                }

                if (empty($blockinfo['template'])) {
                    $template = 'sidemenu';
                } else {
                    $template = $blockinfo['template'];
                }

                $data = xarTplBlock('base',$template, array('usermods'         => $usermods,
                                                            'indlinks'         => $indlinks,
                                                            //'blockid'          => $blockinfo['bid'],
                                                            'logouturl'        => $logouturl,
                                                            'logoutlabel'      => $logoutlabel,
                                                            'loggedin'         => $loggedin,
                                                            'usercontent'      => $usercontent,
                                                            'marker'           => $marker,
                                                            'showlogout'       => $showlogout,
                                                            'where'            => $thismodname,
                                                            'what'             => $thisfuncname,
                                                            'displayrss'       => $displayrss,
                                                            'displayprint'     => $displayprint,
                                                            'displayrss'       => $displayrss,
                                                            'displayprint'     => $displayprint,
                                                            'printurl'         => $printurl,
                                                            'rssurl'           => $rssurl));
                // this should do for now
                break;
    }

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;
    return $blockinfo;

}

/**
 * modify block settings
 *
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_modify($blockinfo)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Break out options from our content field
    $vars = unserialize($blockinfo['content']);
    $blockinfo['content'] = '';

    // Defaults
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }

    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

    // Prepare output array
    $c=1;
    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        $vars['contentlines'] = array();
        foreach ($contentlines as $contentline) {
            $link = explode('|', $contentline);
            $vars['contentlines'][] = $link; 
            $c++;
        }
    }
    
    return xarTplBlock('base', 'menuAdmin', $vars);
}

/**
 * update block settings
 *
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_insert($blockinfo)
{
    // Should be boolean, but needs to review the where this variable is coming from to change it.
    if (!xarVarFetch('displaymodules', 'str:1', $vars['displaymodules'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayrss', 'str:1', $vars['displayrss'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayprint', 'str:1', $vars['displayprint'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayrss', 'str:1', $vars['displayrss'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayprint', 'str:1', $vars['displayprint'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('marker', 'str:1', $vars['marker'], '[x]', XARVAR_NOT_REQUIRED)) return;

    // User links
    $content = array();
    $c = 1;
    if (isset($blockinfo['linkname'])) {
    if(!xarVarFetch('linkurl',   'isset', $linkurl,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('linkname',  'isset', $linkname,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('linkdesc',  'isset', $linkdesc,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('linkchild', 'isset', $linkchild, NULL, XARVAR_DONT_SET)) {return;}

        foreach ($blockinfo['linkname'] as $v) {
            if (!isset($blockinfo['linkdelete'][$c])) {
                // FIXME: MrB, i added the @ to avoid testing whether all fields contains something usefull
                @$content[] = "$linkurl[$c]|$linkname[$c]|$linkdesc[$c]|$linkchild[$c]";
            }
            if (isset($blockinfo['linkinsert'][$c])) {
                $content[] = "||";
            }
            $c++;
        }
    }
    if ($blockinfo['new_linkname']) {
       $content[] = xarVarCleanFromInput('new_linkurl').'|'.xarVarCleanFromInput('new_linkname').'|'.xarVarCleanFromInput('new_linkdesc').'|'.xarVarCleanFromInput('new_linkchild');
    }
    $vars['content'] = implode("LINESPLIT", $content);

    $blockinfo['content']= serialize($vars);

    // Ensure we have a title for the block.
    if (empty($blockinfo['title'])){
        $blockinfo['title'] = xarML('Main Menu');
    }

    return($blockinfo);
}

?>
