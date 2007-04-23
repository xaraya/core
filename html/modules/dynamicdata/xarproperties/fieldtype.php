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
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle field type property
 */
class FieldTypeProperty extends SelectProperty
{
    public $id         = 22;
    public $name       = 'fieldtype';
    public $desc       = 'Field Type';
    public $reqmodules = array('dynamicdata');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';

        if (count($this->options) == 0) {
            $proptypes = DataPropertyMaster::getPropertyTypes();
            if (!isset($proptypes)) $proptypes = array();

            foreach ($proptypes as $propid => $proptype) {
                // TODO: label isnt guaranteed to be unique, if not, leads to some surprises.
                $this->options[$proptype['label']] = array('id' => $propid, 'name' => $proptype['label']);
            }
        }
        // sort em by name
        ksort($this->options);
    }
    public function checkInput($name = '', $value = null)
    {
       return parent::checkInput('dd_'.$this->id);
    }
}
?>
