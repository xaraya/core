<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
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
    return array(
        'displaymodules' => 'None',
        'modulelist' => '',
        'displayprint' => true,
        'displayrss' => false,
        'displayprint' => false,
        'marker' => '[x]',
        'content' => 'http://www.example.com/|Title|Example|',
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // don't share across pages (depending on dynamic menu or not)
        'usershared' => 1, // share for group members
        'cacheexpire' => null);
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
    return array(
        'text_type' => 'Menu',
        'text_type_long' => 'Generic menu',
        'module' => 'base',
        'func_update' => 'base_menublock_insert',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
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
    // Security Check
    if (!xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Break out options from our content field
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // are there any user modules, then get their names
    // checking as early as possible :)
    $mods = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('UserCapable' => 1)));
    if(empty($mods)) {
    // there aren't any user capable modules, dont display user menu
        return;
    }

    // Get the marker for the main menu
    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

    $marker = $vars['marker'];

    if (empty($vars['displaymodules'])) {
        $vars['displaymodules'] = 'None';
    }

    // which module is loaded atm?
    // we need it's name, type and function - dealing only with user type mods, aren't we?
    // This needs to be deprecated for multi-modules setups later on
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();

    // Sort Order, Status, Common Labels and Links Display preparation
    $logoutlabel = xarVarPrepForDisplay(xarML('logout'));

    $authmoduledata=xarModAPIFunc('roles','user','getdefaultauthdata');
    $authmodlogout=$authmoduledata['defaultloginmodname'];

    $logouturl = xarModURL($authmodlogout,'user', 'logout', array());
    $loggedin = xarUserIsLoggedIn();

    // Get current URL
    $truecurrenturl = xarServerGetCurrentURL(array(), false);
    $currenturl = xarServerGetCurrentURL();

    // Added Content For non-modules list.
    if (!empty($vars['content'])) {
        $usercontent = array();
        $contentlines = explode("LINESPLIT", $vars['content']);
        foreach ($contentlines as $contentline) {
            //list($url, $title, $comment, $child) = explode('|', $contentline);
            // FIXME: make sure we don't generate content lines with missing pieces elsewhere
            $parts = explode('|', $contentline);
            $url = $parts[0];
            // FIXME: this probably causes bug #3393
            $here = (substr($truecurrenturl, -strlen($url)) == $url) ? 'true' : '';
            if (!empty($url)){
                switch ($url[0])
                {
                    case '[': // module link
                    {
                        // Credit to Elek M?ton for further expansion
                        $sections = explode(']',substr($url,1));
                        $url = explode(':', $sections[0]);
                        // if the current module is active, then we are here
                        if ($url[0] == $thismodname &&
                            (!isset($url[1]) || $url[1] == $thismodtype) &&
                            (!isset($url[2]) || $url[2] == $thisfuncname)) {
                            $here = 'true';
                        }
                        if (empty($url[1])) $url[1]="user";
                        if (empty($url[2])) $url[2]="main";
                        $url = xarModUrl($url[0],$url[1],$url[2]);
                        if(isset($sections[1])) {
                            $url .= xarVarPrepForDisplay($sections[1]);
                        }
                        break;
                    }
                    case '{': // article link
                    {
                        $url = explode(':', substr($url, 1,  - 1));
                        // Get current pubtype type (if any)
                        if (xarVarIsCached('Blocks.articles', 'ptid')) {
                            $ptid = xarVarGetCached('Blocks.articles', 'ptid');
                        }
                        if (empty($ptid)) {
                            // try to get ptid from input
                            xarVarFetch('ptid', 'isset', $ptid, NULL, XARVAR_DONT_SET);
                        }
                        // if the current pubtype is active, then we are here
                        if ($url[0] == $ptid) {
                            $here = 'true';
                        }
                        $url = xarModUrl('articles', 'user', 'view', array('ptid' => $url[0]));
                        break;
                    }
                    case '(': // category link
                    {
                        $url = explode(':', substr($url, 1,  - 1));
                        if (xarVarIsCached('Blocks.categories','catid')) {
                            $catid = xarVarGetCached('Blocks.categories','catid');
                        }
                        if (empty($catid)) {
                            // try to get catid from input
                            xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET);
                        }
                        if (empty($catid) && xarVarIsCached('Blocks.categories','cids')) {
                            $cids = xarVarGetCached('Blocks.categories','cids');
                        } else {
                            $cids = array();
                        }
                        $catid = str_replace('_', '', $catid);
                        $ancestors = xarModAPIFunc('categories','user','getancestors',
                                                  array('cid' => $catid,
                                                        'cids' => $cids,
                                                        'return_itself' => true));
                        if(!empty($ancestors)) {
                            $ancestorcids = array_keys($ancestors);
                            if (in_array($url[0], $ancestorcids)) {
                                // if we are on or below this category, then we are here
                                $here = 'true';
                            }
                        }
                        $url = xarModUrl('articles', 'user', 'view', array('catid' => $url[0]));
                        break;
                    }
                    default: // standard URL
                        // BUG 2023: Make sure manual URLs are prepped for XML, consistent with xarModURL()
                        if (xarMod::$genXmlUrls) {
                            $url = xarVarPrepForDisplay($url);
                        }
                }
            }
            $title = $parts[1];
            $comment = $parts[2];
            $child = isset($parts[3]) ? $parts[3] : '';

            // Security Check
            //FIX: Should contain a check for the particular menu item
            //     Like "menu:$blockinfo[title]:$blockinfo[bid]:$title"?
            if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$blockinfo[title]:$blockinfo[bid]")) {
                $title = xarVarPrepForDisplay($title);
                $comment = xarVarPrepForDisplay($comment);
                $child = xarVarPrepForDisplay($child);
                $usercontent[] = array('title' => $title, 'url' => $url, 'comment' => $comment, 'child'=> $child, 'here'=> $here);
            }
        }
    } else {
        $usercontent = '';
    }

    // Added list of modules if selected.
    if ($vars['displaymodules'] != 'None') {
        if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$blockinfo[title]:$blockinfo[bid]")) {
           $useAliasName=0;
           $aliasname='';
            if ($vars['displaymodules'] == 'List' && !empty($vars['modulelist'])) {
                $modlist = explode(',',$vars['modulelist']);
                $list = array();
                foreach ($modlist as $mod) {
                    $temp = xarMod_getBaseInfo($mod);
                    if(!empty($temp) && xarModIsAvailable($temp['name']))
                        if (isset($temp)) $list[] = $temp;
                }
                $mods = $list;
                if ($list == array()) $usermods = '';
            }

            foreach($mods as $mod){
                if (!xarSecurityCheck('ViewBlock',0,'BlockItem',$blockinfo['name']. ":" . $mod['name'])) continue;
                /* Check for active module alias */
                /* jojodee -  We need to review the module alias functions and, thereafter it's use here */
                $useAliasName = xarModVars::get($mod['name'], 'useModuleAlias');
                $aliasname = xarModVars::get($mod['name'],'aliasname');
                /* use the alias name if it exists for the label */
                if (isset($useAliasName) && $useAliasName==1 && isset($aliasname) && !empty($aliasname)) {
                    $label = $aliasname;
                } else {
                    $label = xarModGetDisplayableName($mod['name']);
                }
                $title = xarModGetDisplayableDescription($mod['name']);
                $link = xarModURL($mod['name'] ,'user', 'main', array());
                // depending on which module is currently loaded we display accordingly
                if($mod['name'] == $thismodname && $thismodtype == 'user'){
                    // Get list of links for modules
                    $labelDisplay = $label;
                    $usermods[] = array(   'label'     => $labelDisplay,
                                           'link'      => '',
                                           'desc'      => $title,
                                           'modactive' => 1);

                    // Lets check to see if the function exists and just skip it if it doesn't
                    // with the new api load, it causes some problems.  We need to load the api
                    // in order to do it right.
                    xarModAPILoad($mod['name'], 'user');
                    if (function_exists($label.'_userapi_getmenulinks') ||
                        file_exists("modules/$mod[osdirectory]/xaruserapi/getmenulinks.php")){
                        // The user API function is called.
                        $menulinks = xarModAPIFunc($mod['name'],  'user', 'getmenulinks');
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
//                                        if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"$blockinfo[title]:$menulink[title]:All")) {
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
                    $labelDisplay = $label;
                    $usermods[] = array('label' => $labelDisplay,
                                        'link' => $link,
                                        'desc' => $title,
                                        'modactive' => 0);
                }
            }
            if (empty($usermods)) $usermods = '';
        } else {
            $modid = xarModGetIDFromName('roles');
            $modinfo = xarModGetInfo($modid);
            if ($modinfo){
                $title = $modinfo['displaydescription'];
            } else {
                  $title = xarML('No description');
            }
            $usermods[] = array('label' => xarModGetDisplayableName('roles'),
                'link' => xarModUrl('roles', 'user', 'main'),
                'desc' => xarModGetDisplayableDescription('roles'),
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
    if (xarSecurityCheck('AdminBaseBlock',0,'adminmenu',"$blockinfo[title]:All:All") or
        !xarUserIsLoggedIn() or
        empty($vars['showlogout'])) {
        $showlogout = false;
    }else{
        $showlogout = true;
    }

    $rssurl         = xarServerGetCurrentURL(array('theme' => 'rss'));
    $printurl       = xarServerGetCurrentURL(array('theme' => 'print'));

    if (isset($vars['displayprint'])) {
        $displayprint = $vars['displayprint'];
    } else {
        $displayprint = false;
    }
    if (isset($vars['displayrss'])) {
        $displayrss = $vars['displayrss'];
    } else {
        $displayrss = false;
    }

    $data = array(
        'usermods'         => $usermods,
        'indlinks'         => $indlinks,
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
        'printurl'         => $printurl,
        'rssurl'           => $rssurl
    );

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
    // Break out options from our content field
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }

    if (!isset($vars['showlogout'])) {
        $vars['showlogout'] = 1;
    }

    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

    if (empty($vars['displaymodules'])) {
        $vars['displaymodules'] = "None";
    }

    if (empty($vars['modulelist'])) {
        $vars['modulelist'] = '';
    }

    // Prepare output array
    $c=0;
    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        $vars['contentlines'] = array();
        foreach ($contentlines as $contentline) {
            $link = explode('|', $contentline);
            $vars['contentlines'][] = $link;
            $c++;
        }
    }

    return $vars;
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
    // Global options.
    if (!xarVarFetch('displaymodules', 'str:1', $vars['displaymodules'], 'None', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modulelist', 'str', $vars['modulelist'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showlogout', 'checkbox', $vars['showlogout'], true, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayrss', 'checkbox', $vars['displayrss'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayprint', 'checkbox', $vars['displayprint'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('marker', 'str:1', $vars['marker'], '[x]', XARVAR_NOT_REQUIRED)) return;

    // User links.
    $content = array();
    $c = 1;
    if (!xarVarFetch('linkname', 'array', $linkname, NULL, XARVAR_NOT_REQUIRED)) return;
    if (isset($linkname)) {
        if (!xarVarFetch('linkurl',  'list:str', $linkurl,  NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkdesc',  'list:str', $linkdesc,  NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkchild', 'list:str', $linkchild, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkdelete', 'list:checkbox', $linkdelete, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('linkinsert', 'list:checkbox', $linkinsert, NULL, XARVAR_NOT_REQUIRED)) return;

        foreach ($linkname as $v) {
            if (!isset($linkdelete[$c]) || $linkdelete[$c] == false) {
                // FIXME: MrB, i added the @ to avoid testing whether all fields contains something useful
                @$content[] = "$linkurl[$c]|$linkname[$c]|$linkdesc[$c]|$linkchild[$c]";
            }
            if (!empty($linkinsert[$c])) {
                $content[] = "||";
            }
            $c++;
        }
    }

    if (!xarVarFetch('new_linkname', 'str', $new_linkname, '', XARVAR_NOT_REQUIRED)) return;
    if (!empty($new_linkname)) {
        if (!xarVarFetch('new_linkurl', 'str', $new_linkurl, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('new_linkdesc', 'str', $new_linkdesc, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('new_linkchild', 'str', $new_linkchild, '', XARVAR_NOT_REQUIRED)) return;

        $content[] = $new_linkurl . '|' . $new_linkname . '|' . $new_linkdesc . '|' . $new_linkchild;
    }

    if (!xarVarFetch('new_linkinsert', 'checkbox', $new_linkinsert, false, XARVAR_NOT_REQUIRED)) return;
    if (!empty($new_linkinsert)) {
        $content[] = "||";
    }

    $vars['content'] = implode("LINESPLIT", $content);

    $blockinfo['content'] = $vars;

    return($blockinfo);
}
function base_menublock_update($blockinfo)
{
    return base_menublock_insert($blockinfo);
}
?>
