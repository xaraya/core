<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @link http://xaraya.com/index.php/release/1.html
 */

sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * Handle module property
 * @author mikespub
 */
class ModuleProperty extends ObjectRefProperty
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    public $filter = array();

    public $initialization_refobject    = 'modules_modules';    // ID of the object we want to reference
    public $initialization_store_prop   = 'regid';              // Name of the property we want to use for storage

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/modules/xarproperties';
    }

    function showInput(Array $data=array())
    {
        if (!empty($data['filter'])) $this->filter = $data['filter'];
        if (!empty($data['configuration'])) $this->parseConfiguration($data['configuration']);
        return parent::showInput($data);
    }

    function getOptions()
    {
        $items = xarModAPIFunc('modules', 'admin', 'getlist',array('filter' => $this->filter));
        $options = array();
        foreach($items as $item) {
            $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
        }
        return $options;
    }
}
?>
