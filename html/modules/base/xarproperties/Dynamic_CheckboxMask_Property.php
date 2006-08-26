<?php
/**
 * Checkbox Mask Property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Class to handle check box property
 *
 * @package dynamicdata
 */
class Dynamic_CheckboxMask_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template =  'checkboxmask';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 1114;
        $info->name = 'checkboxmask';
        $info->desc = 'Checkbox Mask';

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        if(is_array($value)) {
            $this->value = maskImplode($value);
        } else {
            $this->value = $value;
        }

        return true;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }

        if (!is_array($data['value']) && is_string($data['value'])) {
            $data['value'] = maskExplode($data['value']);
        }

        if (!isset($data['options']) || count($data['options']) == 0) {
            $this->getOptions();
            $options = array();
            foreach($this->options as $key => $option) {
                $option['checked'] = in_array($option['id'], $data['value']);
                $data['options'][$key] = $option;
            }
        }

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        if (!is_array($value)) $value = maskExplode($value);

        $this->getOptions();
        $numOptionsSelected = 0;
        $options = array();
        foreach($this->options as $key => $option)
        {
            $option['checked'] = in_array($option['id'], $value);
            $options[$key] = $option;
            if ($option['checked']) {
                $numOptionsSelected++;
            }
        }

        $data['options'] = $options;
        $data['numOptionsSelected'] = $numOptionsSelected;

        return parent::showOutput($data);
    }

}

function maskImplode($anArray)
{
    $output = '';
    if(is_array($anArray)) {
        foreach($anArray as $entry) {
            $output .= $entry;
        }
    }
    return $output;
}

function maskExplode($aString)
{
    return explode(',', substr(chunk_split($aString, 1, ','), 0, -1));
}
?>
