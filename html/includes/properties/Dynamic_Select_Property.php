<?php
/**
 * File: $Id$
 *
 * Dynamic Data Select Property
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
 * Handle the select property
 *
 * @package dynamicdata
 */
class Dynamic_Select_Property extends Dynamic_Property
{
    var $options;
    var $func;
    var $file;
    var $override = false; // allow values other than those in the options

    function Dynamic_Select_Property($args)
    {
        $this->Dynamic_Property($args);
        if (!isset($this->options)) {
            $this->options = array();
        }
        // options may be set in one of the child classes
        if (count($this->options) == 0 && !empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $this->value = $value;
                return true;
            }
        }
        // check if we allow values other than those in the options
        if ($this->override) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data=array();

        if (!isset($value)) {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }
        if (!isset($options) || count($options) == 0) {
            $data['options'] = $this->options;
        } else {
            $data['options'] = $options;
        }
        // check if we need to add the current value to the options
        if (!empty($data['value']) && $this->override) {
            $found = false;
            foreach ($data['options'] as $option) {
                if ($option['id'] == $data['value']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['options'][] = array('id' => $data['value'], 'name' => $data['value']);
            }
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }
        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
            $data['id']= $id;
        }
        /*$out = '<select' .
               ' name="' . $name . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';

        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected="selected">'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }
        */

        /*$out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        */

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';


        $template="dropdown";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);
        //return $out;
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        //$out = '';
        $data=array();
        // TODO: support multiple selection
        $join = '';
        foreach ($this->options as $option) {
            if ($option['id'] == $value) {
                $data['option']['name']=xarVarPrepForDisplay($option['name']);
                //$out .= $join . xarVarPrepForDisplay($option['name']);
                $join = ' | ';
            }
        }
        // check if we need to show the current value instead
        if (!empty($value) && $this->override && empty($join)) {
            $data['option']['name'] = xarVarPrepForDisplay($value);
        }

        $template="dropdown";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
        // return $out;
    }

    function parseValidation($validation = '')
    {
        // if the validation field starts with xarModAPIFunc, we'll assume that this is
        // a function call that returns an array of names, or an array of id => name
        if(is_array($validation)) {
            foreach($validation as $id => $name) {
                array_push($this->options, array('id' => $id, 'name' => $name));
            }
        } elseif (preg_match('/^xarModAPIFunc/i',$validation)) {
            $this->func = $validation;
            eval('$options = ' . $validation .';');
            if (isset($options) && count($options) > 0) {
                foreach ($options as $id => $name) {
                    array_push($this->options, array('id' => $id, 'name' => $name));
                }
            }

        // or if it contains a ; or a , we'll assume that this is a list of name1;name2;name3 or id1,name1;id2,name2;id3,name3
        } elseif (strchr($validation,';') || strchr($validation,',')) {
            // allow escaping \; for values that need a semi-colon
            $options = preg_split('/(?<!\\\);/', $validation);
            foreach ($options as $option) {
                $option = strtr($option,array('\;' => ';'));
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = strtr($id,array('\,' => ','));
                    $name = strtr($name,array('\,' => ','));
                    array_push($this->options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = strtr($option,array('\,' => ','));
                    array_push($this->options, array('id' => $option, 'name' => $option));
                }
            }

        // or if it contains a data file path, load the options from the file.  File will contain one or more lines each containing a list specified as name1;name2;name3 or id1,name1;id2,name2;id3,name3
        } elseif (preg_match('/^{file:(.*)}/',$validation, $fileMatch)) {
            $filePath = $fileMatch[1];
            $this->file = $filePath;
            $fileLines = file($filePath);
            foreach ($fileLines as $option)
            {
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = strtr($id,array('\,' => ','));
                    $name = strtr($name,array('\,' => ','));
                    array_push($this->options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = strtr($option,array('\,' => ','));
                    array_push($this->options, array('id' => $option, 'name' => $option));
                }
            }

        // otherwise we'll leave it alone, for use in any subclasses (e.g. min:max in NumberList, or basedir for ImageList, or ...)
        } else {
        }
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
                          'id'         => 6,
                          'name'       => 'dropdown',
                          'label'      => 'Dropdown List',
                          'format'     => '6',
                          'validation' => '',
                          'source'     => '',
                          'dependancies' => '',
                          'requiresmodule' => '',
                          'aliases'        => '',
                          'args'           => serialize($args)
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

        // hard-coded options etc.
        $data['static'] = count($this->options);

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        // new number of options
        $numoptions = count($this->options);

        $data['func'] = '';
        $data['file'] = '';
        $data['options'] = array();
        $data['other'] = '';
        if (!empty($this->func)) {
            $data['func'] = xarVarPrepForDisplay($this->func);
        } elseif (!empty($this->file)) {
            $data['file'] = xarVarPrepForDisplay($this->file);
        } elseif ($numoptions > 0 && $numoptions != $data['static']) {
            $data['options'] = $this->options;
        } else {
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }
        // read-only value set by the property type (for now)
        $data['override'] = $this->override;

        // allow template override by child classes
        if (!isset($template)) {
            $template = 'dropdown';
        }
        return xarTplModule('dynamicdata', 'admin', 'validation', $data, $template);
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
                if (!empty($validation['func']) && preg_match('/^xarModAPIFunc/i',$validation['func'])) {
                    $this->validation = $validation['func'];

                } elseif (!empty($validation['file']) && file_exists($validation['file'])) {
                    $this->validation = '{file:' . $validation['file'] . '}';

                } elseif (!empty($validation['other'])) {
                    $this->validation = $validation['other'];

                } elseif (!empty($validation['options'])) {
                    // remove last option if empty
                    $last = count($validation['options']) - 1;
                    if (empty($validation['options'][$last]['name'])) {
                        array_pop($validation['options']);
                    }
                    $options = array();
                    foreach ($validation['options'] as $id => $option) {
                        $option['name'] = strtr($option['name'],array(';' => '\;', ',' => '\,'));
                        if (!isset($option['id'])) {
                            $options[] = $option['name'];
                        } else {
                            $option['id'] = strtr($option['id'],array(';' => '\;', ',' => '\,'));
                            $options[] = $option['id'].','.$option['name'];
                        }
                    }
                    $this->validation = join(';',$options);

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
