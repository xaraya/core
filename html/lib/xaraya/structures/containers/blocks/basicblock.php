<?php
/**
 * @package core
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * BasicBlock class, default parent class for all blocks
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @param $args blockinfo from db passed in when instantiating
 *
**/
sys::import('xaraya.structures.containers.blocks.blocktype');
interface iBlock extends iBlockType
{
    public function getInfo();
    public function getInit();
    public function upgrade($oldversion);
    public function display();
}

interface iBlockGroup extends iBlock
{
    // protected $type_category = 'group';
    function attachInstance($block_id);
    function detachInstance($block_id);
    function orderInstance($block_id, $direction);
    function getInstances();
}
interface iBlockModify extends iBlock
{
    // required
    function modify();
    function update();
    // optional
    // function checkmodify();
}
interface iBlockDelete extends iBlock
{
    // required
    public function delete();
}
abstract class BasicBlock extends BlockType implements iBlock
{
    // File Information, supplied by developer, never changes during a versions lifetime, required
    protected $type = 'basicblock';
    protected $module = ''; // module block type belongs to, if any
    protected $text_type = 'Basic Block';  // Block type display name
    protected $text_type_long = 'Parent class for all block instances'; // Block type description
    protected $xarversion = '0.0.0';    // must be a 3 point version number
    // Additional info, supplied by developer, optional 
    protected $type_category = 'block'; // options [(block)|group] 
    protected $author = '';
    protected $contact = '';
    protected $credits = '';
    protected $license = '';
    
    // blocks subsystem flags
    protected $show_preview = true;  // let the subsystem know if it's ok to show a preview
    // @todo: drop the show_help flag, and go back to checking if help method is declared 
    protected $show_help    = false; // let the subsystem know if this block type has a help() method

    // blocks inheriting from this class must define their own public properties
    // all public properties not accounted for already by the subsystem are stored in $this->content

/**
 * Methods called by the blocks subsystem
**/
    // this method is called by BlockType::__construct()
    public function init()
    {
    
    }

    // this method is called by xarBlock::render();
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
    public function update()
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
?>