<?php
interface iBlockType
{
    function __construct(Array $blockinfo=array());

    // content getters and setters 
    function getContent();
    function storeContent();
    // info methods 
    function getFileInfo();
    function getConfiguration();
    function getTypeInfo();
    function getInstanceInfo();

    // can't declare these since their visibility is protected
    //function setContent(Array $content=array());    
    //function setConfiguration();
    //function setTypeInfo();
    //function setInstanceInfo();
    
    // instance group handler methods    
    function attachGroup($block_id, $box_template=null, $block_template=null);
    function detachGroup($block_id);
    function updateGroup($block_id, $box_template=null, $block_template=null);
    function getGroups();  

    function checkAccess($access);
}

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
    protected $state = xarBlock::BLOCK_STATE_VISIBLE;
    // Block instance information, supplied by blocks subsystem (dbinfo, or over-ridden by blocktag)
    protected $title;

    protected $group_instances = array(); // instances belonging to the blockgroup    
    
    // Block group instance information, supplied by block group types 
    protected $group;
    protected $group_id;
    
    // templates
    protected $template_base;
    protected $block_template;
    protected $box_template;

    // Block caching configuration, supplied by blocks subsystem (dbinfo, or over-ridden by blocktag) 
    protected $nocache             = 0; // 0 = caching on; 1 = caching off;
    protected $pageshared          = 1; // 0 = No sharing; 1 = Share across pages;
    protected $usershared          = 0; // 0 = Cache for all users;1=Cache per user group;2=Cache per user;
    protected $cacheexpire         = NULL; // length of time before cached block is considered stale

    // stop showing (expire) block after x minutes
    // cfr. Base module HTML Block, now for any block(group) :)
    protected $expire              = 0;

    // Block access configuration, supplied by blocks subsystem (dbinfo) 
    // @TODO: set appropriate defaults for each level
    protected $add_access          = array('group' => 0, 'level' => 100, 'failure' => 0);
    protected $display_access      = array('group' => 0, 'level' => 100, 'failure' => 0);
    protected $modify_access       = array('group' => 0, 'level' => 100, 'failure' => 0);
    protected $delete_access       = array('group' => 0, 'level' => 100, 'failure' => 0);
    protected static $access_property = null;

    // groups this block instance belongs to, handled by blocks subsystem 
    protected $instance_groups = array(); 

    // anything we got from the db and not accounted for above is treated as content
    protected $content        = array();    
    // blocks inheriting from this class must define their own public properties
    // the values of which will be stored in $content

    final public function __construct(Array $blockinfo=array())
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
        // check for upgrade and run if necessary
        $this->runUpgrade();
        // run any additional initialisation supplied by this block type
        if (xarBlock::hasMethod($this, 'init', true))
            $this->init();
    }
/**
 * init
 * @params none
 * @return void
**/
    // NOTE: since the constructor cannot be overloaded, this method
    // is called by the constructor to run any additional functions
    // specific to this type immediately after the object is initialised     
    public function init()
    {
    }


    final protected function runUpgrade()
    {
        if ($this->xarversion != $this->type_version && xarBlock::hasMethod($this, 'upgrade', true)) {
            if (!empty($this->type_version)) {
                sys::import('xaraya.version');
                if (xarVersion::compare($this->type_version, $this->xarversion, 3) >= 0) {
                    // 1st version is bigger, can't downgrade blocks
                    throw new Exception();
                }
            }
            if (!$this->upgrade($this->type_version)) {
                // upgrade failed
                throw new Exception();
            }
        } 
        $this->type_version = $this->xarversion;
        return true;        
    }

    final public function __get($p)
    {
        // this allows public read only access to protected properties :)
        // to keep things consistent $this->content is synonymous with $this->getContent();
        if ($p == 'content') 
            return $this->getContent();
        $nullreturn = null;
        if (!isset($this->$p))
            return $nullreturn;
        return $this->$p;
    }

    public function uniqueId()
    {
        $id = $this->type;
        if (!empty($this->module))
            $id = "{$this->module}_{$id}";
        if (!empty($this->group))
            $id .= "_{$this->group}";
        if (!empty($this->name))
            $id .= "_{$this->name}";
        $id .= "_{$this->block_index}";
        return $id;
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
        $allowed = array('text_type', 'text_type_long', 'xarversion',
                            'author', 'contact', 'credits', 'license', 'type_category');
        $fileinfo = array();        
        foreach ($allowed as $p) 
            $fileinfo[$p] = isset($this->$p) ? $this->$p : ''; 
        return $fileinfo; 
    }

    final public function getTypeInfo()
    {
        $allowed = array(
            'type', 'module', 'tid', 'type_id', 'type_state',
        );
        $info = array();
        foreach ($allowed as $p)
            $info[$p] = $this->$p;
        return $info;    
    }

    final protected function setTypeInfo()
    {
        // set type info properties from dbinfo
        $allowed = array_keys($this->getTypeInfo());
        foreach ($this->blockinfo as $p => $v) {
            if (!in_array($p, $allowed)) continue;
            $this->$p = $v;
        }
        return true;
    }

    final public function getInstanceInfo()
    {
        $allowed = array(
            'block_id', 'name', 'state', 'title', 'group_id', 'group', 
        ); 
        $info = array();
        foreach ($allowed as $p)
            $info[$p] = $this->$p;
        return $info;  
    }

    final protected function setInstanceInfo()
    {
        // set instance info properties from dbinfo
        $allowed = array_keys($this->getInstanceInfo());
        foreach ($this->blockinfo as $p => $v) {
            if (!in_array($p, $allowed)) continue;
            $this->$p = $v;
        }
        return true;    
    }

    final public function getConfiguration()
    {
        $allowed = array(
            'nocache', 'pageshared', 'usershared', 'cacheexpire', 
            'add_access', 'modify_access', 'delete_access', 'display_access',
            'expire', 'box_template', 'block_template', 'instance_groups',
            'show_preview', 'show_help', 'type_version'
        );
        $info = array();
        foreach ($allowed as $p)
            $info[$p] = $this->$p;
        return $info;
    }
    
    final protected function setConfiguration()
    {
        $allowed = array_keys($this->getConfiguration());
        if (empty($this->block_id)) { 
            $content = !empty($this->blockinfo['type_info']) ? $this->blockinfo['type_info'] : array();
        } else {
            $content = $this->blockinfo['content'];
        }
        foreach ($content as $p => $v) {
            if (!in_array($p, $allowed)) continue;
            $this->$p = $v;
        }
        return true;       
    }

    
    final public function getContent()
    {
        $disallowed = array_merge(
            array('content', 'refresh', 'allow_multiple'),
            array_keys($this->getTypeInfo()),
            array_keys($this->getInstanceInfo()),
            array_keys($this->getConfiguration()),
            array_keys($this->getFileInfo())
        );
        $content = array();
        $properties = $this->getPublicProperties();
        foreach ($properties as $p => $v) {
            if (in_array($p, $disallowed)) continue; 
            $content[$p] = $v;
        }
        return $content;
    }
    
    final public function setContent(Array $content=array())
    {
        if (empty($content)) {
            if (empty($this->block_id)) { 
                $content = !empty($this->blockinfo['type_info']) ? $this->blockinfo['type_info'] : array();
            } else {
                $content = $this->blockinfo['content'];
            }
        }
        $allowed = array_keys($this->getContent());
        if (!empty($content)) {
            foreach ($content as $p => $v) {
                if (!in_array($p, $allowed)) continue;
                $this->$p = $v;
            }
        }
        $this->content = $this->getContent();
        return true;
    }

    // @param access (display|modify|delete)
    // this method is called by blocks_admin_modify|update|delete functions
    // and by xarBlock::render() method to determine access for current user
    // @return boolean true if access allowed
    public function checkAccess($access)
    {
        if (empty($access)) throw new EmptyParameterException('Access method');
        $access_method = $access . '_access';
        $access = isset($this->$access_method) ? $this->$access_method :
            array('group' => 0, 'level' => 100, 'failure' => 0);
        // Decide whether this block is displayed to the current user
        $args = array(
            'module' => $this->module,
            'component' => 'Block',
            'instance' => $this->type . ":" . $this->name . ":" . $this->bid,
            'group' => $access['group'],
            'level' => $access['level'],
        );
        static $access_property;
        if (!isset($access_property)) {
            sys::import('modules.dynamicdata.class.properties.master');
            $access_property = DataPropertyMaster::getProperty(array('name' => 'access'));
        }
        return $access_property->check($args);
    }

    final public function attachGroup($block_id, $box_template=null, $block_template=null)
    {
        return $this->updateGroup($block_id, $box_template, $block_template);
    }

    final public function updateGroup($block_id, $box_template=null, $block_template=null)
    {
        $this->instance_groups[$block_id] = array(
            'box_template' => $box_template,
            'block_template' => $block_template,
        );
        return true;    
    }    
    
    final public function detachGroup($block_id)
    {
        if (isset($this->instance_groups[$block_id]))
            unset($this->instance_groups[$block_id]);
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
    final public function setExpire($timestamp=0)
    {
        $this->expire = $timestamp;
    }
    final public function setTitle($title='')
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
    final public function setCacheExpire($cacheexpire=null)
    {
        $this->cacheexpire = $cacheexpire;
    }
}
?>