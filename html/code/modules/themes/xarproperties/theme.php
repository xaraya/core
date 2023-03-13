<?php
sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * The theme property displays a dropdown of available themes
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 * @author mikespub
 */

/**
 * This property displays a dropdown of themes on this site (subject to whatever filters are configured)
 */
class ThemeProperty extends ObjectRefProperty
{
    public $id         = 38;
    public $name       = 'theme';
    public $desc       = 'Theme';
    public $reqmodules = array('themes');

    public $filter = array();

    public $initialization_refobject    = 'themes';            // The object we want to reference

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/themes/xarproperties';
        // these correspond to what we actually get from the modules getlist() function below
        $this->initialization_store_prop   = 'regid';
        $this->initialization_display_prop = 'name';
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
	 * @return array Returns list of options
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        $options = array();
        $items = xarMod::apiFunc('themes', 'admin', 'getlist',array('filter' => $this->filter));
        foreach($items as $item) {
            try {
                $options[] = array('id' => $item[$this->initialization_store_prop], 'name' => $item[$this->initialization_display_prop]);
            } catch(Exception $e) {}
        }
        return $options;
    }
}
