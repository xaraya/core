<?php
/**
 * File: $Id$
 *
 * Dynamic Text Box Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * handle the textbox property
 *
 * @package dynamicdata
 */
class Dynamic_TextBox_Property extends Dynamic_Property
{
    var $size = 50;
    var $maxlength = 254;

    var $min = null;
    var $max = null;

    function Dynamic_TextBox_Property($args)
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

//    function showInput($name = '', $value = null, $size = 0, $maxlength = 0, $id = '', $tabindex = '')
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
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
/*        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
            $data['name']     = $name;
            $data['id']       = $id;
            $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
            $data['tabindex'] = !empty($tabindex) ? ' tabindex="'.$tabindex.'"'  : '';
            $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
            $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
            $data['size']     = !empty($size) ? $size : $this->size;

      $template="textbox";
      return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }

    function showOutput($args = array())
    {
        extract($args);

        if (isset($value)) {
            $value=xarVarPrepHTMLDisplay($value);
        } else {
            $value=xarVarPrepHTMLDisplay($this->value);
        }
        $data=array();

        $data['value'] = $value;

        $template="textbox";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);

    }


	/**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
	 **/
	 function getBasePropertyInfo()
	 {
	 	$baseInfo = array(
                              'id'         => 2,
                              'name'       => 'textbox',
                              'label'      => 'Text Box',
                              'format'     => '2',
                              'validation' => '',
							'source'     => '',
							'dependancies' => '',
							'requiresmodule' => '',
							'aliases' => '',
							// ...
						   );
		return $baseInfo;
	 }

}

?>
