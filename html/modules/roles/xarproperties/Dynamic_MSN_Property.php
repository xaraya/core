<?php
/**
 * Handle MSN property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* 
 * Handle MSN property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

class Dynamic_MSN_Property extends Dynamic_URLIcon_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui'; // TODO: verify this !
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('MSN Messenger');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();

        if (!empty($value)) {
// TODO: what's the link to use for MSN Messenger ??
            $link = "TODO: what's the link for MSN ?".$value;
        } else {
            $link = '';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;
        $data['link']     = xarVarPrepForDisplay($link);

      $template="";
      return xarTplProperty('roles', 'msn', 'showinput', $data);
    }

    function showOutput($args = array())
    {
            extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }

        $data=array();

        if (!empty($value)) {
            $link = "TODO: what's the link for MSN ?".$value;
            $data['link'] = xarVarPrepForDisplay($link);

            if (!empty($this->icon)) {
                $data['value']= $this->value;
                $data['icon'] = $this->icon;
                $data['name'] = $this->name;
                $data['id']   = $this->id;
                $data['image']= xarVarPrepForDisplay($this->icon);

            $template="";
            return xarTplProperty('roles', 'msn', 'showoutput', $data);

            }
        }
     return '';   
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 30,
                              'name'       => 'msn',
                              'label'      => 'MSN Messenger',
                              'format'     => '30',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'roles',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>
