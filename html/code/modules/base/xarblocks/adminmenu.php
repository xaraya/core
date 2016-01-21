<?php
/**
 * Adminmenu Block
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Initialise block info
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
// Inherit properties from MenuBlock class
sys::import('xaraya.structures.containers.blocks.menublock');
class Base_AdminmenuBlock extends MenuBlock implements iBlock
{
    protected $type                = 'adminmenu';
    protected $module              = 'base';
    protected $text_type           = 'Admin Menu';
    protected $text_type_long      = 'Displays Admin Menu';
    protected $xarversion          = '2.4.0';
    protected $show_preview        = true;
    protected $show_help           = true;
    
    protected $menumodtype         = 'admin';
    protected $menumodtypes        = array('admin', 'util');

    public $showlogout          = 1;
    public $menustyle           = 'bycat';
    public $showfront           = 1;
    public $marker              = '';

/**
 * This method is called by the BasicBlock class constructor
 * 
 * @param void N/A
**/    
    public function init()
    {
        parent::init();
        if (empty($this->modulelist)) {
            // if the modulelist is empty, admin deselected all modules, put back the modules module
            // @CHECKME: put back the blocks module too so we can edit this?
            $this->modulelist = array('modules' => array('visible' => 1));
        }
        // make sure we keep the content array in sync
        $this->content['modulelist'] = $this->modulelist;
    }
    
}
?>