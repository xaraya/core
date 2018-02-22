<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/*
* Unserialized configuration of the dropdown
    array(
        'initialization_include_no_cat' => 1,
        'initialization_include_all_cats' => 1,
        'initialization_basecategories' => array('Picker Dropdown',array(0=>array(0=>array(-1))),array(1=>true),array(1=>1)),
    );
*/
sys::import('modules.base.xarproperties.array');

/**
 * This property displays a configuration widget for a categories dropdown
 */
class CategoryPickerProperty extends ArrayProperty
{
    public $id         = 30050;
    public $name       = 'categorypicker';
    public $desc       = 'CategoryPicker';
    public $reqmodules = array('categories');
    
    public $display_column_definition = array(
                                array("Tree Name",2,"New Tree",""),
                                array("Root Branch",100,0,'a:3:{s:29:"initialization_include_no_cat";i:0;s:31:"initialization_include_all_cats";i:1;s:29:"initialization_basecategories";a:1:{i:0;a:4:{i:0;s:15:"Picker Dropdown";i:1;i:1;i:2;b:1;i:3;i:1;}}}',),
                                array("Include Self",14,1,""),
                                array("Select Type",6,0,'a:3:{s:12:"display_rows";s:1:"0";s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:62:"1,Single Dropdown;2,Multiple - One Box;3,Multiple - Two Boxes;";}')
                                );
    public $display_minimum_rows      = 1;
    public $initialization_addremove  = 2;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/categories/xarproperties';
    }

    /**
     * Create Value
     * 
     * @param int $itemid
     * @return boolean Returns true
     */
    public function createValue($itemid=0)
    {
        // Set the module_id: case of a bound property
        if (isset($this->objectref)) $this->module_id = (int)$this->objectref->module_id;
        // Override or a standalone property
        if (isset($data['module'])) $this->module_id = xarMod::getID($data['module']);
        // No hint at all, take the current module
        if (!isset($this->module_id)) $this->module_id = xarMod::getID(xarMod::getName());

        // Do the same for itemtypes
        if (isset($this->objectref)) $this->itemtype = (int)$this->objectref->itemtype;
        if (isset($data['itemtype'])) $this->itemtype = (int)$data['itemtype'];
        // No hint at all, assume all itemtypes
        if (!isset($this->itemtype)) $this->itemtype = 0;
        return true;
    }

    /**
     * Updates value for the given item id.
     * @param int $itemid ID of the item to be updated
     * @return boolean Returns true on success, false on failure
     */
    public function updateValue($itemid=0)
    {
        return $this->createValue($itemid);
    }
}

?>