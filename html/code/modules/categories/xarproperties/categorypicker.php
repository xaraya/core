<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/*
* Unserialized configuration of the dropdown
    array(
        'initialization_include_no_cat' => 1,
        'initialization_include_all_cats' => 1,
        'initialization_basecategories' => array('Picker Dropdown',array(1=>array(1=>array(-1))),array(1=>true),array(1=>1)),
    );

*/
sys::import('modules.base.xarproperties.array');

class CategoryPickerProperty extends ArrayProperty
{
    public $id         = 30050;
    public $name       = 'categorypicker';
    public $desc       = 'CategoryPicker';
    public $reqmodules = array('categories');
    
    public $display_column_definition = array(array("Tree Name","Base Category","Include Self","Select Type"),array(2,100,14,6),array("New Tree",0,1,0),array("",'a:3:{s:29:"initialization_include_no_cat";i:0;s:31:"initialization_include_all_cats";i:1;s:29:"initialization_basecategories";a:4:{i:0;s:15:"Picker Dropdown";i:1;a:1:{i:1;a:1:{i:1;a:1:{i:0;i:-1;}}}i:2;a:1:{i:1;b:1;}i:3;a:1:{i:1;i:1;}}}',"",'a:3:{s:12:"display_rows";s:1:"0";s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:62:"1,Single Dropdown;2,Multiple - One Box;3,Multiple - Two Boxes;";}'));  
    public $initialization_addremove = 2;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/categories/xarproperties';
        $this->prepostprocess = 2;
    }

    public function createValue($itemid=0)
    {
        // Set the module_id: case of a bound property
        if (isset($this->objectref)) $this->module_id = (int)$this->objectref->module_id;
        // Override or a standalone property
        if (isset($data['module'])) $this->module_id = xarMod::getID($data['module']);
        // No hint at all, take the current module
        if (!isset($this->module_id)) $this->module_id = xarMod::getID(xarModGetName());

        // Do the same for itemtypes
        if (isset($this->objectref)) $this->itemtype = (int)$this->objectref->itemtype;
        if (isset($data['itemtype'])) $this->itemtype = (int)$data['itemtype'];
        // No hint at all, assume all itemtypes
        if (!isset($this->itemtype)) $this->itemtype = 0;
        return true;
    }

    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }
}

?>