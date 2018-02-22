<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 * @author mikespub
 */

sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * This property displays a dropdown of Xaraya modules (subject to filters)
 * 
 */
class ModuleProperty extends ObjectRefProperty
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    public $filter = array();

    // these correspond to what we actually get from the modules getlist() function below
    public $initialization_refobject    = 'modules';            // The object we want to reference
    public $initialization_store_prop   = 'regid';
    public $initialization_display_prop = 'name';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/modules/xarproperties';
    }

	/**
	 * Display a dropdown for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    function showInput(Array $data=array())
    {
        if (!empty($data['filter'])) $this->filter = $data['filter'];
        return parent::showInput($data);
    }

	/**
     * Retrieve the list of options on demand
     * 
     * N.B. the code below is repetitive, but lets leave it clearly separated for 
     * each type of input for the moment
     * 
     * @param void N/A
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        $items = xarMod::apiFunc('modules', 'admin', 'getlist',array('filter' => $this->filter));
        foreach($items as $item) {
            try {
                $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
            } catch(Exception $e) {}
        }
        return $options;
    }
}
?>