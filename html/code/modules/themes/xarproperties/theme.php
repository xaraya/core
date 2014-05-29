<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */

sys::import('modules.dynamicdata.xarproperties.objectref');

/**
 * Handle module property
 * @author mikespub
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

    function showInput(Array $data=array())
    {
        if (!empty($data['filter'])) $this->filter = $data['filter'];
        return parent::showInput($data);
    }

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
?>
