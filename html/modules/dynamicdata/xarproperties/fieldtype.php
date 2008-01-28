<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * Handle field type property
 */
class FieldTypeProperty extends ObjectRefProperty
{
    public $id         = 22;
    public $name       = 'fieldtype';
    public $desc       = 'Field Type';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
//        $this->initialization_refobject    = 'properties';
        $this->initialization_store_prop   = 'id';
        $this->initialization_display_prop = 'label';
    }
    function getOptions()
    {
        $proptypes = DataPropertyMaster::getPropertyTypes();
        if (!isset($proptypes)) $proptypes = array();

        $options = array();
        foreach ($proptypes as $propid => $proptype) {
            // TODO: label isnt guaranteed to be unique, if not, leads to some surprises.
            $options[$proptype[$this->initialization_display_prop]] = array('id' => $proptype[$this->initialization_store_prop], 'name' => $proptype[$this->initialization_display_prop]);
        }
        // sort by name
        ksort($options);
        return $options;
    }
}
?>
