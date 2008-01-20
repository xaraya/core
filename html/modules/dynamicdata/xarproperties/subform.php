<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Handle subform property
 */
class SubFormProperty extends DataProperty
{
    public $id         = 997;
    public $name       = 'subform';
    public $desc       = 'Sub Form';
    public $reqmodules = array('dynamicdata');

    public $objectid  = 0;
    public $objectname  = '';
    public $style     = 'serialized';
    public $title     = '';
    public $link      = '';
    public $where     = ''; // TODO
    public $input     = 1;
    public $display   = 1; // TODO
    public $repeat    = 1;
    public $fieldlist = null;
    public $objectref = null;
    public $oldvalue  = null;
    public $arguments = array('objectname','style','title','link','where','input','display','fieldlist','repeat');
    public $warnings  = '';

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        if (!xarVarFetch('fieldprefix', 'isset', $this->fieldprefix,  NULL, XARVAR_DONT_SET)) {return;}
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
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
/*
        if (xarVarIsCached('DynamicData.SubForm',$name)) {
            $this->value = xarVarGetCached('DynamicData.SubForm',$name);
            return true;
        }
*/
        // see if we're still dealing with the same item here
        if ($this->style == 'itemid' && !empty($this->title)) {
            $oldname = $name . '_old';
            xarVarFetch($oldname, 'id', $oldvalue, $this->value, XARVAR_NOT_REQUIRED);
        } elseif ($this->style == 'parentid' && !empty($this->link)) {
            $oldname = $name . '_old';
            xarVarFetch($oldname, 'id', $oldvalue, $this->value, XARVAR_NOT_REQUIRED);
            $newname = $name . '_new';
            xarVarFetch($newname, 'id', $newvalue, NULL, XARVAR_NOT_REQUIRED);
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

            $object =& DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                'fieldlist' => $this->fieldlist));
            $i=0;
            $values = array();
            $prefix = empty($this->fieldprefix) ? $name : $this->fieldprefix;

            // We need to figure out how many items we need to process
            // FIXME: is there a better way to do this?
            $postarray = array_keys($_POST);
            $items = array();
            foreach ($postarray as $key) {
                preg_match("/Item_[0-9]+_/", $key, $matches);
                if (isset($matches[0])) {
                    $str = explode("_",$matches[0]);
                    $items[$str[1]] = (int)$str[1];
                }
            }

            foreach ($items as $i) {
                // check user input for the object item - using the current name as field prefix if non is given

                $isvalid = $object->checkInput(array('fieldprefix' => "Item_" . $i . "_" . $prefix));

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
                $values[] = $value;
            }
            $this->value = serialize($values);
        } elseif ($this->style == 'itemid' && (empty($value) || $value == $oldvalue) && !empty($this->input)) {
            // check user input for the object item - using the current name as field prefix
            $isvalid = $object->checkInput(array('fieldprefix' => $name));

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
            if (empty($preview))
            {
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

        } elseif ($this->style == 'parentid' && !empty($value) && $value == $oldvalue && !empty($this->input)) {

            // check if we want to create new child items or not
            xarVarFetch($name . '_dd_create', 'array', $dd_create, NULL, XARVAR_NOT_REQUIRED);
            if (!empty($dd_create) && !empty($dd_create[$this->objectid])) {
                $docreate = 1;
            } else {
                $docreate = 0;
            }

            $childitems = array();
            foreach ($object->properties as $property)
            {
                $propertyname = $property->name;
                $propertyid = $property->id;
                // check user input for the object item - using the current name as field prefix
                $propertyid = $name .'_dd_'.$propertyid;
                unset($propertyvaluearray);
                xarVarFetch($propertyid, 'array', $propertyvaluearray, NULL, XARVAR_NOT_REQUIRED);
                if (!empty($propertyvaluearray)) {
                    foreach ($propertyvaluearray as $id => $val) {
                        if (empty($id) && !$docreate) continue;
                        if (!isset($childitems[$id])) {
                            $childitems[$id] = array();
                        }
                        $childitems[$id][$propertyname] = $val;
                    }
                }
            }

            // make sure the link field is included in the field list
            if (!empty($this->fieldlist) && !in_array($this->link,$this->fieldlist)) {
                array_push($this->fieldlist,$this->link);
            }
            // check user input for the object item
            $myobject =& DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                'fieldlist' => $this->fieldlist));
            $keylist = array_keys($myobject->properties);
            // report all invalid values here, even the ones we don't see because of the fieldlist
            $this->invalid = '';
            $this->warnings = '';
            foreach ($childitems as $id => $item) {
                $item['itemid'] = $id;
                // set parent id in link field if necessary
                if (!isset($item[$this->link])) {
                    $item[$this->link] = $value;
                    $childitems[$id][$this->link] = $value;
                }
                $isvalid = $myobject->checkInput($item);
                if ($isvalid) {
                    // Note: this also sets new items with id 0 on preview
                    foreach ($keylist as $key) {
                        if (isset($item[$key])) {
                            $object->properties[$key]->setItemValue($id,$item[$key]);
                        }
                    }
                } else {
                    foreach ($keylist as $key) {
                        if (!empty($myobject->properties[$key]->invalid)) {
                            // pass along the invalid message for this property
                            $this->invalid .= ' [' . $myobject->properties[$key]->label . '] ' . $myobject->properties[$key]->invalid;

                            // invalid messages for fields will be shown in the object form by default, so
                            // only show explicit warnings for the fields that aren't in the fieldlist here
                            if (!empty($this->fieldlist) && !in_array($key,$this->fieldlist)) {
                                $this->warnings .= ' [' . $myobject->properties[$key]->label . '] ' . $myobject->properties[$key]->invalid;
                            }
                        }
                    }
                }
            }

            if (!empty($this->invalid)) {
                $this->value = null;
                return false;
            }
            $this->invalid = null;

            // if we don't know we're previewing, we don't really have a choice here
            if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
            if (empty($preview))
            {
                foreach ($childitems as $id => $item) {
                    $item['itemid'] = $id;
                    if (!empty($id)) {
                        $id = $myobject->updateItem($item);
                    } elseif ($docreate) {
                        $id = $myobject->createItem($item);
                    }
                }
            }

            // we only store the parent id here
            $this->value = $value;

//        } elseif ($this->style == 'childlist' && (empty($value) || $value == $oldvalue)) {
        } elseif ($this->style == 'childlist' && (empty($value) || !empty($newvalue)) && !empty($this->input)) {

            // check if we want to create new child items or not
            xarVarFetch($name . '_dd_create', 'array', $dd_create, NULL, XARVAR_NOT_REQUIRED);
            if (!empty($dd_create) && !empty($dd_create[$this->objectid])) {
                $docreate = 1;
            } else {
                $docreate = 0;
            }

            $childitems = array();
            foreach ($object->properties as $property)
            {
                $propertyname = $property->name;
                $propertyid = $property->id;
                // check user input for the object item - using the current name as field prefix
                $propertyid = $name .'_dd_'.$propertyid;
                unset($propertyvaluearray);
                xarVarFetch($propertyid, 'array', $propertyvaluearray, NULL, XARVAR_NOT_REQUIRED);
                if (!empty($propertyvaluearray)) {
                    foreach ($propertyvaluearray as $id => $val) {
                        if (empty($id) && !$docreate) continue;
                        if (!isset($childitems[$id])) {
                            $childitems[$id] = array();
                        }
                        $childitems[$id][$propertyname] = $val;
                    }
                }
            }

            // make sure the link field is included in the field list
            if (!empty($this->fieldlist) && !in_array($this->link,$this->fieldlist)) {
                array_push($this->fieldlist,$this->link);
            }
            // check user input for the object item
            $myobject =& DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                'fieldlist' => $this->fieldlist));
            $keylist = array_keys($myobject->properties);
            // report all invalid values here, even the ones we don't see because of the fieldlist
            $this->invalid = '';
            $this->warnings = '';
            foreach ($childitems as $id => $item) {
                $item['itemid'] = $id;
                // set item id in link field if necessary (not parent id here)
                if (!isset($item[$this->link])) {
                    $item[$this->link] = $id;
                    $childitems[$id][$this->link] = $id;
                }
                $isvalid = $myobject->checkInput($item);
                if ($isvalid) {
                    // Note: this also sets new items with id 0 on preview
                    foreach ($keylist as $key) {
                        if (isset($item[$key])) {
                            $object->properties[$key]->setItemValue($id,$item[$key]);
                        }
                    }
                } else {
                    foreach ($keylist as $key) {
                        if (!empty($myobject->properties[$key]->invalid)) {
                            // pass along the invalid message for this property
                            $this->invalid .= ' [' . $myobject->properties[$key]->label . '] ' . $myobject->properties[$key]->invalid;

                            // invalid messages for fields will be shown in the object form by default, so
                            // only show explicit warnings for the fields that aren't in the fieldlist here
                            if (!empty($this->fieldlist) && !in_array($key,$this->fieldlist)) {
                                $this->warnings .= ' [' . $myobject->properties[$key]->label . '] ' . $myobject->properties[$key]->invalid;
                            }
                        }
                    }
                }
            }

            if (!empty($this->invalid)) {
                $this->value = null;
                return false;
            }
            $this->invalid = null;

            $value = array();
            // if we don't know we're previewing, we don't really have a choice here
            if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
            if (empty($preview))
            {
                foreach ($childitems as $id => $item) {
                    $item['itemid'] = $id;
                    if (!empty($id)) {
                        $id = $myobject->updateItem($item);
                    } elseif ($docreate) {
                        // this will give us the new child itemid
                        $id = $myobject->createItem($item);
                    }
                    // Note: we need to make sure we're using the same type here
                    $value[] = (string) $id;
                }
            } else {
                foreach ($childitems as $id => $item) {
                    if (!empty($id)) {
                        // Note: we need to make sure we're using the same type here
                        $value[] = (string) $id;
                    }
                }
            }

            // we store the serialized list of child itemids here
            $this->value = serialize($value);

        } else {
            // just accept the new value
            $this->value = $value;
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        extract($data);

        if (!empty($validation)) $this->parseConfiguration($validation);

        if (!isset($value)) $value = $this->value;
        if (!isset($name)) $name = 'dd_'.$this->id;

        foreach ($this->arguments as $item) {
            if (isset($$item)) {
                $this->$item = $$item;
            }
        }

        // default to the current itemid if necessary
        if (!empty($this->objectid) && $this->style == 'parentid' &&
            empty($value) && !empty($this->title) && !empty($this->_itemid)) {
            $value = $this->_itemid;
        }

        // invalid messages for fields will be shown in the object form by default, so
        // only show explicit warnings for the fields that aren't in the fieldlist here
        $data['invalid']   = !empty($this->warnings) ? xarML('Invalid #(1)', $this->warnings) :'';

        // Prepare the properties for the form
        foreach ($this->arguments as $item) {
            $data[$item]   = $this->$item;
        }
        $data['value']     = $value;

        // use the current property name/dd_[id] as default prefix for the input fields in the sub-object
        if (!isset($data['fieldprefix'])) $data['fieldprefix'] = $name;

        if (!empty($this->objectid)) {
            $data['object'] =& $this->getObject($value);
            $data['emptyobject'] = $myobject = DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                            'fieldlist' => $this->fieldlist));

            // get the list of available items if requested
            if ($this->style == 'itemid' && !empty($this->title)) {
                $mylist =& DataObjectMaster::getObjectList(array('objectid'  => $this->objectid,
                                                                      'fieldlist' => array($this->title),
                                                                      'where'     => $this->where));
                $data['dropdown'] = $mylist->getItems();

            } elseif (($this->style == 'childlist' || $this->style == 'parentid') &&
                       !empty($this->link)) {
                if (empty($this->title)) {
                    // pick some field to count with
                    if (!empty($data['object']->primary)) {
                        // preferably the primary key
                        $data['count'] = $data['object']->primary;
                    } else {
                        // CHECKME: what is this about really? aren't we talking about different tables
                        // otherwise any field other than the link field :-)
                        foreach (array_keys($data['object']->properties) as $key) {
                            if ($key != $this->link) {
                                $data['count'] = $key;
                                break;
                            }
                        }
                    }
                } else {
                    $data['count'] = $data['object']->primary;
                }
                // get the number of items per link field value
                $mylist =& DataObjectMaster::getObjectList(array('objectid'  => $this->objectid,
                                                                      'fieldlist' => array($this->link,'COUNT('.$data['count'].')'),
                                                                      'groupby'   => array($this->link)));
                $data['dropdown'] = $mylist->getItems();
            } else {
                $data['dropdown'] = array();
            }
        }

        return parent::showInput($data);
    }

    public function showOutput(Array $args = array())
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

/*
        if (!empty($this->objectid) && $this->style == 'parentid' &&
            empty($value) && !empty($this->title) && !empty($this->_itemid)) {
            $value = $this->_itemid;
        }
*/
        $data = array();
        $data['style'] = $this->style;
        $data['value'] = $value;
        if (!empty($this->objectid) && !empty($value)) {
            $data['object'] =& $this->getObject($value);
        }

        $module    = empty($module)   ? $this->getModule()   : $module;
        $template  = empty($template) ? $this->getTemplate() : $template;

        return xarTplProperty($module, $template, 'showoutput', $data);
    }

    function &getObject($value)
    {
        if (isset($this->objectref)) {
            $myobject =& $this->objectref;
            // Note: be careful that serialized values have the same type here (cfr. childlist)
            if ($value == $this->oldvalue) {
                return $myobject;
            }
        } else {
            $myobject = null;
        }
        $this->oldvalue = $value;
        switch ($this->style) {
            case 'parentid':
                if (!isset($myobject)) {
                    if (empty($this->fieldlist)) {
                        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE; // skip the display-only properties
                    } else {
                        $status = null;
                    }
                    $myobject =& DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                            'fieldlist' => $this->fieldlist,
                                                                            'status'    => $status));
                } else {
                    // reset the list of item ids
                    $myobject->itemids = array();
                }

                $repeats = $this->repeat ? $this->repeat : 1;
                for ($i=0;$i<$repeats;$i++) {
                    $objects[] = $myobject;
                }
//                var_dump($this->link);exit;
//                $data['repeats'] = $this->repeat;
                if (!empty($this->link) && !empty($value))
                {
                    if (is_numeric($value)) {
                        $where = $this->link . ' eq ' . $value;
                    } else {
                        $where = $this->link . " eq '" . $value . "'";
                    }
                    $myobject->getItems(array('where' => $where));
                } else {
                    // re-initialize the items array
//                    $myobject->items = array();
                }
                break;

            case 'childlist':
                if (!isset($myobject)) {
                    if (empty($this->fieldlist)) {
                        $status = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE; // skip the display-only properties
                    } else {
                        $status = null;
                    }
                    $myobject =& DataObjectMaster::getObjectList(array('objectid'  => $this->objectid,
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
                        $unserializedvalue = unserialize($value);
                        if( $unserializedvalue === false )
                        {
                            $where = $this->link . " eq '" . $value . "'";
                        } elseif (count($unserializedvalue) > 0) {
                            if( is_numeric($unserializedvalue[0]) )
                            {
                                $where = $this->link . ' IN (' . implode(",",$unserializedvalue) . ')';
                            } else {
                                $where = $this->link . " IN ('" . implode('\',\'',$unserializedvalue) . "')";
                            }
                        }
                    }
                    if(isset($where)) {
                        $myobject->getItems(array('where' => $where));
                    } else {
                        // re-initialize the items array
                        $myobject->items = array();
                    }
                } else {
                    // re-initialize the items array
                    $myobject->items = array();
                }
                break;

            case 'itemid':
                if (!isset($myobject)) {
                    $myobject =& DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                        'fieldlist' => $this->fieldlist));
                }
                if (!empty($value)) {
                    $myobject->getItem(array('itemid' => $value));
                }
                break;

            case 'serialized':
            default:
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
                $objects = array();
                if (empty($value)) {
                    if (!isset($myobject)) {
                        $myobject = DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                            'fieldlist' => $this->fieldlist));
                    } else {
                        // initialise the properties again
                        foreach (array_keys($myobject->properties) as $propname) {
                            $myobject->properties[$propname]->value = $myobject->properties[$propname]->defaultvalue;
                        }
                    }
                    $repeats = $this->repeat ? $this->repeat : 1;
                    for ($i=0;$i<$repeats;$i++) {
                        $objects[] = $myobject;
                    }
                } else {
                    foreach ($value as $vals) {
                        $myobject = DataObjectMaster::getObject(array('objectid'  => $this->objectid,
                                                                        'fieldlist' => $this->fieldlist));
                        foreach ($vals as $key => $val) {
                            if (isset($myobject->properties[$key])) {
                                $myobject->properties[$key]->setValue($val);
                            }
                        }
                        $objects[] = $myobject;
                    }
                }
                return $objects;
                break;
        }
        if (!isset($this->objectref)) {
            $this->objectref =& $myobject;
        }
        return $myobject;
    }

    public function parseConfiguration($validation = '')
    {
        if (is_array($validation)) {
            $fields = $validation;
        } else {
            $fields = unserialize($validation);
        }
        if (!empty($fields) && is_array($fields)) {
            foreach ($this->arguments as $item) {
                if (isset($fields[$item])) {
                    // FIXME: needs to be a better way to convert between objectname and objectid
                    if ($item == 'objectname') {
                        $info = DataObjectMaster::getObjectInfo(array('name' => $fields[$item]));
                        $this->objectid = $info['objectid'];
                    }
                    $this->$item = $fields[$item];
                } elseif ($item == 'input' && isset($fields[$item])) {
                    $this->$item = $fields[$item];
                }
            }
        }
    }

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @param $args['repetitions'] number of repetitions of this subform to be displayed on forms
     * @return string containing the HTML (or other) text to output in the BL template
     */
    public function showConfiguration(Array $args = array())
    {
        extract($args);
        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['size']       = !empty($size) ? $size : 50;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->configuration = $validation;
            $this->parseConfiguration($validation);
        }
        foreach ($this->arguments as $item) {
            $data[$item] = $this->$item;
        }
        if (!empty($this->objectname)) {
            $object = DataObjectMaster::getObject(array('name' => $this->objectname));
            $data['objectid'] = $object->objectid;
            $data['properties'] = $object->getProperties();
        } else {
            $this->objectid = 0;
            $data['objectid'] = 0;
            $data['properties'] = array();
        }
        $data['other']     = '';

        $data['styles']    = array('serialized' => xarML('Local value'),
                                   'itemid'     => xarML('Link to item'),
                                   'childlist'  => xarML('List of children (child ids)'),
                                   'parentid'   => xarML('List of children (parent id)'));

        // allow template override by child classes
        $module    = empty($module)   ? $this->getModule()   : $module;
        $template  = empty($template) ? $this->getTemplate() : $template;

        return xarTplProperty($module, $template, 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @return bool true if the validation rule could be processed, false otherwise
     */
    public function updateConfiguration(Array $args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        $name = empty($name) ? 'dd_'.$this->id : $name;

        // do something with the validation and save it in $this->configuration
        if (isset($validation)) {
            if (is_array($validation)) {
                $data = array();
                foreach ($this->arguments as $item) {
                    if (isset($validation[$item])) {
                        // FIXME: needs to be a better way to convert between objectname and objectid
                        if ($item == 'objectname') {
                            $info = DataObjectMaster::getObjectInfo(array('objectid' => $validation[$item]));
                            $validation[$item] = $info['name'];
                        }
                        $data[$item] = $validation[$item];
                    } elseif ($item == 'input' && isset($validation[$item])) {
                        $data[$item] = $validation[$item];
                    }
                }
                $this->configuration = serialize($data);

            } else {
                $this->configuration = $validation;
            }
        }

        return true;
    }
}
?>
