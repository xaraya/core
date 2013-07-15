<?php
/**
 * @package core
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
    // @TODO: see validations note in constructor
/**
 * MenuBlock class, default parent class for menu blocks
 *
 * @TODO: move all common menu functions here
**/
sys::import('xaraya.structures.containers.blocks.basicblock');

abstract class MenuBlock extends BasicBlock implements iBlock
{

    protected $module          = '';  // Module your child class belongs to
    protected $text_type       = 'Menu Block';  // Block name
    protected $text_type_long  = 'Parent class for menu blocks'; // Block description

    protected $menumodtype     = 'user';       // type of module links we're dealing with
    protected $menumodtypes    = array();      // optional array of valid modtypes
    protected $xarmodules      = array();      // list of $menumodtype capable modules

    // store current request info as static properties
    public static $thismodname;
    public static $thismodtype;
    public static $thisfuncname;
    public static $currenturl;
    public static $truecurrenturl;
    
    public $modulelist      = array();      // settings for $xarmodules list

    public function init()
    {
        $typeCapable = ucfirst($this->menumodtype) . 'Capable';
        // get the list of modules for this menu modtype
        $this->xarmodules = xarMod::apiFunc('modules','admin','getlist',
            array('filter' => array($typeCapable => true, 'State' => XARMOD_STATE_ACTIVE)));
        // get module aliases while we're here, we need those too
        $aliasMap = xarConfigVars::get(null,'System.ModuleAliases');
        $aliases = array();
        if (!empty($aliasMap)) {
            foreach ($aliasMap as $alias => $modname) {
                $aliases[$modname][$alias] = array('id' => $alias, 'name' => $alias);
            }
        }
        // replace old menu blocks modulelist property default with new default
        if (empty($this->modulelist) && !is_array($this->modulelist)) {
            $this->modulelist = array();
        }
        // sync the modulelist with xarmodules
        foreach ($this->xarmodules as $key => $mod) {
            $modname = $mod['name'];
            // add new modules to the modlist
            if (is_array($this->modulelist)) {
                if (!isset($this->modulelist[$modname])) {
                    $this->modulelist[$modname] = array(
                        'visible' => 1,
                        'alias_name' => $modname,
                        'view_access' => array('group' => 0, 'level' => 100, 'failure' => 0),
                    );
                }
                // add aliases for module if aliases are in use
                if ((bool)xarModVars::get($modname, 'use_module_alias') && !empty($aliases[$modname])) {
                    $this->modulelist[$modname]['aliases'] = $aliases[$modname];
                } else {
                    $this->modulelist[$modname]['aliases'] = array();
                }
                // add in some other useful info about the module
                $this->modulelist[$modname]['modname'] = $modname;
                $this->modulelist[$modname]['displayname'] = $mod['displayname'];
                $this->modulelist[$modname]['displaydescription'] = $mod['displaydescription'];
            }
        }
        self::setRequestInfo();
    }

    public function setRequestInfo()
    {
        if (!isset(self::$thismodname) || !isset(self::$thismodtype) || !isset(self::$thisfuncname)) {
            // set current request info properties
            list(self::$thismodname, self::$thismodtype, self::$thisfuncname) = xarController::$request->getInfo();
        }
        if (!isset(self::$currenturl))
            self::$currenturl = xarServer::getCurrentURL();
        if (!isset(self::$truecurrenturl))
            self::$truecurrenturl = xarServer::getCurrentURL(array(), false);
    }

/**
 * Get a module link for display
 *
 * @param array $link array of link information, required
 * @param bool $expand force loadmenuarray (default false)
 * @return mixed bool false if no modname, or link isn't visible, array of link info on success
**/
    protected function getModuleLink($link, $expand=false)
    {
        if (empty($link['modname']) || empty($link['visible']) || (bool)xarModVars::get($link['modname'], $this->menumodtype . '_menu_link')) return;

        $modname = $link['modname'];
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        // check access defined in the module list
        if (!empty($this->modulelist[$modname]['view_access'])) {
            // Decide whether this menu item is displayable to the current user
            $args = array(
                'module' => 'base',
                'component' => 'Block',
                'instance' => $this->title . "All:All",
                'group' => $this->modulelist[$modname]['view_access']['group'],
                'level' => $this->modulelist[$modname]['view_access']['level'],
            );
            if (!$accessproperty->check($args)) return false;
        }
        if (!empty($this->modulelist[$modname]['alias_name']) && empty($link['label'])) {
            $aliasname = $this->modulelist[$modname]['alias_name'];
            if (isset($this->modulelist[$modname]['aliases'][$aliasname])) {
                $link['label'] = $aliasname;
            }
        }
        if (empty($link['label'])) {
            $link['label'] = $this->modulelist[$modname]['displayname'];
        }
        if (empty($link['title'])) {
            $link['title'] = $this->modulelist[$modname]['displaydescription'];
        }
        $link['url'] = xarModURL($modname, $this->menumodtype, 'main', array());
        if ($link['url'] == self::$currenturl) $link['url'] = '';

        if (empty($link['name'])) {
            $link['name'] = $modname . '_' . $this->menumodtype;
        }

        // see if module is active
        $isactive = ($modname == self::$thismodname &&
                    (self::$thismodtype == $this->menumodtype || !empty($this->menumodtypes) && in_array(self::$thismodtype, $this->menumodtypes)));
        $menulinks = array();
        // get menulinks if module is active or calling function requested expand(ed) list
        if ($isactive || $expand) {
            $menulinks = xarMod::apiFunc('base', 'admin', 'loadmenuarray',
                array(
                    'modname' => $modname,
                    'modtype' => $this->menumodtype, // make sure we get correct type of menu links
                ));
        }
        $link['menulinks'] = $menulinks;
        $link['isactive'] = $isactive;
        $link['ismodlink'] = 1;

        return $link;
    }
}
?>