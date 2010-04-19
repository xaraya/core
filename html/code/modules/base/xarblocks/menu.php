<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
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

class Base_MenuBlock extends BasicBlock implements iBlock
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
    public $user_content             = array(
                                    'url' => '[base]&page=docs',
                                    'name'=> 'Documentation',
                                    'description' => 'General Documentation',
                                    'visible' => true,
                                    );
    public $showlogout          = true;
    public $showback            = true;

    public $rssurl;
    public $printurl;

    public function __construct(Array $data=array())
    {
        parent::__construct($data);
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
        //if (!$data['allowaccess']) return;
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
        if (empty($data['lines'])) $data['lines'] = array($this->user_content);
        if (!isset($data['showback'])) $data['showback'] = $this->showback;
        if (!isset($data['showlogout'])) $data['showlogout'] = $this->showlogout;

        // which module is loaded atm?
        // we need it's name, type and function - dealing only with user type mods, aren't we?
        // This needs to be deprecated for multi-modules setups later on
//        list($thismodname, $thismodtype, $thisfuncname) = xarController::$request->getInfo();
        $thismodname = xarController::$request->getModule();
        $thismodtype = xarController::$request->getType();
        $thisfuncname = xarController::$request->getFunction();
        // Sort Order, Status, Common Labels and Links Display preparation

        $authmoduledata = xarMod::apiFunc('roles','user','getdefaultauthdata');
        $authmodlogout = $authmoduledata['defaultloginmodname'];
        if (xarSecurityCheck('AdminBaseBlock',0,'adminmenu',"$data[title]:All:All")) {
            $logouturl = xarModURL('base', 'admin', 'confirmlogout');
        } else {
            $logouturl = xarModURL($authmodlogout,'user', 'logout', array());
        }
        $logoutlabel = xarVarPrepForDisplay(xarML('Logout'));
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
                        // @CHECKME: Q1) Does anybody use these?
                        // @CHECKME: Q2) As non-core modules do they belong here?
                        // @TODO: Get answers to Q1 and Q2 :-P ; Figure out a friendlier syntax
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
            $access = isset($data['view_access']) ? $data['view_access'] : array();
            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            foreach($mods as $mod){
                if (isset($access[$mod['name']])) {
                    // Decide whether this menu item is displayable to the current user
                    $args = array(
                        'module' => 'base',
                        'component' => 'Block',
                        'instance' => $data['title'] . "All:All",
                        'group' => $access[$mod['name']]['group'],
                        'level' => $access[$mod['name']]['level'],
                    );
                    if (!$accessproperty->check($args)) continue;
                }

                if ((bool)xarModVars::get($mod['name'], 'user_menu_link')) continue;

                /* Check for active module alias */
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

                            $indlinks[] = array('userlink'      => $menulink['url'],
                                                'userlabel'     => $menulink['label'],
                                                'usertitle'     => $menulink['title'],
                                                'funcactive'    => $funcactive);
                            }
                    } else {
                        $indlinks= '';
                    }

                } else {
                    $labelDisplay = $label;
                    $usermods[] = array('label' => $labelDisplay,
                                        'link' => $link,
                                        'desc' => $title,
                                        'modactive' => 0);
                }
            }
            if (empty($usermods)) $usermods = '';
        } else {
            $usermods = '';
        }

        // prepare the data for template(s)
        $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
        if (empty($indlinks)){
            $indlinks = '';
        }

        if (!$loggedin || empty($data['showlogout'])) {
            $showlogout = false;
        } else {
            $showlogout = true;
        }
        // optionally show a link to the back end for admins
        if (!empty($data['showback'])) {
            $showback = true;
        } else {
            $showback = false;
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
            'rssurl'           => $rssurl,
            'showback'         => $showback,
        );
        return $data;
    }        
}
?>
