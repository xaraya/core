<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/* include the base class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle check box list property
 */
class CheckboxListProperty extends SelectProperty
{
    public $id         = 1115;
    public $name       = 'checkboxlist';
    public $desc       = 'Checkbox List';

    public $display_columns = 3;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template  = 'checkboxlist';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVarFetch($name, 'isset', $value,  NULL, XARVAR_NOT_REQUIRED);
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) $value = '';
        $this->setValue($value);
        return true;
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['value'])) {
            if (is_array($data['value'])) {
                $this->value = implode(',',$data['value']);
            } else {
                $this->value = $data['value'];
            }
        }
        $data['value'] = $this->getValue();
        if (!isset($data['rows_cols'])) $data['rows_cols'] = $this->display_columns;
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->getValue();
        return parent::showOutput($data);
    }

    public function getValue()
    {
        if (!is_array($this->value) && is_string($this->value) && !empty($this->value)) 
            return explode(',', $this->value);
        else return array();
    }

    public function setValue($value=null)
    {
        if ( is_array($value) ) $this->value = implode ( ',', $value);
        else $this->value = $value;
    }
}

?>
