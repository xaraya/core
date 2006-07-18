<?php
/**
 * Dynamic Item Type property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_NumberBox_Property.php";

/**
 * Handle the item type property
 *
 * @package dynamicdata
 */
class Dynamic_ItemType_Property extends Dynamic_NumberBox_Property
{
    public $module   = ''; // get itemtypes for this module with getitemtypes()
    public $itemtype = null; // get items for this module+itemtype with getitemlinks()
    public $func     = null; // specific API call to retrieve a list of items
    public $options  = array();

    function __construct($args)
    {
        parent::__construct($args);
        // Tplmodule and template are by default those of the numberbox (whatever they may be)
        $this->filepath   = 'modules/dynamicdata/xarproperties';

        // options may be set in one of the child classes
        if (count($this->options) == 0 && !empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('dynamicdata');
        $info->id   = 20;
        $info->name = 'itemtype';
        $info->desc = 'Item Type';

        return $info;
    }

    function validateValue($value = null)
    {
        if (empty($this->module)) {
            // let Dynamic_NumberBox_Property handle the rest
            return parent::validateValue($value);
        }
        if (isset($value)) {
            $this->value = $value;
        }
        // check if this option really exists
        $isvalid = $this->getOption(true);
        if (!$isvalid) {
            $this->invalid = xarML('item type');
            $this->value = null;
            return false;
        } else {
            return true;
        }
    }

    function showInput($data = array())
    {
        if (!empty($data)) {
            $this->setArguments($data);
        }
        if (empty($this->module)) {
            // let Dynamic_NumberBox_Property handle the rest
            return parent::showInput($data);
        }

        $data['options']  = $this->getOptions();
        if (empty($data['options'])) {
            // let Dynamic_NumberBox_Property handle the rest
            return parent::showInput($data);
        }
        $data['value']    = $this->value; // cfr. setArguments()
        $data['name']     = !empty($this->fieldname) ? $this->fieldname : 'dd_' . $this->id;
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do

        // Once we get here, we dont let our parent dictate what we need anymore for rendering
        $this->tplmodule = 'dynamic_data';
        $this->template = 'itemtype';
        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (!empty($data)) {
            $this->setArguments($data);
        }
        if (empty($this->module)) {
            // let Dynamic_NumberBox_Property handle the rest
            return parent::showOutput($data);
        }

        $data['value'] = $this->value;
        $data['option'] = array('id' => $this->value,
                                'name' => $this->getOption());

        // Once we get here, we dont let our parent dictate what we need anymore for rendering
        $this->tplmodule = 'dynamic_data';
        $this->template = 'itemtype';
        return parent::showOutput($data);
    }

    function setArguments($args = array())
    {
        if (!empty($args['module']) &&
            preg_match('/^\w+$/',$args['module']) &&
            xarModIsAvailable($args['module'])) {

            $this->module = $args['module'];
            if (isset($args['itemtype']) && is_numeric($args['itemtype'])) {
                $this->itemtype = $args['itemtype'];
            }
            if (isset($args['func']) && is_string($args['func'])) {
                $this->func = $args['func'];
            }
        }
        if (!empty($args['name'])) {
            $this->fieldname = $args['name'];
        }
        // could be 0 here
        if (isset($args['value']) && is_numeric($args['value'])) {
            $this->value = $args['value'];
        }
        if (!empty($args['options']) && is_array($args['options'])) {
            $this->options = $args['options'];
        }
    }

    /**
     * Possible formats
     *
     *   module
     *       show the list of itemtypes for that module via getitemtypes()
     *       E.g. "articles" = the list of publication types in articles
     *
     *   module.itemtype
     *       show the list of items for that module+itemtype via getitemlinks()
     *       E.g. "articles.1" = the list of articles in publication type 1 News Articles
     *
     *   module.itemtype:xarModAPIFunc(...)
     *       show some list of "item types" for that module via xarModAPIFunc(...)
     *       and use itemtype to retrieve individual items via getitemlinks()
     *       E.g. "articles.1:xarModAPIFunc('articles','user','dropdownlist',array('ptid' => 1, 'where' => ...))"
     *       = some filtered list of articles in publication type 1 News Articles
     *
     *   TODO: support 2nd API call to retrieve the item in case getitemlinks() isn't supported
     */
    function parseValidation($validation = '')
    {
        // see if the validation field contains a valid module name
        if (preg_match('/^\w+$/',$validation) &&
            xarModIsAvailable($validation)) {

            $this->module = $validation;

        } elseif (preg_match('/^(\w+)\.(\d+)$/',$validation,$matches) &&
                  xarModIsAvailable($matches[1])) {

            $this->module = $matches[1];
            $this->itemtype = $matches[2];

        } elseif (preg_match('/^(\w+)\.(\d+):(xarModAPIFunc.*)$/i',$validation,$matches) &&
                  xarModIsAvailable($matches[1])) {

            $this->module = $matches[1];
            $this->itemtype = $matches[2];
            $this->func = $matches[3];
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
        if (empty($this->module)) {
            return array();
        }

        $options = array();
        if (!isset($this->itemtype)) {
            // we're interested in the module itemtypes (= default behaviour)
            $itemtypes = xarModAPIFunc($this->module,'user','getitemtypes',
                                       // don't throw an exception if this function doesn't exist
                                       array(), 0);
            if (!empty($itemtypes)) {
                foreach ($itemtypes as $typeid => $typeinfo) {
                    if (isset($typeid) && isset($typeinfo['label'])) {
                        $options[] = array('id' => $typeid, 'name' => $typeinfo['label']);
                    }
                }
            }

        } elseif (empty($this->func)) {
            // we're interested in the items for module+itemtype
            $itemlinks = xarModAPIFunc($this->module,'user','getitemlinks',
                                       // don't throw an exception if this function doesn't exist
                                       array('itemtype' => $this->itemtype,
                                             'itemids'  => null), 0);
            if (!empty($itemlinks)) {
                foreach ($itemlinks as $itemid => $linkinfo) {
                    if (isset($itemid) && isset($linkinfo['label'])) {
                        $options[] = array('id' => $itemid, 'name' => $linkinfo['label']);
                    }
                }
            }

        } else {
            // we have some specific function to retrieve the items here
            eval('$items = ' . $this->func .';');
            if (isset($items) && count($items) > 0) {
                foreach ($items as $id => $name) {
                    // skip empty items from e.g. dropdownlist() API
                    if (empty($id) && empty($name)) continue;
                    array_push($options, array('id' => $id, 'name' => $name));
                }
            }
        }

        $this->options = $options;
        return $options;
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
        if (count($this->options) > 0) {
            foreach ($this->options as $option) {
                if ($option['id'] == $this->value) {
                    if ($check) return true;
                    return $option['name'];
                }
            }
            if ($check) return false;
        }
        if (empty($this->module)) {
            if ($check) return true;
            return $this->value;
        }
        if (!isset($this->itemtype)) {
            // we're interested in one of the module itemtypes (= default behaviour)
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

        // we don't want to check empty values for items
        if (empty($this->value)) {
             if ($check) return true;
             return $this->value;
        }

        // we're interested in one of the items for module+itemtype
        $itemlinks = xarModAPIFunc($this->module,'user','getitemlinks',
                                   // don't throw an exception if this function doesn't exist
                                   array('itemtype' => $this->itemtype,
                                         'itemids' => array($this->value)), 0);
        if (!empty($itemlinks) && !empty($itemlinks[$this->value])) {
            if ($check) return true;
            return $itemlinks[$this->value]['label'];
        }
        if ($check) return false;
        return $this->value;
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
        $data['name']      = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']        = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']  = !empty($tabindex) ? $tabindex : 0;
        $data['size']      = !empty($size) ? $size : 50;
        $data['invalid']   = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        $data['modname']   = '';
        $data['modid']     = '';
        $data['itemtype']  = '';
        $data['func']      = '';
        if (!empty($this->module)) {
            $data['modname'] = $this->module;
            $data['modid']   = xarModGetIDFromName($this->module);
            if (isset($this->itemtype)) {
                $data['itemtype'] = $this->itemtype;
                if (isset($this->func)) {
                    $data['func'] = $this->func;
                }
            }
        }
        $data['other']     = '';

        // allow template override by child classes
        if (empty($module)) {
            $module = 'dynamicadata';
        }
        if (empty($template)) {
            $template = 'itemtype';
        }

        return xarTplProperty($module, $template, 'validation', $data);
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
                $this->validation = '';
                if (!empty($validation['modid'])) {
                    $modinfo = xarModGetInfo($validation['modid']);
                    if (empty($modinfo)) return false;
                    $this->validation = $modinfo['name'];
                    if (!empty($validation['itemtype'])) {
                        $this->validation .= '.' . $validation['itemtype'];
                        if (!empty($validation['func'])) {
                            $this->validation .= ':' . $validation['func'];
                        }
                    }

                } elseif (!empty($validation['other'])) {
                    $this->validation = $validation['other'];
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