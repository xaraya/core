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
    public $itemfunc;   // CHECKME: how is this best implemented?

    public $initialization_firstline        = null;
    public $initialization_function         = null;
    public $initialization_file             = null;
    public $initialization_collection       = null;
    public $initialization_options          = null;
    public $validation_override             = false;
    public $validation_override_invalid;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'dropdown';
        $this->tplmodule = 'base';
        $this->filepath   = 'modules/base/xarproperties';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // check if this option really exists
        $isvalid = $this->getOption(true);
        if ($isvalid) {
            return true;
        }
        // check if we allow values other than those in the options
        if ($this->validation_override) {
            return true;
        }
        if (!empty($this->validation_override_invalid)) {
            $this->invalid = xarML($this->validation_override_invalid);
        } else {
            $this->invalid = xarML('unallowed selection: #(1) for #(2)', $value, $this->name);
        }
        $this->value = null;
        return false;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        // If we have options passed, take them. Otherwise generate them
        if (!isset($data['options'])) {

        // Parse a configuration if one was passed
            if(isset($data['configuration'])) {
                $this->parseConfiguration($data['configuration']);
                unset($data['configuration']);
            }

        // Allow overriding by specific parameters
            if (isset($data['function']))   $this->initialization_function = $data['function'];
            if (isset($data['file']))       $this->initialization_file = $data['file'];
            if (isset($data['collection'])) $this->initialization_collection = $data['collection'];
            if (isset($data['firstline']))  $this->initialization_firstline = $data['firstline'];

        // Finally generate the options
            $data['options'] = $this->getOptions();
        } else {
            // If a firstline was defined add it in
            if (isset($data['firstline'])) $this->initialization_firstline = $data['firstline'];
            $data['options'] = array_merge($this->getFirstline(),$data['options']);
        }
        
        // Make sure the optins have the correct form
        if (!is_array($data['options']))
            throw new Exception(xarML('Dropdown options do not have the correct form'));
        if (!is_array(current($data['options']))) {
            $normalizedoptions = array();
            foreach ($data['options'] as $key => $value)
                $normalizedoptions[] = array('id' => $key, 'name' => $value);
            $data['options'] = $normalizedoptions;
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

        // If we have options passed, take them.
        if (isset($data['options'])) $this->options = $data['options'];
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
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        if (!empty($this->initialization_function)) {
            @eval('$items = ' . $this->initialization_function .';');
            if (!isset($items) || !is_array($items)) $items = array();
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

    function getFirstline()
    {
        $firstline = $this->initialization_firstline;
        if (empty($firstline)) return array();
        
        if (is_array($firstline)) {
            if (isset($firstline['name'])) {
                if (strpos($firstline['name'],'xar') === 0) @eval('$firstline["name"] = ' . $firstline['name'] .';');
                $line = array('id' => $firstline['id'], 'name' => $firstline['name']);
            } else {
                if (strpos($firstline['id'],'xar') === 0) @eval('$firstline["id"] = ' . $firstline['id'] .';');
                $line = array('id' => $firstline['id'], 'name' => $firstline['id']);
            }
        } else {
            $firstline = explode(',',$firstline);
            if (isset($firstline[1])) {
                if (strpos($firstline[1],'xar') === 0) @eval('$firstline[1] = ' . $firstline[1] .';');
                $line = array('id' => $firstline[0], 'name' => $firstline[1]);
            } else {
                if (strpos($firstline[0],'xar') === 0) @eval('$firstline[0] = ' . $firstline[0] .';');
                $line = array('id' => $firstline[0], 'name' => $firstline[0]);
            }
        }
        return array($line);        
    }

    /**
     * Retrieve or check an individual option on demand
     *
     * @param  $check boolean
     * @return if check == false:
     *                - display value, if found, of an option whose store value is $this->value
     *                - $this->value, if not found
     * @return if check == true:
     *                - true, if an option exists whose store value is $this->value
     *                - false, if no such option exists
     */
    function getOption($check = false)
    {
        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }

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

        /* I don't see how this works, so I've moved it aside here for now (random)
        if (!empty($this->itemfunc)) {
            // use $value as argument for your API function : array('whatever' => $value, ...)
            $value = $this->value;
            eval('$result = ' . $this->itemfunc .';');
            if (isset($result)) {
                if ($check) return true;
                return $result;
            }
        }
        if ($check) return false;
        return $this->value;
        */
    }
}

?>
