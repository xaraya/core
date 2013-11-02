<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/* Include the parent class  */
sys::import('modules.dynamicdata.class.properties.base');
/**
 * Handle check box property
 */
class CheckboxProperty extends DataProperty
{
    public $id         = 14;
    public $name       = 'checkbox';
    public $desc       = 'Checkbox';
    public $reqmodules = array('base');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'checkbox';
        $this->filepath  = 'modules/base/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (empty($value) || $value == 'false') {
            $this->value = false;
        } else {
            $this->value = true;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['checked'])) $data['value']  = $data['checked'];
        if (!isset($data['value'])) $data['value'] = $this->value;
        if ($data['value'] === true || $data['value'] === 'true') $data['value'] = 1;
        elseif ($data['value'] === false || $data['value'] === 'false') $data['value'] = 0;
        $data['checked']  = $data['value'];
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        return parent::showInput($data);
    }

    public function castType($value=null)
    {
        return ($value === 1 || $value === '1' || $value === true || $value === 'true') ? true : false;
    }
}
?>
