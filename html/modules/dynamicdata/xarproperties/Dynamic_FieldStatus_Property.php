<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * Dynamic Data Field Status Property
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle field status
 *
 * @package dynamicdata
 */
class Dynamic_FieldStatus_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule =  'dynamicdata';
        $this->template =  'status';

        if (count($this->options) == 0) {
            $this->options['display'] = array(
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('Active')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Display Only')),
                                 array('id' => Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden')),
                             );
            $this->options['input'] = array(
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_NOINPUT, 'name' => xarML('No Input Allowed')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_ADD, 'name' => xarML('Can be added')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_MODIFY, 'name' => xarML('Can be changed')),
                                 array('id' => Dynamic_Property_Master::DD_INPUTSTATE_ADDMODIFY, 'name' => xarML('Can be added/changed')),
                             );
        }
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 25;
        $info->name = 'fieldstatus';
        $info->desc = 'Field Status';

        return $info;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        $valuearray['display'] = $value & 31;
        $valuearray['input'] = $value & 992;

        $data['value'] = $valuearray;

        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }

        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        return parent::showInput($data);
    }

    function getOption($check = false)
    {
        //TODO: get this working
    }
}
?>
