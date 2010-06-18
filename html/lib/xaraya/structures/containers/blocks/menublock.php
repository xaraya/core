<?php
    // @TODO: see validations note in constructor
/**
 * MenuBlock class, default parent class for menu blocks
 *
 * @TODO: move all common menu functions here
**/
sys::import('xaraya.structures.containers.blocks.basicblock');

class MenuBlock extends BasicBlock implements iBlock
{
    public $module          = 'BlockModule';  // Module your child class belongs to
    public $text_type       = 'Menu Block';  // Block name
    public $text_type_long  = 'Parent class for menu blocks'; // Block description

    public $menumodtype     = 'user';       // type of module links we're dealing with
    public $menumodtypes    = array();      // optional array of valid modtypes
    public $xarmodules      = array();      // list of $menumodtype capable modules
    public $modulelist      = array();      // settings for $xarmodules list

    // store current request info as static properties
    public static $thismodname;
    public static $thismodtype;
    public static $thisfuncname;
    public static $currenturl;
    public static $truecurrenturl;

    public function __construct(Array $args=array())
    {
        parent::__construct($args);

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
                $this->modulelist[$modname]['aliases'] = !empty($aliases[$modname]) ? $aliases[$modname] : array();
            }
            // add aliases for module if aliases are in use
            if ((bool)xarModVars::get($modname, 'use_module_alias') && !empty($aliases[$modname]))
                $this->xarmodules[$key]['aliases'] = $aliases[$modname];
        }
        $this->content['modulelist'] = $this->modulelist;
    }

    public function display(Array $args=array())
    {
        self::setRequestInfo();
        $data = parent::display($args);
        return $data;
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

}
?>