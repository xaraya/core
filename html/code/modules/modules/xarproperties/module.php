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
class ModuleProperty extends ObjectRefProperty
{
    public $id         = 19;
    public $name       = 'module';
    public $desc       = 'Module';
    public $reqmodules = array('modules');

    public $filter = array();

// CHECKME: we're not actually using the objectref property here, because we want displayname etc.
    public $initialization_refobject    = 'modules';            // The object we want to reference

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/modules/xarproperties';
        // these correspond to what we actually get from the modules getlist() function below
        $this->initialization_store_prop   = 'regid';
        $this->initialization_display_prop = 'displayname';
    }

    function showInput(Array $data=array())
    {
        if (!empty($data['filter'])) $this->filter = $data['filter'];
        return parent::showInput($data);
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
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
