<?php
/**
 * Base block management
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
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class AdminMenuBlock extends BasicBlock implements iBlock
{
    public $nocache             = 1;

    public $name                = 'AdminMenuBlock';
    public $module              = 'base';
    public $text_type           = 'Admin Menu';
    public $text_type_long      = 'Displays Admin Menu';
    public $allow_multiple      = true;

    public $showlogout          = 1;
    public $menustyle           = 'bycat';
    public $showhelp            = 0;
    public $showfront           = 1;

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;
        $vars = isset($data['content']) ? $data['content'] : array();
        if (!isset($vars['showlogout'])) $vars['showlogout'] = $this->showlogout;
        if (!isset($vars['menustyle'])) $vars['menustyle'] = $this->menustyle;
        if (!isset($vars['showhelp'])) $vars['showhelp'] = $this->showhelp;
        if (!isset($vars['showfront'])) $vars['showfront'] = $this->showfront;

        // are there any admin modules, then get the whole list sorted by names
        // checking this as early as possible
        $mods = xarMod::apiFunc('modules', 'admin', 'getlist',
            array('filter' => array('AdminCapable' => true)));

        // which module is loaded atm?
        // we need it's name, type and function - dealing only with admin type mods, aren't we?
        list($thismodname, $thismodtype, $thisfuncname) = xarRequest::getInfo();

        // SETTING 1: Show a logout link in the block?
        $showlogout = false;
        if(isset($vars['showlogout']) && $vars['showlogout']) $showlogout = true;
        /// Show a help link
        $showhelp = false;
        if(isset($vars['showhelp'])&& $vars['showhelp']) $showhelp =true;
        // Show a link to front end?
        $showfront = false;
        if(isset($vars['showfront']) && $vars['showfront']) $showfront = true;

        // SETTING 2: Menustyle
        if(!isset($vars['menustyle'])) {
            // If it is not set, revert to the default setting
            $vars['menustyle'] = xarModVars::get('modules', 'menustyle');
        }

        // Get current URL for later comparisons because we need to compare
        // xhtml compliant url, we fetch the default 'XML'-formatted URL.
        $currenturl = xarServer::getCurrentURL();

        // Admin types
        // FIXME: this is quite ad-hoc here
        $admintypes = array('admin', 'util');

        switch(strtolower($vars['menustyle'])){
            case 'byname': // display by name
                foreach($mods as $mod) {
                    $modname = $mod['name'];
                    if ((bool)xarModVars::get($modname, 'admin_menu_link')) continue;
                    $labelDisplay = $mod['displayname'];
                    // get URL to module's main function
                    $link = xarModURL($modname, 'admin', 'main', array());
                    if (!xarSecurityCheck('ViewBlock',0,'BlockItem',$data['name']. ":" . $mod['name'])) {
                        $adminmods[$modname]['features'] = array();
                        continue;
                    }
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

                        $adminmods[$modname]['features']['modactive'] = 1;
                        // adding attributes and flags to each module link for the template
                        if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                            $adminmods[$modname]['features']['overview'] = 1;
                            $adminmods[$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                        }

                        // For active module we need to display the mod functions links
                        // call the api function to obtain function links, but don't raise an exception if it's not there
                        try {
                            $menulinks = xarMod::apiFunc($modname, 'admin', 'getmenulinks', array());
                        } catch (FunctionNotFoundException $e) {
                            // try for presence of an adminmenu-dat file for this module
                            // the menuarray function returns an empty array or menulinks for the module
                            $menulinks = xarMod::apiFunc('base','admin','menuarray',array('module' => $modname));
                        }
                        if (!empty($menulinks) && is_array($menulinks)) {
                            foreach($menulinks as $menulink) {
                                $adminmods[$modname]['indlinks'][] = array(
                                    'adminlink'     => $menulink['url'],
                                    'adminlabel'    => $menulink['label'],
                                    'admintitle'    => $menulink['title'],
                                    'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                                );
                            }
                            unset($menulinks);
                        }
                    } // if
                } // foreach

                $template = 'verticallistbyname';
                $vars['adminmods'] = $adminmods;
                break;

            default:
            case 'bycat': // sort by categories
                // <mrb> for the release we can do without the adminmenu table, if
                // that gains functionality consider putting it back.
                foreach ($mods as $mod) {
                    // get URL to module's main function
                    $modname=$mod['name'];
                    if ((bool)xarModVars::get($modname, 'admin_menu_link')) continue;
                    $link = xarModURL($modname, 'admin', 'main', array());
                    $labelDisplay = $mod['displayname'];
                    if(!isset($mod['category']) or $mod['category'] == '0') {
                        $mod['category'] = xarML('Unknown');
                    }
                    $cat = xarVarPrepForDisplay($mod['category']);

                    // if this module is loaded we probably want to display it with -current css rule in the menu
                    if (!xarSecurityCheck('ViewBlock',0,'BlockItem',$data['name']. ":" . $mod['name'])) {
                        $catmods[$cat][$modname]['features'] = array();
                        continue;
                    }
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
                        try {
                            $menulinks = xarMod::apiFunc($modname, 'admin', 'getmenulinks', array());
                        } catch (FunctionNotFoundException $e) {
                            // try for presence of an adminmenu-dat file for this module
                            // the menuarray function returns an empty array or menulinks for the module
                            $menulinks = xarMod::apiFunc('base','admin','menuarray',array('module' => $modname));
                        }
                        if (!empty($menulinks) && is_array($menulinks)) {
                            foreach($menulinks as $menulink) {
                                $catmods[$cat][$modname]['indlinks'][] = array(
                                    'adminlink'     => $menulink['url'],
                                    'adminlabel'    => $menulink['label'],
                                    'admintitle'    => $menulink['title'],
                                    'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                                );
                            }
                            unset($menulinks);
                        }
                    } else {
                       // Why is this needed?
                       unset($mod['displayname']);
                    }
                } //inner foreach

                $template = 'verticallistbycats';
                ksort($catmods);
                $vars['catmods'] = $catmods;
                break;
        }

        //making a few assumptions here for now about modname and directory
        //very rough - but let's use what we have for now
        //Leave way open for real help system
        //TODO : move any final help functions to some module or api when decided

    if (file_exists(sys::code() . 'modules/'.$thismodname.'/xaradmin/overview.php')) {
            if ($thisfuncname<>'overview' && $thisfuncname<>'main') {
            $overviewlink = xarModURL($thismodname,'admin','overview',array(),NULL,$thisfuncname);
            } else {
            $overviewlink = xarModURL($thismodname,'admin','overview');
            }
        } else { //no overview exists;
        $overviewlink = xarModURL('base','admin','overview',array('template'=>'nooverview'));
        }

        $vars['overviewlink']=$overviewlink;
        // Set template base.
        // FIXME: not allowed to set private variables of BL directly
        $data['_bl_template_base'] = $template;
        $data['content'] = $vars;
        return $data;
    }
}
?>