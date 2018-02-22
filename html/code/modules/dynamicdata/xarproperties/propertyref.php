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

sys::import('modules.base.xarproperties.dropdown');

/**
 * This property displays a dropdown of properties of a dataobject
 * Select a property from a particular object, either by specifying the reference object in configuration, or
 * by specifying which other property from the current objectref contains the objectname or objectid
 */
class PropertyRefProperty extends SelectProperty
{
    public $id         = 3131;
    public $name       = 'propertyref';
    public $desc       = 'Property Dropdown';
    public $reqmodules = array('dynamicdata');

    public $initialization_refobject = 'objects'; // select the object whose property we want to reference, or
    public $initialization_other_rule = '';       // specify the property in objectref that contains the objectname (e.g. source)

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        // we want a reference to the object here
        $this->include_reference = 1;
    }

	/**
	 * Display a dropdown of properties for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        // Allow overriding by specific parameters
        if (isset($data['refobject']))  $this->initialization_refobject = $data['refobject'];
        if (isset($data['other_rule'])) $this->initialization_other_rule = $data['other_rule'];
        return parent::showInput($data);
    }

	/**
	 * Display a dropdown of properties for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showOutput(Array $data = array())
    {
        // Override getOption() below
        return parent::showOutput($data);
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
/* we can't cache here if we rely on another property, so override getOption instead
        // Check configuration and return saved options (e.g. when we're dealing with an object list)
        if (!empty($this->_items) && $this->isSameConfiguration() && !empty($this->options)) {
            return $this->options;
        }
*/
        $options = array();

        // get the object whose properties we want
        $objectname = '';
        if (!empty($this->initialization_other_rule)) {
            $propname = $this->initialization_other_rule;
            if (!empty($this->objectref) && !empty($this->objectref->properties[$propname])) {
            // CHECKME: this only works in object lists if this property comes *after* the $propname one -> override getOption instead
                $objectname = $this->objectref->properties[$propname]->getValue();
            }
        } elseif (!empty($this->initialization_refobject)) {
            $objectname = $this->initialization_refobject;
        }
        if (empty($objectname)) {
            return $options;
        }

        if (is_numeric($objectname)) {
            $objectid = $objectname;
        } else {
            $info = DataObjectMaster::getObjectInfo(array('name' => $objectname));
            if (empty($info) || empty($info['objectid'])) {
                // try table name
                $fields = xarMod::apiFunc('dynamicdata','util','getmeta',
                                          array('table' => $objectname));
                if (!empty($fields) && !empty($fields[$objectname])) {
                    foreach (array_keys($fields[$objectname]) as $fieldname) {
                        $options[] = array('id' => $fieldname, 'name' => $fieldname);
                    }
                }
                return $options;
            }
            $objectid = $info['objectid'];
        }

        $object = DataObjectMaster::getObjectList(array('name' => 'properties'));
        $items = $object->getItems(array('where'     => "objectid eq $objectid", // filter on the selected object
                                         'fieldlist' => array('name','label')));
        foreach ($items as $item) {
            $options[] = array('id' => $item['name'], 'name' => $item['label']);
        }

/* we can't cache here if we rely on another property, so override getOption instead
        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
*/
        return $options;
    }

	/**
     * Retrieve or check an individual option on demand
     *
     * @param  $check boolean
     * @return if check == false:<br/>
     *                - display value, if found, of an option whose store value is $this->value<br/>
     *                - $this->value, if not found<br/>
     *         if check == true:<br/>
     *                - true, if an option exists whose store value is $this->value<br/>
     *                - false, if no such option exists<br/>
     */
    function getOption($check = false)
    {
        if ($check) return true;
        // override default getOption behaviour to avoid problems in object lists
        return $this->value;
    }
}
?>
