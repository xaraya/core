<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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
sys::import('xaraya.structures.containers.blocks.basicblock');

class MenuBlock extends BasicBlock implements iBlock
    {
    public $name                = 'MenuBlock';
    public $module              = 'base';
    public $text_type           = 'Menu';
    public $text_type_long      = 'Generic menu';
    public $allow_multiple      = true;
    public $show_preview        = true;
    public $nocache             = 1;
    public $pageshared          = 0;
    public $usershared          = 1;

    public $displaymodules      = 'None';
    public $modulelist          = '';
    public $displayrss          = false;
    public $displayprint        = false;
    public $marker              = '[x]';
    public $content             = array(
                                    'url' => '[base]&page=docs',
                                    'name'=> 'Documentation',
                                    'description' => 'General Documentation',
                                    'visible' => true,
                                    );
    public $showlogout          = true;

    public $rssurl;
    public $printurl;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->rssurl = xarServer::getCurrentURL(array('theme' => 'rss'));
        $this->printurl = xarServer::getCurrentURL(array('theme' => 'print'));
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (!$data['allowaccess']) return;
        if (empty($data)) return;

        // are there any user modules, then get their names
        // checking as early as possible :)
        $mods = xarMod::apiFunc('modules',
                              'admin',
                              'getlist',
                              array('filter'     => array('UserCapable' => true, 'State' => XARMOD_STATE_ACTIVE)));
        if(empty($mods)) {
        // there aren't any user capable modules, dont display user menu
            return;
        }

        if (empty($data['displaymodules'])) $data['displaymodules'] = $this->displaymodules;
        if (empty($data['modulelist'])) $data['modulelist'] = $this->modulelist;
        if (empty($data['lines'])) $data['lines'] = array($this->content);

        // which module is loaded atm?
        // we need it's name, type and function - dealing only with user type mods, aren't we?
        // This needs to be deprecated for multi-modules setups later on
        list($thismodname, $thismodtype, $thisfuncname) = xarRequest::getInfo();

        // Sort Order, Status, Common Labels and Links Display preparation
        $logoutlabel = xarVarPrepForDisplay(xarML('logout'));

        $authmoduledata = xarMod::apiFunc('roles','user','getdefaultauthdata');
        $authmodlogout = $authmoduledata['defaultloginmodname'];

        $logouturl = xarModURL($authmodlogout,'user', 'logout', array());
        $loggedin = xarUserIsLoggedIn();

        // Get current URL
        $truecurrenturl = xarServer::getCurrentURL(array(), false);
        $currenturl = xarServer::getCurrentURL();

        // Added Content For non-modules list.
        if (!empty($data['lines'])) {
            $usercontent = array();
            foreach ($data['lines'] as $line) {
                if (empty($line['visible'])) continue;
                // FIXME: this probably causes bug #3393
                $here = (substr($truecurrenturl, -strlen($line['url'])) == $line['url']) ? 'true' : '';
                if (!empty($line['url'])){
                    switch (substr($line['url'],0,1))
                    {
                        case '[': // module link
                        {
                            // Credit to Elek M?ton for further expansion
                            $sections = explode(']',substr($line['url'],1));
                            $line['url'] = explode(':', $sections[0]);
                            // if the current module is active, then we are here
                            if ($line['url'][0] == $thismodname &&
                                (!isset($line['url'][1]) || $line['url'][1] == $thismodtype) &&
                                (!isset($line['url'][2]) || $line['url'][2] == $thisfuncname)) {
                                $here = 'true';
                            }
                            if (empty($line['url'][1])) $line['url'][1]="user";
                            if (empty($line['url'][2])) $line['url'][2]="main";
                            $line['url'] = xarModUrl($line['url'][0],$line['url'][1],$line['url'][2]);
                            if(isset($sections[1])) {
                                // fix if the URL is encoded and the next part starts with &
                                if (!strpos($line['url'], '?') && substr($sections[1],0,1) == '&') {
                                    $sections[1] = preg_replace('/^(&amp;|&)/','?',$sections[1]);
                                }
                                $line['url'] .= xarVarPrepForDisplay($sections[1]);
                            }
                            break;
                        }
                        case '{': // article link
                        {
                            $line['url'] = explode(':', substr($line['url'], 1,  - 1));
                            // Get current pubtype type (if any)
                            if (xarVarIsCached('Blocks.articles', 'ptid')) {
                                $ptid = xarVarGetCached('Blocks.articles', 'ptid');
                            }
                            if (empty($ptid)) {
                                // try to get ptid from input
                                xarVarFetch('ptid', 'isset', $ptid, NULL, XARVAR_DONT_SET);
                            }
                            // if the current pubtype is active, then we are here
                            if ($line['url'][0] == $ptid) {
                                $here = 'true';
                            }
                            $line['url'] = xarModUrl('articles', 'user', 'view', array('ptid' => $line['url'][0]));
                            break;
                        }
                        case '(': // category link
                        {
                            $line['url'] = explode(':', substr($line['url'], 1,  - 1));
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
                            $ancestors = xarMod::apiFunc('categories','user','getancestors',
                                                      array('cid' => $catid,
                                                            'cids' => $cids,
                                                            'return_itself' => true));
                            if(!empty($ancestors)) {
                                $ancestorcids = array_keys($ancestors);
                                if (in_array($line['url'][0], $ancestorcids)) {
                                    // if we are on or below this category, then we are here
                                    $here = 'true';
                                }
                            }
                            $line['url'] = xarModUrl('articles', 'user', 'view', array('catid' => $line['url'][0]));
                            break;
                        }
                        default: // standard URL
                            // BUG 2023: Make sure manual URLs are prepped for XML, consistent with xarModURL()
                            if (xarMod::$genXmlUrls) {
                                $line['url'] = xarVarPrepForDisplay($line['url']);
                            }
                    }
                }
                $title = $line['name'];
                $comment = $line['description'];
                $child = isset($line['child']) ? $line['child'] : false;

                // Security Check
                //FIX: Should contain a check for the particular menu item
                //     Like "menu:$data[title]:$data[bid]:$title"?
                if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$data[title]:$data[bid]")) {
                    $title = xarVarPrepForDisplay($title);
                    $comment = xarVarPrepForDisplay($comment);
                    $child = xarVarPrepForDisplay($child);
                    $usercontent[] = array('title' => $title, 'url' => $line['url'], 'comment' => $comment, 'child'=> $child, 'here'=> $here);
                }
            }
        } else {
            $usercontent = '';
        }

        // Added list of modules if selected.
        if ($data['displaymodules'] != 'None') {
            if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"menu:$data[title]:$data[bid]")) {
               $useAliasName=0;
               $module_alias_name='';
                if ($data['displaymodules'] == 'List' && !empty($data['modulelist'])) {
                    $modlist = explode(',',$data['modulelist']);
                    $list = array();
                    foreach ($modlist as $mod) {
                        try {
                            $temp = xarMod_getBaseInfo($mod);
                            if(!empty($temp) && xarModIsAvailable($temp['name']))
                                if (isset($temp)) $list[] = $temp;
                        } catch (Exception $e) {}
                    }
                    $mods = $list;
                    if ($list == array()) $usermods = '';
                }
                    
                $access = isset($args['view_access']) ? $args['view_access'] : array();
                foreach($mods as $mod){
                    if (isset($access[$mod['name']])) {
                        // Decide whether this block is modifiable to the current user
                        $args = array(
                            'module' => 'base',
                            'component' => 'Block',
                            'instance' => $data['title'] . "All:All",
                            'group' => $access[$mod['name']]['group'],
                            'level' => $access[$mod['name']]['level'],
                        );
                        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
                        if (!$accessproperty->check($args)) continue;
                    }

                    if (!xarSecurityCheck('ViewBlock',0,'BlockItem',$data['name']. ":" . $mod['name'])) continue;
                    if ((bool)xarModVars::get($mod['name'], 'user_menu_link')) continue;
                    /* Check for active module alias */
                    /* jojodee -  We need to review the module alias functions and, thereafter it's use here */
                    $useAliasName = xarModVars::get($mod['name'], 'use_module_alias');
                    $module_alias_name = xarModVars::get($mod['name'],'module_alias_name');
                    /* use the alias name if it exists for the label */
                    if (isset($useAliasName) && $useAliasName==1 && isset($module_alias_name) && !empty($module_alias_name)) {
                        $label = $module_alias_name;
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
                        file_exists(sys::code() . "modules/$mod[osdirectory]/xaruserapi/getmenulinks.php")){
                            // The user API function is called.
                            $menulinks = xarMod::apiFunc($mod['name'],  'user', 'getmenulinks');
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
    //                                        if (xarSecurityCheck('ViewBaseBlocks',0,'Block',"$data[title]:$menulink[title]:All")) {
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
                $modid = xarMod::getRegID('roles');
                $modinfo = xarMod::getInfo($modid);
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
        if (xarSecurityCheck('AdminBaseBlock',0,'adminmenu',"$data[title]:All:All") or
            !xarUserIsLoggedIn() or
            empty($data['showlogout'])) {
            $showlogout = false;
        } else {
            $showlogout = true;
        }

        $marker         = isset($data['marker']) ? $data['marker'] : $this->marker;
        $displayrss     = isset($data['displayrss']) ? $data['displayrss'] :$this->displayrss;
        $displayprint   = isset($data['displayprint']) ? $data['displayprint'] : $this->displayprint;
        $printurl       = isset($data['printurl']) ? $data['printurl'] : $this->printurl;
        $rssurl         = isset($data['rssurl']) ? $data['rssurl'] : $this->rssurl;

        $data['content'] = array(
            'usermods'         => $usermods,
            'indlinks'         => $indlinks,
            'logouturl'        => $logouturl,
            'logoutlabel'      => $logoutlabel,
            'loggedin'         => $loggedin,
            'usercontent'      => $usercontent,
            'module'           => $thismodname,
            'marker'           => $marker,
            'showlogout'       => $showlogout,
            'where'            => $thismodname,
            'what'             => $thisfuncname,
            'displayrss'       => $displayrss,
            'displayprint'     => $displayprint,
            'printurl'         => $printurl,
            'rssurl'           => $rssurl
        );
        return $data;
    }

/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        if (empty($data['marker'])) $data['marker'] = $this->marker;
        if (empty($data['displaymodules'])) $data['displaymodules'] = $this->displaymodules;
        if (empty($data['modulelist'])) $data['modulelist'] = $this->modulelist;
        if (empty($data['displayrss'])) $data['displayrss'] = $this->displayrss;
        if (empty($data['displayprint'])) $data['displayprint'] = $this->displayprint;
        if (empty($data['content'])) $data['content'] = $this->content;
        if (empty($data['showlogout'])) $data['showlogout'] = $this->showlogout;

        // @CHECKME: is this used?
        if (empty($data['style'])) $data['style'] = 1;

/*        $data['modules'] = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('UserCapable' => 1, 'State' => XARMOD_STATE_ACTIVE)));
        // Prepare output array
        $c=0;
        if (!empty($data['content'])) {
            $contentlines = explode("LINESPLIT", $data['content']);
            $data['contentlines'] = array();
            foreach ($contentlines as $contentline) {
                $link = explode('|', $contentline);
                $data['contentlines'][] = $link;
                $c++;
            }
        }*/
        $data['view_access'] = isset($data['view_access']) ? $data['view_access'] : array();

        // @CHECKME: is this used?
        if (empty($data['lines'])) $data['lines'] = array($this->content);
        return $data;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);

        // Global options.
        if (!xarVarFetch('displaymodules', 'str:1',    $content['displaymodules'], $this->displaymodules, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('modulelist',     'str',      $content['modulelist'], $this->modulelist, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showlogout',     'checkbox', $content['showlogout'], $this->showlogout, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayrss',     'checkbox', $content['displayrss'], $this->displayrss, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayprint',   'checkbox', $content['displayprint'], $this->displayprint, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('marker',         'str:1',    $content['marker'], $this->marker, XARVAR_NOT_REQUIRED)) return;

        // User links.
        $content['lines'] = array();
        $c = 1;
        if (!xarVarFetch('name', 'list:str', $linkname, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!empty($linkname)) {
            if (!xarVarFetch('url',     'list:str',      $linkurl,  NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('description',    'list:str',      $linkdesc,  NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('visible', 'array', $linkvisible, NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('child',   'list:checkbox', $linkchild, NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('delete',  'list:checkbox', $linkdelete, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('insert',  'list:checkbox', $linkinsert, NULL, XARVAR_NOT_REQUIRED)) return;

            foreach ($linkname as $v) {
                if (!isset($linkdelete[$c]) || $linkdelete[$c] == false) {
                    $content['lines'][] = array(
                                    'url' => $linkurl[$c],
                                    'name' => $linkname[$c],
                                    'description' => $linkdesc[$c],
                                    'visible' => !empty($linkvisible[$c]) ? $linkvisible[$c] : 0,
                                    'child' => !empty($linkchild[$c]) ? $linkchild[$c] : 0,
                                );
                }
                if (!empty($linkinsert[$c])) {
                    $content[] = array();
                }
                $c++;
            }
        }

        if (!xarVarFetch('new_linkname', 'str', $new_linkname, '', XARVAR_NOT_REQUIRED)) return;
        if (!empty($new_linkname)) {
            if (!xarVarFetch('new_linkurl', 'str', $new_linkurl, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('new_linkdesc', 'str', $new_linkdesc, '', XARVAR_NOT_REQUIRED)) return;

            $content['lines'][] = array(
                            'url' => $new_linkurl,
                            'name' => $new_linkname,
                            'description' => $new_linkdesc,
                            'visible' => 1,
                            'child' => 0,
                        );
        }

        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        sys::import('modules.dynamicdata.class.properties.master');
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        $content['view_access'] = array();
        foreach ($modules as $module) {
            $isvalid = $accessproperty->checkInput('view_access_' . $module['name']);echo $isvalid;
            $content['view_access'][$module['name']] = $accessproperty->value;
        }

        $data['content'] = serialize($content);
        return $data;
    }
}
?>