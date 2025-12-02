<?php

/**
 * @package core\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
// @TODO: see validations note in constructor
/**
 * MenuBlock class, default parent class for menu blocks
 *
 * @TODO: move all common menu functions here
**/
// use ixarMod;

abstract class MenuBlock extends BasicBlock implements iBlock
{
    protected $module          = '';  // Module your child class belongs to
    protected $text_type       = 'Menu Block';  // Block name
    protected $text_type_long  = 'Parent class for menu blocks'; // Block description

    protected $menumodtype     = 'user';       // type of module links we're dealing with
    protected $menumodtypes    = [];      // optional array of valid modtypes
    protected $xarmodules      = [];      // list of $menumodtype capable modules

    // store current request info as instance properties
    public $thismodname;
    public $thismodtype;
    public $thisfuncname;
    public $currenturl;
    public $truecurrenturl;

    public $modulelist      = [];      // settings for $xarmodules list
    /** @var ?AccessProperty */
    protected $accessProperty = null;

    public function init()
    {
        $typeCapable = ucfirst($this->menumodtype) . 'Capable';
        // get the list of modules for this menu modtype
        $this->xarmodules = $this->mod()->apiFunc(
            'modules',
            'admin',
            'getlist',
            ['filter' => [$typeCapable => true, 'State' => ixarMod::STATE_ACTIVE]]
        );
        // get module aliases while we're here, we need those too
        $aliasMap = $this->config()->getVar('System.ModuleAliases');
        $aliases = [];
        if (!empty($aliasMap)) {
            foreach ($aliasMap as $alias => $modname) {
                $aliases[$modname][$alias] = ['id' => $alias, 'name' => $alias];
            }
        }
        // replace old menu blocks modulelist property default with new default
        if (empty($this->modulelist) && !is_array($this->modulelist)) {
            $this->modulelist = [];
        }
        // sync the modulelist with xarmodules
        foreach ($this->xarmodules as $key => $mod) {
            $modname = $mod['name'];
            // add new modules to the modlist
            if (is_array($this->modulelist)) {
                if (!isset($this->modulelist[$modname])) {
                    $this->modulelist[$modname] = [
                        'visible' => 1,
                        'alias_name' => $modname,
                        'view_access' => ['group' => 0, 'level' => 100, 'failure' => 0],
                    ];
                }
                // add aliases for module if aliases are in use
                if ((bool) $this->mod($modname)->getVar('use_module_alias') && !empty($aliases[$modname])) {
                    $this->modulelist[$modname]['aliases'] = $aliases[$modname];
                } else {
                    $this->modulelist[$modname]['aliases'] = [];
                }
                // add in some other useful info about the module
                $this->modulelist[$modname]['modname'] = $modname;
                $this->modulelist[$modname]['displayname'] = $mod['displayname'];
                $this->modulelist[$modname]['displaydescription'] = $mod['displaydescription'];
            }
        }
        $this->setRequestInfo();
    }

    public function setRequestInfo()
    {
        if (!isset($this->thismodname) || !isset($this->thismodtype) || !isset($this->thisfuncname)) {
            // set current request info properties
            [$this->thismodname, $this->thismodtype, $this->thisfuncname] = $this->req()->getRequest()->getInfo();
        }
        if (!isset($this->currenturl)) {
            $this->currenturl = $this->ctl()->getCurrentURL();
        }
        if (!isset($this->truecurrenturl)) {
            $this->truecurrenturl = $this->ctl()->getCurrentURL([], false);
        }
    }

    /**
     * Get a module link for display
     *
     * @param array<mixed> $link array of link information, required
     * @param bool $expand force loadmenuarray (default false)
     * @return mixed bool false if no modname, or link isn't visible, array of link info on success
    **/
    protected function getModuleLink($link, $expand = false)
    {
        if (empty($link['modname']) || empty($link['visible']) || (bool) $this->mod($link['modname'])->getVar($this->menumodtype . '_menu_link')) {
            return false;
        }

        $modname = $link['modname'];
        // check access defined in the module list
        if (!empty($this->modulelist[$modname]['view_access'])) {
            $this->accessProperty ??= $this->prop()->getProperty(['name' => 'access']);
            // Decide whether this menu item is displayable to the current user
            $args = [
                'module' => 'base',
                'component' => 'Block',
                'instance' => $this->title . "All:All",
                'group' => $this->modulelist[$modname]['view_access']['group'],
                'level' => $this->modulelist[$modname]['view_access']['level'],
            ];
            if (!$this->accessProperty->check($args)) {
                return false;
            }
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
        $link['url'] = $this->ctl()->getModuleURL($modname, $this->menumodtype, 'main', []);
        if ($link['url'] == $this->currenturl) {
            $link['url'] = '';
        }

        if (empty($link['name'])) {
            $link['name'] = $modname . '_' . $this->menumodtype;
        }

        // see if module is active
        $isactive = ($modname == $this->thismodname
                    && ($this->thismodtype == $this->menumodtype || !empty($this->menumodtypes) && in_array($this->thismodtype, $this->menumodtypes)));
        $menulinks = [];
        // get menulinks if module is active or calling function requested expand(ed) list
        if ($isactive || $expand) {
            $menulinks = $this->mod()->apiFunc(
                'base',
                'admin',
                'loadmenuarray',
                [
                    'modname' => $modname,
                    'modtype' => $this->menumodtype, // make sure we get correct type of menu links
                ]
            );
        }
        $link['menulinks'] = $menulinks;
        $link['isactive'] = $isactive;
        $link['ismodlink'] = 1;

        return $link;
    }
}
