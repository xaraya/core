<?php

/**
 * @package core\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

interface iBlockType
{
    public function __construct(array $blockinfo = []);

    // content getters and setters
    public function getContent();
    public function storeContent();
    // info methods
    public function getFileInfo();
    public function getConfiguration();
    public function getTypeInfo();
    public function getInstanceInfo();

    // can't declare these since their visibility is protected
    //function setContent(Array $content=array());
    //function setConfiguration();
    //function setTypeInfo();
    //function setInstanceInfo();

    // instance group handler methods
    public function attachGroup($block_id, $box_template = null, $block_template = null);
    public function detachGroup($block_id);
    public function updateGroup($block_id, $box_template = null, $block_template = null);
    public function getGroups();

    public function checkAccess($access);
}

/**
 * @package core\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
sys::import('xaraya.structures.descriptor');
abstract class BlockType extends ObjectDescriptor implements iBlockType
{
    // keep an internal count of all blocks instantiated
    private static $_count = 0;
    // All properties here have protected visibility to prevent anything
    // other than the blocks subsystem from setting them directly.
    protected $blockinfo; // args passed to constructor

    // Block type information, supplied by blocks subsystem (dbinfo)
    protected $type;
    protected $module;
    protected $type_id;
    protected $type_state;
    protected $type_version;

    protected $block_index; // (self::$_count);

    // Block instance information, supplied by blocks subsystem (dbinfo)
    protected $block_id;
    protected $name;
    protected $state = ixarBlock::BLOCK_STATE_VISIBLE;
    // Block instance information, supplied by blocks subsystem (dbinfo, or over-ridden by blocktag)
    protected $title;

    protected $group_instances = []; // instances belonging to the blockgroup

    // Block group instance information, supplied by block group types
    protected $group;
    protected $group_id;

    // templates
    protected $template_base;
    protected $block_template;
    protected $box_template;
    protected $tplmodule;

    // Block caching configuration, supplied by blocks subsystem (dbinfo, or over-ridden by blocktag)
    protected $nocache             = 0; // 0 = caching on; 1 = caching off;
    protected $pageshared          = 1; // 0 = No sharing; 1 = Share across pages;
    protected $usershared          = 0; // 0 = Cache for all users;1=Cache per user group;2=Cache per user;
    protected $cacheexpire         = null; // length of time before cached block is considered stale

    // stop showing (expire) block after x minutes
    // cfr. Base module HTML Block, now for any block(group) :)
    protected $expire              = 0;

    // Block access configuration, supplied by blocks subsystem (dbinfo)
    // @TODO: set appropriate defaults for each level
    protected $add_access          = ['group' => 0, 'level' => 100, 'failure' => 0];
    protected $display_access      = ['group' => 0, 'level' => 100, 'failure' => 0];
    protected $modify_access       = ['group' => 0, 'level' => 100, 'failure' => 0];
    protected $delete_access       = ['group' => 0, 'level' => 100, 'failure' => 0];
    protected static $access_property = null;

    // groups this block instance belongs to, handled by blocks subsystem
    protected $instance_groups = [];

    // anything we got from the db and not accounted for above is treated as content
    protected $content        = [];
    // blocks inheriting from this class must define their own public properties
    // the values of which will be stored in $content

    public function __construct(array $blockinfo = [])
    {
        $this->block_index = self::$_count++;
        // normalize blockinfo
        // store the original arguments
        $this->blockinfo = $blockinfo;
        // set type information
        $this->setTypeInfo();
        // set instance information
        $this->setInstanceInfo();
        // set configuration
        $this->setConfiguration();
        // set content
        $this->setContent();
        // move runUpgrade() and init() to BasicBlock constructor - see iBlock interface
    }

    final public function __get($p)
    {
        // this allows public read only access to protected properties :)
        // to keep things consistent $this->content is synonymous with $this->getContent();
        if ($p == 'content') {
            return $this->getContent();
        }
        $nullreturn = null;
        if (!isset($this->$p)) {
            return $nullreturn;
        }
        return $this->$p;
    }

    public function uniqueId()
    {
        $id = $this->type;
        if (!empty($this->module)) {
            $id = "{$this->module}_{$id}";
        }
        if (!empty($this->group)) {
            $id .= "_{$this->group}";
        }
        if (!empty($this->name)) {
            $id .= "_{$this->name}";
        }
        $id .= "_{$this->block_index}";
        return $id;
    }

    public function upgrade($oldversion)
    {
        return true;
    }

    /**
     * Store content
     * Returns an array of data prepped for storage in the db
    **/
    final public function storeContent()
    {
        $info = $this->getFileInfo();
        $info += $this->getConfiguration();
        $info += $this->getContent();
        return $info;
    }

    final public function getFileInfo()
    {
        $allowed = ['text_type', 'text_type_long', 'xarversion',
            'author', 'contact', 'credits', 'license', 'type_category'];
        $fileinfo = [];
        foreach ($allowed as $p) {
            $fileinfo[$p] = $this->$p ?? '';
        }
        return $fileinfo;
    }

    final public function getTypeInfo()
    {
        // We need to get the actual $classname and $filepath from getinfo() - requires UPGRADE due to table change
        $allowed = [
            'type', 'module', 'tid', 'type_id', 'type_state', 'classname', 'filepath',
        ];
        $info = [];
        foreach ($allowed as $p) {
            $info[$p] = $this->$p;
        }
        return $info;
    }

    final protected function setTypeInfo()
    {
        // set type info properties from dbinfo
        $allowed = array_keys($this->getTypeInfo());
        foreach ($this->blockinfo as $p => $v) {
            if (!in_array($p, $allowed)) {
                continue;
            }
            $this->$p = $v;
        }
        return true;
    }

    final public function getInstanceInfo()
    {
        $allowed = [
            'block_id', 'name', 'state', 'title', 'group_id', 'group', 'tplmodule',
        ];
        $info = [];
        foreach ($allowed as $p) {
            $info[$p] = $this->$p;
        }
        return $info;
    }

    final protected function setInstanceInfo()
    {
        // set instance info properties from dbinfo
        $allowed = array_keys($this->getInstanceInfo());
        foreach ($this->blockinfo as $p => $v) {
            if (!in_array($p, $allowed)) {
                continue;
            }
            $this->$p = $v;
        }
        return true;
    }

    final public function getConfiguration()
    {
        $allowed = [
            'nocache', 'pageshared', 'usershared', 'cacheexpire',
            'add_access', 'modify_access', 'delete_access', 'display_access',
            'expire', 'box_template', 'block_template', 'instance_groups',
            'show_preview', 'show_help', 'type_version',
        ];
        $info = [];
        foreach ($allowed as $p) {
            $info[$p] = $this->$p;
        }
        return $info;
    }

    final protected function setConfiguration()
    {
        $allowed = array_keys($this->getConfiguration());
        if (empty($this->block_id)) {
            // We assume this is a block tag: attributes overwrite the type_info (if they exist)
            $attributes = $this->blockinfo['content'] ?? [];
            if (!empty($this->blockinfo['type_info'])) {
                $content = $attributes + $this->blockinfo['type_info'];
            } else {
                $content = $attributes;
            }
        } else {
            // We assume a block created via the UI
            $content = $this->blockinfo['content'];
        }
        foreach ($content as $p => $v) {
            if (!in_array($p, $allowed)) {
                continue;
            }
            $this->$p = $v;
        }
        return true;
    }


    final public function getContent()
    {
        $disallowed = array_merge(
            // these are supposed to be protected, not public properties
            ['content', 'refresh', 'allow_multiple', 'localServiceCache', 'xarServices'],
            array_keys($this->getTypeInfo()),
            array_keys($this->getInstanceInfo()),
            array_keys($this->getConfiguration()),
            array_keys($this->getFileInfo())
        );
        $content = [];
        $properties = $this->getPublicProperties();
        foreach ($properties as $p => $v) {
            if (in_array($p, $disallowed)) {
                continue;
            }
            $content[$p] = $v;
        }
        return $content;
    }

    final public function setContent(array $content = [])
    {
        if (empty($content)) {
            if (empty($this->block_id)) {
                // We assume this is a block tag: attributes overwrite the type_info (if they exist)
                $attributes = $this->blockinfo['content'] ?? [];
                if (!empty($this->blockinfo['type_info'])) {
                    $content = $attributes + $this->blockinfo['type_info'];
                } else {
                    $content = $attributes;
                }
            } else {
                // We assume a block created via the UI
                $content = $this->blockinfo['content'];
            }
        }
        $allowed = array_keys($this->getContent());
        if (!empty($content)) {
            foreach ($content as $p => $v) {
                if (!in_array($p, $allowed)) {
                    continue;
                }
                $this->$p = $v;
            }
        }
        $this->content = $this->getContent();
        return true;
    }

    final public function setAccess($interface, $access)
    {
        $property = $interface . '_access';
        $this->$property = $access;
        return true;
    }

    // @param access (display|modify|delete)
    // this method is called by blocks_admin_modify|update|delete functions
    // and by xarBlock::render() method to determine access for current user
    // @return boolean true if access allowed
    public function checkAccess($access)
    {
        if (empty($access)) {
            throw new EmptyParameterException('Access method');
        }
        $access_method = $access . '_access';
        $access = $this->$access_method
            ?? ['group' => 0, 'level' => 100, 'failure' => 0];
        // Decide whether this block is displayed to the current user
        $args = [
            'module' => $this->module,
            'component' => 'Block',
            'instance' => $this->type . ":" . $this->name . ":" . $this->bid,
            'group' => $access['group'],
            'level' => $access['level'],
        ];
        /** @var AccessProperty $access_property */
        static $access_property;
        if (!isset($access_property)) {
            sys::import('modules.dynamicdata.class.properties.master');
            $access_property = DataPropertyMaster::getProperty(['name' => 'access']);
        }
        return $access_property->check($args);
    }

    final public function attachGroup($block_id, $box_template = null, $block_template = null)
    {
        return $this->updateGroup($block_id, $box_template, $block_template);
    }

    final public function updateGroup($block_id, $box_template = null, $block_template = null)
    {
        $this->instance_groups[$block_id] = [
            'box_template' => $box_template,
            'block_template' => $block_template,
        ];
        return true;
    }

    final public function detachGroup($block_id)
    {
        if (isset($this->instance_groups[$block_id])) {
            unset($this->instance_groups[$block_id]);
        }
        return true;
    }

    final public function getGroups()
    {
        return $this->instance_groups;
    }

    /**
     * Public access to protected properties that can be over-ridden
    **/
    final public function setTemplateBase($template)
    {
        $this->template_base = $template;
    }
    final public function setBlockTemplate($template)
    {
        $this->block_template = $template;
    }
    final public function setBoxTemplate($template)
    {
        $this->box_template = $template;
    }
    final public function setExpire($timestamp = 0)
    {
        $this->expire = $timestamp;
    }
    final public function setTitle($title = '')
    {
        $this->title = $title;
    }
    final public function setNoCache($nocache)
    {
        $this->nocache = (bool) $nocache;
    }
    final public function setPageShared($pageshared)
    {
        $this->pageshared = (bool) $pageshared;
    }
    final public function setUserShared($usershared)
    {
        $this->usershared = $usershared;
    }
    final public function setCacheExpire($cacheexpire = null)
    {
        $this->cacheexpire = $cacheexpire;
    }
}
