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
/**
 * BasicBlock class, default parent class for all blocks
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @param $args blockinfo from db passed in when instantiating
 *
**/
use Xaraya\Blocks\BlockServicesInterface;
use Xaraya\Blocks\BlockServicesTrait;
use Xaraya\Context\Context;

/**
 * For documentation purposes only - available via BasicBlock
 */
interface iBlock extends iBlockType, BlockServicesInterface
{
    public function getInfo();
    public function getInit();
    public function upgrade($oldversion);
    public function display();
}

interface iBlockGroup extends iBlock
{
    // protected $type_category = 'group';
    public function attachInstance($block_id);
    public function detachInstance($block_id);
    public function orderInstance($block_id, $direction);
    public function getInstances();
}
interface iBlockModify extends iBlock
{
    // required
    public function modify();
    public function update(array $data = []);
    // optional
    // function checkmodify();
}
interface iBlockDelete extends iBlock
{
    // required
    public function delete();
}

/**
 * Basic block class
 *
 * Available methods:
 * - __construct(array $blockinfo = [], $context = null) - called by xar::block()->getObject()
 * - display() - called by xar::block()->render()
 * - getInfo()
 * - ...
 *
 * Inherited methods:
 * - init() - called by BlockType::__construct()
 * - getContent()
 * - ...
 *
 * Available services:
 * - $this->ctl() = xarController::* Main Controller (getURL, redirect, ...)
 * - $this->log() = xarLog::* Logger (message, variable, ...)
 * - $this->mls() = xarMLS::* Multi-Language System (translate, ...)
 * - $this->mod() = xarMod*::* Modules (getVar, setVar, ...)
 * - $this->sec() = xarSec::* Security (checkAccess, genAuthKey, ...)
 * - $this->tpl() = xarTpl::* Templating (module, setPageTitle, ...)
 * - $this->var() = xarVar::* Variables (fetch, check, ...)
 * - $this->block() = xarBlock*::* Blocks (render, ...)
 * - $this->data() = DataObjectFactory::* with context (getObject, getObjectList, ...)
 * - $this->prop() = DataProperty*::* with context (getProperty, getPropertyTypes, ...)
 * - $this->cache() = xar*Cache::* Caching (getModuleKey, getObjectKey, ...)
 * - $this->config() = xarConfigVars::* Config (getVar, setVar, ...)
 * - $this->sysConfig() = xarSystemVars::* System (getVar, setVar, ...)
 * - $this->session() = xarSession::* Session (getVar, setVar, ...)
 * - $this->db() = xarDB::* Database (getConn, getPrefix, ...)
 * - ...
 * - $this->service($name, ...$args) = get core service by name
 * - $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 */
abstract class BasicBlock extends BlockType implements iBlock
{
    use BlockServicesTrait;

    // File Information, supplied by developer, never changes during a versions lifetime, required
    protected $type = 'basicblock';
    protected $module = ''; // module block type belongs to, if any
    protected $tplmodule = ''; // module calling the block, that may have its own template for it
    protected $text_type = 'Basic Block';  // Block type display name
    protected $text_type_long = 'Parent class for all block instances'; // Block type description
    protected $xarversion = '0.0.0';    // must be a 3 point version number
    // Additional info, supplied by developer, optional
    protected $type_category = 'block'; // options [(block)|group]
    protected $author = '';
    protected $contact = '';
    protected $credits = '';
    protected $license = '';
    // We need to get the actual $classname and $filepath from getinfo() - requires UPGRADE due to table change
    protected $filepath = '';

    // blocks subsystem flags
    protected $show_preview = true;  // let the subsystem know if it's ok to show a preview
    // @todo: drop the show_help flag, and go back to checking if help method is declared
    protected $show_help    = false; // let the subsystem know if this block type has a help() method

    // blocks inheriting from this class must define their own public properties
    // all public properties not accounted for already by the subsystem are stored in $this->content

    /**
     * Methods called by the blocks subsystem
    **/
    /**
     * Summary of __construct
     * @param array<string, mixed> $blockinfo
     * @param ?Context<string, mixed> $context
     */
    final public function __construct(array $blockinfo = [], $context = null)
    {
        // set context before calling parent constructor
        $this->setContext($context);
        parent::__construct($blockinfo);
        // move runUpgrade() and init() to BasicBlock constructor - see iBlock interface
        // check for upgrade and run if necessary
        $this->runUpgrade();
        // run any additional initialisation supplied by this block type
        if ($this->block()->hasMethod($this, 'init', true)) {
            $this->init();
        }
    }

    /**
     * init
     * @return void
    **/
    // NOTE: since the constructor cannot be overloaded, this method
    // is called by the constructor to run any additional functions
    // specific to this type immediately after the object is initialised
    public function init() {}

    final protected function runUpgrade()
    {
        if ($this->xarversion != $this->type_version && $this->block()->hasMethod($this, 'upgrade', true)) {
            if (!empty($this->type_version)) {
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

    // this method is called by xar::block()->render();
    public function display()
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by blocks_admin_modify_instance()
    public function modify()
    {
        $data = $this->getContent();
        return $data;
    }

    // this method is called by blocks_admin_modify_instance()
    public function update($data = [])
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by blocks_admin_delete_instance()
    public function delete()
    {
        $data = $this->getInfo();
        return $data;
    }

    // this method is called by BlockType::__construct() to run upgrades from older block versions
    public function upgrade($oldversion)
    {
        // use it much as you would the xarinit upgrade function in modules
        switch ($oldversion) {
            case '0.0.0': // if no version was previously set, the default is 0.0.0
                // upgrades from 0.0.0 go here
                // fall through to subsequent upgrades
            case '0.0.1':
                // upgrades from 0.0.1 go here

                // etc...
                break;
        }
        return true;
    }

    public function getInit()
    {
        return $this->storeContent();
    }

    // @todo: this is here to support legacy blocks
    // deprecate once all blocks are using $this->getContent() instead
    public function getInfo()
    {
        /** @var array<string, mixed> $info */
        $info = $this->getTypeInfo();
        $info += $this->getInstanceInfo();
        $info += $this->getConfiguration();
        $info += $this->getContent();
        $info['content'] = $this->storeContent();
        return $info;
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
