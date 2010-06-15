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
// Inherit properties from MenuBlock class
sys::import('xaraya.structures.containers.blocks.menublock');

class Base_AdminmenuBlock extends MenuBlock implements iBlock
{
    public $name                = 'AdminMenuBlock';
    public $module              = 'base';
    public $text_type           = 'Admin Menu';
    public $text_type_long      = 'Displays Admin Menu';
    public $allow_multiple      = true;
    public $nocache             = 1;

    public $showlogout          = 1;
    public $menustyle           = 'bycat';
    //public $showhelp            = 0; <chris> remove this unused property for now
    public $showfront           = 1;

    public $modulelist;

    protected $adminmodules     = array();

    public function __construct(Array $data=array())
    {
        parent::__construct($data);
        // all methods require the list of active admin capable modules, set it early
        $this->adminmodules = xarMod::apiFunc('modules','admin','getlist',
            array('filter' => array('AdminCapable' => true, 'State' => XARMOD_STATE_ACTIVE)));
        // add any missing modules to the modulelist
        // eg, at first run of this block instance or when new modules are added to the system
        foreach ($this->adminmodules as $mod) {
            if (!isset($this->modulelist[$mod['name']]))
                $this->modulelist[$mod['name']]['visible'] = 1;
        }
        if (empty($this->modulelist)) {
            // if the modulelist is empty, admin deselected all modules, put back the modules module
            // @CHECKME: put back the blocks module too so we can edit this?
            $this->modulelist = array('modules' => array('visible' => 1));
        }
        // make sure we keep the content array in sync
        $this->content['modulelist'] = $this->modulelist;
    }

/**
 * Display func.
 * @param $data array containing title,content
 */
    function display(Array $data=array())
    {
        $data = parent::display($data);
        if (empty($data)) return;

        $vars = isset($data['content']) ? $data['content'] : array();

        // Admin types
        // FIXME: this is quite ad-hoc here
        // CHECKME: is this even relevent? links are still supplied by admin getmenulinks/adminmenu-dat.xml
        $admintypes = array('admin', 'util');

        foreach ($this->adminmodules as $mod) {
            $modname = $mod['name'];
            // @TODO: deprecate this
            if ((bool)xarModVars::get($modname, 'admin_menu_link')) continue;
            // Use this instead :)
            if (empty($vars['modulelist'][$modname]['visible'])) continue;

            // Check for active module alias
            $useAliasName = xarModVars::get($modname, 'use_module_alias');
            $module_alias_name = xarModVars::get($modname,'module_alias_name');

            // use the alias name if it exists
            if (isset($useAliasName) && $useAliasName==1 && isset($module_alias_name) && !empty($module_alias_name)) {
                $displayname = $module_alias_name;
            } else {
                $displayname = $mod['displayname'];
            }

            // get menu links if module is active
            if ($modname == self::$thismodname && in_array(self::$thismodtype, $admintypes)) {
                $menulinks = $this->getMenuLinks(
                    array(
                        'modname' => $modname,
                        'modtype' => 'admin', // make sure we get admin menu links
                    ));
                $isactive = true;
            } else {
                $menulinks = array();
                $isactive = false;
            }

            switch ($vars['menustyle']) {
                case 'bycat':
                default:
                    // determine category
                    if(!isset($mod['category']) or $mod['category'] == '0') {
                        $mod['category'] = xarML('Unknown');
                    }
                    $cat = xarVarPrepForDisplay($mod['category']);
                    // add module link to category
                    $categories[$cat][$modname] = array(
                        'label' => $displayname,
                        'url' => xarModURL($modname, 'admin', 'main', array()),
                        'title' => xarML('Show administration options for module #(1)', $displayname),
                        'isactive' => $isactive,
                    );
                    // add module menulinks to category (if any)
                    $categories[$cat][$modname]['menulinks'] = $menulinks;
                break;
                case 'byname':
                    // add module link to adminmods
                    $adminmods[$modname] = array(
                        'label' => $displayname,
                        'url' => xarModURL($modname, 'admin', 'main', array()),
                        'title' => xarML('Show administration options for module #(1)', $displayname),
                        'isactive' => $isactive,
                    );
                    // add module menulinks to adminmods (if any)
                    $adminmods[$modname]['menulinks'] = $menulinks;
                break;
            }
        }

        switch ($vars['menustyle']) {
            case 'byname':
                $vars['adminmods'] = $adminmods;
                $template = 'verticallistbyname';
            break;
            case 'bycat':
                ksort($categories);
                $vars['catmods'] = $categories;
                $template = 'verticallistbycats';
            break;
        }

        //making a few assumptions here for now about modname and directory
        //very rough - but let's use what we have for now
        //Leave way open for real help system
        //TODO : move any final help functions to some module or api when decided
        /* <chris> removing this for now as it isn't used anywhere
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
        */

        // Set template base.
        // FIXME: not allowed to set private variables of BL directly
        $data['_bl_template_base'] = $template;
        $data['content'] = $vars;

        return $data;
    }
}
?>
