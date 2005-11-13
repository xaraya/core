<?php
/**
 * Dynamic Passbox property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* 
 * Dynamic Passbox property
 * @author mikespub <mikespub@xaraya.com>
 */

class Dynamic_PassBox_Property extends Dynamic_Property
{
    var $size = 25;
    var $maxlength = 254;

    var $min = 5;
    var $max = null;

    function Dynamic_PassBox_Property($args)
    {
        $this->Dynamic_Property($args);
        // check validation for allowed min/max length (or values)
        if (!empty($this->validation) && strchr($this->validation,':')) {
            list($min,$max) = explode(':',$this->validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = $min; // could be int or float - cfr. FloatBox below
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = $max; // could be int or float - cfr. FloatBox below
            }
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    if (is_array($value) && $value[0] == $value[1]) {
        $value = $value[0];
    } else {
        $this->invalid = xarML('text : Passwords did not match');
            $this->value = null;
        return false;
    }
            
        if (!empty($value) && strlen($value) > $this->maxlength) {
            $this->invalid = xarML('text : must be less than #(1) characters long',$this->max + 1);
            $this->value = null;
            return false;
        } elseif (isset($this->min) && strlen($value) < $this->min) {
            $this->invalid = xarML('text : must be at least #(1) characters long',$this->min);
            $this->value = null;
            return false;
        } else {
    // TODO: allowable HTML ?
            $this->value = $value;
            return true;
        }
    }

    function showInput($args = array())
    {
        extract($args);
        
        $data = array();

        if (empty($maxlength) && isset($this->max)) {
            $this->maxlength = $this->max;
            if ($this->size > $this->maxlength) {
                $this->size = $this->maxlength;
            }
        }


//         $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;
         $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;
         $data['id']       = !empty($id)   ? $id   : 'dd_'.$this->id;
         $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
         $data['tabindex'] = !empty($tabindex) ? $tabindex  : 0;
         $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
         $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
         $data['size']     = !empty($size) ? $size : $this->size;

        $template="";
        return xarTplProperty('roles', 'password', 'showinput', $data);

    }

    function showOutput($value = null)
    {
    //we don't really want to show the password, do we?
    $data=array();
    $data['value']='';

    $template="";
    return xarTplProperty('roles', 'password', 'showoutput', $data);

    //return '';
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
                                 'id'         => 46,
                                 'name'       => 'password',
                                 'label'      => 'Password Text Box',
                                 'format'     => '46',
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
