<?php
/**
 * Dynamic Select property
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
class Dynamic_Select_Property extends Dynamic_Property
{
    public $options;
    public $func;
    public $itemfunc;
    public $file;
    public $override = false; // allow values other than those in the options

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
        if (isset($value)) {
            $this->value = $value;
        }
        // check if this option really exists
        $isvalid = $this->getOption(true);
        if ($isvalid) {
            return true;
        }
        // check if we allow values other than those in the options
        if ($this->override) {
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
            $data['options'] = $this->getOptions();
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
        $data['onchange'] = isset($onchange) ? $onchange : null; // let tpl decide what to do

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

    // FIXME: this won't work when called by a property from a different module
        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'dropdown';
        }
        return xarTplProperty('base', $template, 'showinput', $data);
        //return $out;
    }

    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            $this->value = $value;
        }
        $data=array();
        $data['value'] = $this->value;
        // get the option corresponding to this value
        $result = $this->getOption();
        // only apply xarVarPrepForDisplay on strings, not arrays et al.
        if (!empty($result) && is_string($result)) {
            $result = xarVarPrepForDisplay($result);
        }
        $data['option'] = array('id' => $this->value,
                                'name' => $result);

    // FIXME: this won't work when called by a property from a different module
        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'dropdown';
        }
        return xarTplProperty('base', $template, 'showoutput', $data);
    }

    function parseValidation($validation = '')
    {
        // if the validation field is an array, we'll assume that this is an array of id => name
        if (is_array($validation)) {
            foreach($validation as $id => $name) {
                array_push($this->options, array('id' => $id, 'name' => $name));
            }

        // if the validation field starts with xarModAPIFunc, we'll assume that this is
        // a function call that returns an array of names, or an array of id => name
        } elseif (preg_match('/^xarModAPIFunc/i',$validation)) {
            // if the validation field contains two ;-separated xarModAPIFunc calls,
            // the second one is used to get/check the result for a single $value
            if (preg_match('/^(xarModAPIFunc.+)\s*;\s*(xarModAPIFunc.+)$/i',$validation,$matches)) {
                $this->func = $matches[1];
                $this->itemfunc = $matches[2];
            } else {
                $this->func = $validation;
            }
/*
            eval('$options = ' . $validation .';');
            if (isset($options) && count($options) > 0) {
                foreach ($options as $id => $name) {
                    array_push($this->options, array('id' => $id, 'name' => $name));
                }
            }
*/

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
/*
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
*/

        // otherwise we'll leave it alone, for use in any subclasses (e.g. min:max in NumberList, or basedir for ImageList, or ...)
        } else {
        }
    }

    /**
     * Retrieve the list of options on demand
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $this->options = array();
        if (!empty($this->func)) {
            // we have some specific function to retrieve the options here
            eval('$items = ' . $this->func .';');
            if (isset($items) && count($items) > 0) {
                foreach ($items as $id => $name) {
                    array_push($this->options, array('id' => $id, 'name' => $name));
                }
                unset($items);
            }

        } elseif (!empty($this->file) && file_exists($this->file)) {
            $fileLines = file($this->file);
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

        } else {

        }

        return $this->options;
    }

    /**
     * Retrieve or check an individual option on demand
     */
    function getOption($check = false)
    {
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }
        if (empty($this->itemfunc)) {
            // we're interested in one of the known options (= default behaviour)
            $options = $this->getOptions();
            foreach ($options as $option) {
                if ($option['id'] == $this->value) {
                    if ($check) return true;
                    return $option['name'];
                }
            }
            if ($check) return false;
            return $this->value;
        }
        // most API functions throw exceptions for empty ids, so we skip those here
        if (empty($this->value)) {
             if ($check) return true;
             return $this->value;
        }
        // use $value as argument for your API function : array('whatever' => $value, ...)
        $value = $this->value;
        eval('$result = ' . $this->itemfunc .';');
        if (isset($result)) {
            if ($check) return true;
            return $result;
        }
        if ($check) return false;
        return $this->value;
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
                          'requiresmodule' => 'base',
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
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 1;
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
        $data['itemfunc'] = '';
        $data['file'] = '';
        $data['options'] = array();
        $data['other'] = '';
        if (!empty($this->func)) {
            $data['func'] = xarVarPrepForDisplay($this->func);
            // only supported when we use func too
            if (!empty($this->itemfunc)) {
                $data['itemfunc'] = xarVarPrepForDisplay($this->itemfunc);
            }
        } elseif (!empty($this->file)) {
            $data['file'] = xarVarPrepForDisplay($this->file);
        } elseif ($numoptions > 0 && $numoptions != $data['static']) {
            $data['options'] = $this->options;
        } else {
            $data['other'] = xarVarPrepForDisplay($this->validation);
        }
        // read-only value set by the property type (for now)
        $data['override'] = $this->override;

    // FIXME: this won't work when called by a property from a different module
        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'dropdown';
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
                if (!empty($validation['func']) && preg_match('/^xarModAPIFunc/i',$validation['func'])) {
                    $this->validation = $validation['func'];
                    // only supported when we use func too
                    if (!empty($validation['itemfunc']) && preg_match('/^xarModAPIFunc/i',$validation['itemfunc'])) {
                        $this->validation .= ';' . $validation['itemfunc'];
                    }

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
