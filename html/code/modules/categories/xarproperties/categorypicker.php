<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.base.xarproperties.array');

class CategoryPickerProperty extends ArrayProperty
{
    public $id         = 30050;
    public $name       = 'categorypicker';
    public $desc       = 'CategoryPicker';
    public $reqmodules = array('categories');
    
    public $display_column_definition = array(array("Tree Name","Base Category","Include Self","Multiple Select"),array(2,100,14,14),array("New Tree",0,1,1),array("","","",""));  
    public $initialization_addremove = 2;
    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/categories/xarproperties';
    }
}

?>