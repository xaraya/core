<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/* Include the parent class  */
sys::import('modules.dynamicdata.class.properties');
/**
 * Handle check box property
 */
class CheckboxProperty extends DataProperty
{
    public $id         = 14;
    public $name       = 'checkbox';
    public $desc       = 'Checkbox';
    public $reqmodules = array('base');

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'checkbox';
        $this->filepath   = 'modules/base/xarproperties';
    }

    function checkInput($name='', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}
    // TODO: allow different values here, and verify $checked ?
        if (!empty($value)) {
            $this->value = 1;
        } else {
            $this->value = 0;
        }
        return true;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }

        $data['checked']  = ((isset($data['value']) && $data['value']) || (isset($data['checked']) && $data['checked'])) ? true : false;
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        return parent::showInput($data);
    }
}
?>
