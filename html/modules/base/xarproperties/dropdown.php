<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle select property
 */
class SelectProperty extends DataProperty
{
    public $id   = 6;
    public $name = 'dropdown';
    public $desc = 'Dropdown List';
    public $reqmodules = array('base');

    public $options;
    public $func;
    public $itemfunc;
    public $file;
    public $override = false; // allow values other than those in the options

    public $initialization_function         = null;
    public $initialization_file             = null;
    public $initialization_collection       = null;
    public $initialization_options          = null;
    public $validation_override             = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'dropdown';
        $this->tplmodule = 'base';
        $this->filepath   = 'modules/base/xarproperties';

        if (!isset($this->options)) {
            $this->options = array();
        }
        // options may be set in one of the child classes
        if (count($this->options) == 0 && !empty($this->configuration)) {
            $this->parseConfiguration($this->configuration);
        }
    }

    public function validateValue($value = null)
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
        if ($this->validation_override) {
            return true;
        }
        $this->invalid = xarML('unallowed selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
//        if (isset($data['override'])) $this->validation_override = $data['override'];

        if (!isset($data['options']) || count($data['options']) == 0) {
            if (isset($data['configuration'])) {
                $this->parseConfiguration($data['configuration']);
            }
            $data['options'] = $this->getOptions();
        }

        // check if we need to add the current value to the options
        if (!empty($data['value']) && $this->validation_override) {
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
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($data['value'])) $this->value = $data['value'];

        // get the option corresponding to this value
        $result = $this->getOption();
        // only apply xarVarPrepForDisplay on strings, not arrays et al.
        if (!empty($result) && is_string($result)) $result = xarVarPrepForDisplay($result);
        $data['option'] = array('id' => $this->value, 'name' => $result);

        return parent::showOutput($data);
    }

    /**
     * Retrieve the list of options on demand
     * N.B. the code below is repetitive, but lets leave it clearly separated for each type of input for the moment
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        $options = array();
        if (!empty($this->initialization_function)) {
            eval('$items = ' . $this->initialization_function .';');
            if (isset($items[0]) && is_array($items[0])) {
                foreach($items as $id => $name) {
                    $options[] = array('id' => $name['id'], 'name' => $name['name']);
                }
            } else {
                foreach ($items as $id => $name) {
                    $options[] = array('id' => $id, 'name' => $name);
                }
            }
            unset($items);
        } elseif (!empty($this->initialization_file) && file_exists($this->initialization_file)) {
            $fileLines = file($this->initialization_file);
            foreach ($fileLines as $option)
            {
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = strtr($id,array('\,' => ','));
                    $name = strtr($name,array('\,' => ','));
                    array_push($options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = strtr($option,array('\,' => ','));
                    array_push($options, array('id' => $option, 'name' => $option));
                }
            }
        } elseif (!empty($this->initialization_options)) {
            $lines = explode(';',$this->initialization_options);
            // remove the last (empty) element
            array_pop($lines);
            foreach ($lines as $option)
            {
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = trim(strtr($id,array('\,' => ',')));
                    $name = trim(strtr($name,array('\,' => ',')));
                    array_push($options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = trim(strtr($option,array('\,' => ',')));
                    array_push($options, array('id' => $option, 'name' => $option));
                }
            }
        } elseif (!empty($this->initialization_collection)) {
            eval('$items = ' . $this->initialization_collection .';');
            if (isset($items) && is_object($items)){
                sys::import('xaraya.structures.sets.collection');
                $iter = $items->getIterator();
                while($iter->valid()) {
                    $obj = $iter->current();
                    $options[] = $obj->toArray();
                    $iter->next();
                }
            }
            unset($items);
        }

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
}

?>
