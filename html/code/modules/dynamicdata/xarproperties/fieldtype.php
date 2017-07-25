<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * This property displays a dropdown of dataproperties
 */
class FieldTypeProperty extends ObjectRefProperty
{
    public $id         = 22;
    public $name       = 'fieldtype';
    public $desc       = 'Field Type';
    public $initialization_store_prop   = 'id';
    public $initialization_display_prop = 'label';
    public $initialization_refobject    = null;    // There is no object corresponding to the xar_dynamic_properties_def table

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        // CHECKME: can we somehow get rid of $this->initialization_refobject here, or
        //          switch back to SelectProperty and use initialization_store_type ?
    }
	
	/**
     * Retrieve the list of options on demand
     * 
     * @param void N/A
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        $options = array();
        $proptypes = DataPropertyMaster::getPropertyTypes();
        if (!isset($proptypes)) $proptypes = array();

        foreach ($proptypes as $propid => $proptype) {
            // TODO: label isnt guaranteed to be unique, if not, leads to some surprises.
            $options[$proptype[$this->initialization_display_prop]] = array('id' => $proptype[$this->initialization_store_prop], 'name' => $proptype[$this->initialization_display_prop]);
        }
        // sort by name
        ksort($options);

        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
        return $options;
    }

    public function preList()
    {
        // Skip prefill for fieldtype
        return true;
    }
}
?>