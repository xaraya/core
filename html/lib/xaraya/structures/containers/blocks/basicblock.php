<?php
/**
 * @package core
 * @subpackage blocks
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */
    // @TODO: see validations note in constructor
/**
 * BasicBlock class, default parent class for all blocks
 * CHECKME: BasicBlock implies other parent classes, is that necessary?
 * or could this perhaps just be Block class?
 * CHECKME: Should this class be required by all blocks?
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @param $args blockinfo from db* passed in when instantiating
 *        *see class properties and __constructor method below
 *
 * @TODO: checkInput method, see validate method todo
 * @TODO: validate method, see validations note in constructor
 *
**/
sys::import('xaraya.structures.descriptor');

class BasicBlock extends ObjectDescriptor implements iBlock
{
    // protected $args; // from descriptor
    // Type Info: Classes inheriting from this class should always over-ride these
    public $module          = 'BlockModule';  // Module your child class belongs to
    public $text_type       = 'Basic Block';  // Block name
    public $text_type_long  = 'Parent class for blocks'; // Block description
    // version check so blocks can supply an upgrade method (called in constructor)
    public $xarversion             = '0.0.0'; // expects a 3 point version number

    // block instance properties
    // these will be filled in by blockinfo when a new object is instantiated
    public $bid             = 0;        // Block Id (0 = Standalone block)
    public $groupid         = 0;        // Blockgroup Id (parent blockgroup block; 0 = none)
    public $group           = '';       // Blockgroup Name (parent blockgroup block name)
    public $group_inst_template = '';   // Group Instance template (outer;inner)
    public $template        = '';       // Block instance template (outer;inner)
    public $group_template  = '';       // Blockgroup template (outer)
    public $position        = 0;        // Blockgroup block order
    public $refresh         = 0;        // (deprec)
    public $state           = xarBlock::BLOCK_STATE_VISIBLE;
    public $tid             = 0;        // block type id
    public $type            = 'Block';  // block type name
    public $name            = '';       // Name of block
    public $title           = '';       // Block title
    public $show_preview    = false;    // Show a preview of the display

    // Cache settings: will be over-ridden by blockinfo[content] (means block cache settings table data)
    // or from input, eg via block tag parameters, falling back to these defaults, or those of the child class
    public $nocache             = 0; // 0 = caching on; 1 = caching off;
    public $pageshared          = 1; // 0 = No sharing; 1 = Share across pages;
    public $usershared          = 0; // 0 = Cache for all users; 1 = Cache per user group; 2 = Cache per user;
    public $cacheexpire         = NULL; // length of time before cached block is considered stale
    // stop showing (expire) block after x minutes, stored in $content array
    // cfr. Base module HTML Block, now for any block(group) :)
    public $expire              = 0;
    // allow multiple instances, block type info setting,
    // set by module developer, shouldn't be over-ridden.?
    // @FIXME: this doesn't appear to be used anywhere
    public $allow_multiple      = false;
    // access property block defaults
    // @TODO: set appropriate defaults for each level
    public $display_access      = array('group' => 0, 'level' => 100, 'failure' => 0);
    public $modify_access       = array('group' => 0, 'level' => 100, 'failure' => 0);
    public $delete_access       = array('group' => 0, 'level' => 100, 'failure' => 0);
    public static $access_property = null;
    // the block content
    public $content             = array();
    // blocks inheriting from this class must define their own properties
    // all properties not stored in the db are stored in $this->content

    // use the constructor to populate properties ($data = blockinfo)
    // all blocks inheriting this class should call this constructor
    // eg parent::__construct($data);
    public function __construct(Array $data=array())
    {
        // get the current block version before we over-write with content
        $newver = $this->xarversion;
        // expand content here if necessary (shouldn't be now)
        if (isset($data['content']) && !is_array($data['content'])) {
            $content = @unserialize($data['content']);
            $data['content'] = is_array($content) ? $content : $this->content;
        }
        // @TODO: validate $data
        // set arguments from $data
        parent::__construct($data);
        // merge content
        if (!empty($data['content']) && is_array($data['content']))
            parent::setArgs($data['content']);
        // update properties from content args
        parent::refresh($this);
         // populate content on first run
        if (empty($this->content)) $this->content = $this->getInfo();
        // set a sensible default for blocks not yet using xarversion
        $oldver = !empty($this->content['xarversion']) ? $this->content['xarversion'] : '0.0.0';
        // compare versions if we have a new version that is different to the old version,
        if (!empty($newver) && $newver != $oldver) {
            sys::import('xaraya.version');
            $vercompare = xarVersion::compare($newver, $oldver, 3);
            // compare new block with old block,
            if ($vercompare > 0) {
                // since blocks can have children we need to ensure we only call
                // the upgrade method for this block if it has one (and not defer to its parent)
                // To do this we use reflection...
                // First we need the Class Name of the block,
                $refName = ucfirst($this->module) . '_' . ucfirst($this->type) . 'Block';
                // create a reflection object from the class
                $refObject  = new ReflectionClass($refName);
                // find all public and protected methods in ParentClass
                $parentMethods = $refObject->getParentClass()->getMethods(
                    ReflectionMethod::IS_PUBLIC ^ ReflectionMethod::IS_PROTECTED
                );
                // find all parentmethods that were redeclared in ChildClass
                foreach($parentMethods as $parentMethod) {
                    $declaringClass = $refObject->getMethod($parentMethod->getName())
                                                ->getDeclaringClass()
                                                ->getName();
                    if($declaringClass === $refObject->getName() && $parentMethod->getName() == 'upgrade') {
                        $hasUpgrade = 1;
                        unset($declaringClass);
                        break;
                    }
                }
                // clean up unneeded variables
                unset($parentMethods); unset($refName); unset($refObject);
                if (!empty($hasUpgrade)) {
                    // only run upgrade if new version is greater than old version
                    // modules can over-ride the upgrade method with their own :)
                    // pass the old version to the upgrade method
                    if (!$this->upgrade($oldver)) {
                        // if upgrade method didn't return true, upgrade failed
                        throw new RegistrationException(array($this->module, $this->text_type, $oldver, $newver), 'Unable to upgrade #(1) module block #(2) from version #(3) to version #(4)');
                        // update to new version
                        $this->content['xarversion'] = $newver;
                    } elseif ($vercompare < 0) {
                        // can't downgrade blocks
                        throw new RegistrationException(array($this->module, $this->text_type, $oldver, $newver), 'Unable to downgrade #(1) module block #(2) from version #(3) to version #(4)');
                    }
                }
            }
        }

    }

    public function getInfo()
    {
        return $this->getPublicProperties();
    }

    // init function (supplies blocks initial defaults)
    public function getInit()
    {
        $result = $this->getInfo();
        // @TODO: check the skiplist, prob can/need to skip more properties
        $skiplist = array('name', 'module', 'text_type', 'text_type_long', 'func_update', 'allow_multiple', 'groupid','group','group_inst_template', 'group_template', 'template', 'tid', 'type','bid', 'position', 'refresh', 'display_access', 'modify_access', 'delete_access', 'content', 'state','show_preview','title');
        foreach ($skiplist as $propname) {
            unset($result[$propname]);
        }
        return $result;
    }

    // this method is called by xarBlock::render();
    public function display(Array $args=array())
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by blocks_admin_modify()
    public function modify(Array $args=array())
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by blocks_admin_update()
    public function update(Array $args=array())
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by blocks_admin_delete()
    public function delete(Array $args=array())
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by the constructor to run upgrades from older block versions
    // this method should be placed in the Module_BlockNameBlock class,
    // eg in Base_MenuBlock not in Base_MenuBlockAdmin
    public function upgrade($oldversion)
    {
        // use it much as you would the xarinit upgrade function in modules
        switch ($oldversion) {
            case '0.0.0': // if no version was previously set, the default is 0.0.0
            default:
                // upgrades from 0.0.0 go here
            // fall through to subsequent upgrades
            case '0.0.1':
                // upgrades from 0.0.1 go here

            // etc...
            break;
        }
        return true;
    }

    // @param access (display|modify|delete)
    // this method is called by blocks_admin_modify|update|delete functions
    // and by xarBlock::render() method to determine access for current user
    // @return bool true if access allowed
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
        if (!isset(self::$access_property)) {
            sys::import('modules.dynamicdata.class.properties.master');
            self::$access_property = DataPropertyMaster::getProperty(array('name' => 'access'));
        }
        return self::$access_property->check($args);
    }

    /*
    // optionally display a help tab in the modify_instance UI
    // only include this method if you intend to supply help information
    // requires a template named help-{blockType}.xt in xartemplates/blocks
    // containing the help information for the block type
    public function help()
    {
        // this method must return an array of data
        return $this->getInfo();
    }
    */

}

interface iBlock
{
    public function getInfo();
    public function getInit();
    public function upgrade($oldversion);
    public function display(Array $args=array());
    public function modify(Array $args=array());
    public function update(Array $args=array());
    public function delete(Array $args=array());
    public function checkAccess($access);
}

?>
