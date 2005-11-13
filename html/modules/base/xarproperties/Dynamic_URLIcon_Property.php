<?php
/**
 * Dynamic URL Icon Property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * Handle the URLIcon property
 *
 * @package dynamicdata
 */
class Dynamic_URLIcon_Property extends Dynamic_TextBox_Property
{
    var $icon;

    function Dynamic_URLIcon_Property($args)
    {
        $this->Dynamic_Property($args);
        // check validation field for icon to use !
        if (!empty($this->validation)) {
           $this->icon = $this->validation;
        } else {
           /* We need to keep this empty now if we want favicon to display if nothing set here
              $this->icon = xarML('Please specify the icon to use in the validation field');
           */
           $this->icon='';
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
            $this->value = $value;
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
        if (empty($value)) {
            $value = 'http://';
        }

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data=array();
/*        return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. xarVarPrepForDisplay($value) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($value) && $value != 'http://' ? ' [ <a href="'.xarVarPrepForDisplay($value).'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;


        $template="";
        return xarTplProperty('base', 'urlicon', 'showinput', $data);
    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();

        if (!empty($value) && $value != 'http://') {
            $link = $value;
            $data['link']=xarVarPrepForDisplay($link);
           // FIXME: $this->icon is supposed to contain the URL already
            if (isset($this->icon))  {
               $data['icon']=trim($this->icon);
                if ($data['icon']<>'') {
                    $data['value']= $value;
                    $data['icon']= $this->icon;
                } elseif ($data['icon']=='') { 
                    /* We don't have a validated icon to display, use favicon */
                    $data['value']= $value;
                    
                    /* FIXME: getfavicon needs to send back nothing if the favicon doens't exist. */
                    $data['icon'] = xarModAPIFunc('base',
                                                  'user',
                                                  'getfavicon',
                                array('url' => $data['value']));
                    if (!($data['icon'])) {
                        /* we'll have to use the default system icon */
                        $data['icon'] = xarTplGetImage('urlicon.gif','base');
                    }
                }
                $template="";
                return xarTplProperty('base', 'urlicon', 'showoutput', $data);
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
        $baseInfo = array(
                          'id'         => 27,
                          'name'       => 'urlicon',
                          'label'      => 'URL Icon',
                          'format'     => '27',
                          'validation' => '',
                          'source'     => '',
                          'dependancies' => '',
                          'requiresmodule' => '',
                          'aliases' => '',
                          'args'         => '',
                          // ...
                         );
        return $baseInfo;
    }

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
        }
        if (!empty($this->validation) &&
            (substr($this->validation,0,1) == '/' ||
             substr($this->validation,0,7) == 'http://' ||
             file_exists($this->validation))) {
            $data['icon'] = xarVarPrepForDisplay($this->validation);
            $data['other'] = '';

        // if we didn't match the above format
        } else {
            $data['icon'] = '';
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }

        // allow template override by child classes
        if (empty($template)) {
            $template = 'urlicon';
        }
        return xarTplProperty('base', $template, 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @returns bool
     * @return bool true if the validation rule could be processed, false otherwise
     */
    function updateValidation($args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['icon']) &&
                    (substr($validation['icon'],0,1) == '/' ||
                     substr($validation['icon'],0,7) == 'http://' ||
                     file_exists($validation['icon']))) {
                    $this->validation = $validation['icon'];

                } elseif (!empty($validation['other'])) {
                    $this->validation = $validation['other'];

                } else {
                    $this->validation = '';
                }
            } else {
                $this->validation = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }
}

?>