<?php
/**
 * Dynamic SubForm Property
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_SubForm_Property extends Dynamic_Property
{
    var $objectid  = 0;
    var $style     = 'serialized';
    var $title     = '';
    var $link      = '';
    var $where     = ''; // TODO
    var $input     = 1; // TODO
    var $display   = 1; // TODO
    var $fieldlist = null;
    var $objectref = null;
    var $oldvalue  = null;
    var $arguments = array('objectid','style','title','link','where','input','display','fieldlist');
    var $warnings  = '';

    function Dynamic_SubForm_Property($args)
    {
        $this->Dynamic_Property($args);

        // check validation for object, style etc.
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    function validateValue($value = null)
    {
        if (empty($this->objectid)) {
            // nothing to do here
            return true;
        }
        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }

        // retrieve new value for preview + new/modify combinations (in case we miss the preview)
        if (xarVarIsCached('DynamicData.SubForm',$name)) {
            $this->value = xarVarGetCached('DynamicData.SubForm',$name);
            return true;
        }

        // see if we're still dealing with the same item here
        if ($this->style == 'itemid' && !empty($this->title)) {
            $oldname = $name . '_old';
            xarVarFetch($oldname, 'id', $oldvalue, $this->value, XARVAR_NOT_REQUIRED);
        } elseif ($this->style == 'childlist' && !empty($this->link)) {
            $oldname = $name . '_old';
            xarVarFetch($oldname, 'id', $oldvalue, $this->value, XARVAR_NOT_REQUIRED);
            $newname = $name . '_new';
            xarVarFetch($newname, 'id', $newvalue, NULL, XARVAR_NOT_REQUIRED);
        } else {
            $oldvalue = $this->value;
        }

        if (!isset($value)) {
            $value = $this->value;
        }

        $object =& $this->getObject($value);

        if ($this->style == 'serialized') {
            // check user input for the object item
            $isvalid = $object->checkInput();

            $keylist = array_keys($object->properties);

            if (!$isvalid) {
                // check if all values we're interested in are valid
                $this->invalid = '';
                foreach ($keylist as $key) {
                    // we ignore errors in any other properties here
                    if ((empty($this->fieldlist) || in_array($key,$this->fieldlist)) &&
                        !empty($object->properties[$key]->invalid)) {
                        // pass along the invalid message for this property
                        $this->invalid .= ' [' . $object->properties[$key]->label . '] ' . $object->properties[$key]->invalid;
                    }
                }
                if (!empty($this->invalid)) {
                    $this->value = null;
                    return false;
                }
                $this->invalid = null;
            }

            // save the values we're interested in
            $value = array();
            foreach ($keylist as $key) {
                if ((empty($this->fieldlist) || in_array($key,$this->fieldlist)) &&
                    isset($object->properties[$key]->value)) {
                    $value[$key] = $object->properties[$key]->value;
                }
            }
            $this->value = serialize($value);

        } elseif ($this->style == 'itemid' && (empty($value) || $value == $oldvalue)) {
            // check user input for the object item
            $isvalid = $object->checkInput();

            $keylist = array_keys($object->properties);

            if (!$isvalid) {
                // report all invalid values here, even the ones we don't see because of the fieldlist
                $this->invalid = '';
                $this->warnings = '';
                foreach ($keylist as $key) {
                    if (!empty($object->properties[$key]->invalid)) {
                        // pass along the invalid message for this property
                        $this->invalid .= ' [' . $object->properties[$key]->label . '] ' . $object->properties[$key]->invalid;

                        // invalid messages for fields will be shown in the object form by default, so
                        // only show explicit warnings for the fields that aren't in the fieldlist here
                        if (!empty($this->fieldlist) && !in_array($key,$this->fieldlist)) {
                             $this->warnings .= ' [' . $object->properties[$key]->label . '] ' . $object->properties[$key]->invalid;
                        }
                    }
                }
                if (!empty($this->invalid)) {
                    $this->value = null;
                    return false;
                }
                $this->invalid = null;
            }

            // if we don't know we're previewing, we don't really have a choice here
            if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
            if (empty($preview)) {
                if (empty($value) || empty($object->itemid)) {
                    $itemid = $object->createItem();
                } else {
                    $itemid = $object->updateItem();
                }
                if (empty($itemid)) {
                    $this->invalid = 'object';
                    return false;
                }
                $value = $itemid;
                // save new value for preview + new/modify combinations (in case we miss the preview)
                xarVarSetCached('DynamicData.SubForm',$name,$value);
            }
            $this->value = $value;

        } elseif ($this->style == 'childlist' && empty($value) && !empty($newvalue)) {
            $this->value = $newvalue;

        } else {
            // just accept the new value
            $this->value = $value;
        }
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        foreach ($this->arguments as $item) {
            if (isset($$item)) {
                $this->$item = $$item;
            }
        }
// CHECKME: set the value to the itemid by default for childlist ?
/*
        if (!empty($this->objectid) && $this->style == 'childlist' &&
            empty($value) && !empty($this->_itemid)) {
            $value = $this->_itemid;
        }
*/
        $data = array();
        $data['name']      = $name;
        $data['id']        = $id;
        $data['tabindex']  = !empty($tabindex) ? $tabindex : 0;
        // invalid messages for fields will be shown in the object form by default, so
        // only show explicit warnings for the fields that aren't in the fieldlist here
        $data['invalid']   = !empty($this->warnings) ? xarML('Invalid #(1)', $this->warnings) :'';

        foreach ($this->arguments as $item) {
            $data[$item]   = $this->$item;
        }
        $data['value']     = $value;
        if (!empty($this->objectid)) {
            $data['object'] =& $this->getObject($value);

            // get the list of available items if requested
            if ($this->style == 'itemid' && !empty($this->title)) {
                $mylist =& Dynamic_Object_Master::getObjectList(array('objectid'  => $this->objectid,
                                                                      'fieldlist' => array($this->title),
                                                                      'where'     => $this->where));
                $data['dropdown'] = $mylist->getItems();
            } elseif ($this->style == 'childlist' && !empty($this->link)) {
                // pick some field to count with
                if (!empty($data['object']->primary)) {
                    // preferably the primary key
                    $data['count'] = $data['object']->primary;
                } else {
                    // otherwise any field other than the link field :-)
                    foreach (array_keys($data['object']->properties) as $key) {
                        if ($key != $this->link) {
                            $data['count'] = $key;
                            break;
                        }
                    }
                }
                // get the number of items per link field value
                $mylist =& Dynamic_Object_Master::getObjectList(array('objectid'  => $this->objectid,
                                                                      'fieldlist' => array($this->link,'COUNT('.$data['count'].')'),
                                                                      'groupby'   => array($this->link)));
                $data['dropdown'] = $mylist->getItems();
            } else {
                $data['dropdown'] = array();
            }
        }

        if (!isset($template)) {
            $template = 'subform';
        }
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data ,$template);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        foreach ($this->arguments as $item) {
            if (isset($$item)) {
                $this->$item = $$item;
            }
        }
// CHECKME: set the value to the itemid by default for childlist ?
/*
        if (!empty($this->objectid) && $this->style == 'childlist' &&
            empty($value) && !empty($this->_itemid)) {
            $value = $this->_itemid;
        }
*/
        $data = array();
        $data['style'] = $this->style;
        $data['value'] = $value;
        if (!empty($this->objectid) && !empty($value)) {
            $data['object'] =& $this->getObject($value);
        }

        if (!isset($template)) {
            $template = 'subform';
        }
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);
    }

    function parseValidation($validation = '')
    {
        if (is_array($validation)) {
            $fields = $validation;
        } else {
            $fields = @unserialize($validation);
        }
        if (!empty($fields) && is_array($fields)) {
            foreach ($this->arguments as $item) {
                if (!empty($fields[$item])) {
                    $this->$item = $fields[$item];
                }
            }
        }
    }

    function &getObject($value)
    {
        if (isset($this->objectref)) {
            $myobject =& $this->objectref;
            if ($value == $this->oldvalue) {
                return $myobject;
            }
        } else {
            $myobject = null;
        }
        $this->oldvalue = $value;
        switch ($this->style) {
            case 'childlist':
                if (!isset($myobject)) {
                    if (empty($this->fieldlist)) {
                        $status = 1; // skip the display-only properties
                    } else {
                        $status = null;
                    }
                    $myobject =& Dynamic_Object_Master::getObjectList(array('objectid'  => $this->objectid,
                                                                            'fieldlist' => $this->fieldlist,
                                                                            'status'    => $status));
                } else {
                    // reset the list of item ids
                    $myobject->itemids = array();
                }
                if (!empty($this->link) && !empty($value)) {
                    if (is_numeric($value)) {
                        $where = $this->link . ' eq ' . $value;
                    } else {
                        $where = $this->link . " eq '" . $value . "'";
                    }
                    $myobject->getItems(array('where' => $where));
                } else {
                    // re-initialize the items array
                    $myobject->items = array();
                }
                break;

            case 'itemid':
                if (!isset($myobject)) {
                    $myobject =& Dynamic_Object_Master::getObject(array('objectid'  => $this->objectid,
                                                                        'fieldlist' => $this->fieldlist));
                }
                if (!empty($value)) {
                    $myobject->getItem(array('itemid' => $value));
                }
                break;

            case 'serialized':
            default:
                if (!isset($myobject)) {
                    $myobject =& Dynamic_Object_Master::getObject(array('objectid'  => $this->objectid,
                                                                        'fieldlist' => $this->fieldlist));
                } else {
                    // initialise the properties again
                    foreach (array_keys($myobject->properties) as $propname) {
                        $myobject->properties[$propname]->value = $myobject->properties[$propname]->default;
                    }
                }
                if (empty($value)) {
                    $value = array();
                } elseif (!is_array($value)) {
                    $out = @unserialize($value);
                    if (!empty($out) && is_array($out)) {
                        $value = $out;
                    } else {
                        $value = array(); // can't do anything with this
                    }
                }
                foreach ($value as $key => $val) {
                    if (isset($myobject->properties[$key])) {
                        $myobject->properties[$key]->setValue($val);
                    }
                }
                break;
        }
        if (!isset($this->objectref)) {
            $this->objectref =& $myobject;
        }
        return $myobject;
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
                          'id'         => 997,
                          'name'       => 'subform',
                          'label'      => 'Sub Form',
                          'format'     => '997',
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
        $data['size']       = !empty($size) ? $size : 50;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        foreach ($this->arguments as $item) {
            $data[$item] = $this->$item;
        }
        if (!empty($this->objectid)) {
            $data['properties'] = Dynamic_Property_Master::getProperties(array('objectid' => $this->objectid));
        } else {
            $data['properties'] = array();
        }
        $data['other']     = '';

        $data['styles']    = array('serialized' => xarML('Local value'),
                                   'itemid'     => xarML('Link to item'),
                                   'childlist'  => xarML('List of children'));

        // allow template override by child classes
        if (!isset($template)) {
            $template = 'subform';
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
                $data = array();
                foreach ($this->arguments as $item) {
                    if (!empty($validation[$item])) {
                        $data[$item] = $validation[$item];
                    }
                }
                $this->validation = serialize($data);

            } else {
                $this->validation = $validation;
            }
        }
// FIXME: remove this once we switch to TEXT for the validation field
        if (strlen($this->validation) > 254) {
            $this->invalid = 'validation : too long';
            return false;
        }
        // tell the calling function that everything is OK
        return true;
    }
}

?>
