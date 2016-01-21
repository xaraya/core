<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle select property
 */
sys::import('modules.dynamicdata.class.properties.base');

class SelectProperty extends DataProperty
{
    public $id         = 6;
    public $name       = 'dropdown';
    public $desc       = 'Dropdown List';
    public $reqmodules = array('base');
    public $basetype   = 'dropdown';

    public $transform  = true;                         // transform $this->value in getValue() or not
    public $options;
    public $old_config = array();
    public $itemfunc;   // CHECKME: how is this best implemented?

    public $initialization_firstline        = null;
    public $initialization_function         = null;
    public $initialization_file             = null;
    public $initialization_collection       = null;
    public $initialization_options          = null;
    public $validation_override             = false;
    public $validation_override_invalid;
    public $display_rows                    = 0;       // If there are more than these rows,display as a textbox
    public $display_option_link             = false;   // Wrap the output value in a link to the option's display page

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->template  = 'dropdown';
        $this->tplmodule = 'base';
        $this->filepath  = 'modules/base/xarproperties';
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        $options = $this->getOptions();
        if (!empty($options) && ($this->display_rows <= $options)) {
            $found = false;
            foreach ($options as $option) {
                if ($option['name'] == $value) {
                    $value = $option['id'];
                    $this->value = $value;
                    $found = true;
                    break;
                }
            }
            if (!$found) $value = null;
        }
        // check if we allow values other than those in the options
        if ($this->validation_override) {
            return true;
        }
        // check if this option really exists
        $isvalid = $this->getOption(true);
        if ($isvalid) {
            return true;
        }
        if (!empty($this->validation_override_invalid)) {
            $this->invalid = xarML($this->validation_override_invalid);
        } else {
            $this->invalid = xarML('unallowed selection: #(1) for #(2)', $value, $this->name);
        }
        xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
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
            // Legacy support: if the validation field is an array, we'll assume that this is an array of id => name
            } elseif (!empty($data['validation']) && is_array($data['validation']) && xarConfigVars::get(null, 'Site.Core.LoadLegacy')) {
                sys::import('xaraya.legacy.validations');
                $this->options = dropdown($data['validation']);
            }

        // Allow overriding by specific parameters
            if (isset($data['function']))   $this->initialization_function = $data['function'];
            if (isset($data['file']))       $this->initialization_file = $data['file'];
            if (isset($data['collection'])) $this->initialization_collection = $data['collection'];

        // Finally generate the options
            $data['options'] = $this->getOptions();
        }
        
        // If a firstline was defined add it in
        if (isset($data['firstline']))  $this->initialization_firstline = $data['firstline'];
        $data['options'] = array_merge($this->getFirstline(), $data['options']);
        
        // Make sure the options have the correct form
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
        // optionally add hidden previous_value field 
        if (!isset($data['previousvalue'])) $data['previousvalue'] = false;
        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        if(isset($data['rows'])) $this->display_rows = $data['rows']; 
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($data['option_link'])) $this->display_option_link = $data['option_link'];
        if (isset($data['value'])) $this->value = $data['value'];

        // If we have options passed, take them.
        if (isset($data['options'])) $this->options = $data['options'];
        // get the option corresponding to this value
        $result = $this->getOption();
        // only apply xarVarPrepForDisplay on strings, not arrays et al.
        if (!empty($result) && is_string($result)) $result = xarVarPrepForDisplay($result);
        if (!empty($data['link'])) {
            $data['option'] = array('id' => $this->value, 'name' => $result, 'link' => $data['link']);
        } else {
            $data['option'] = array('id' => $this->value, 'name' => $result);
        }

        return parent::showOutput($data);
    }

    /**
     * Retrieve the list of options on demand
     * 
     * N.B. the code below is repetitive, but lets leave it clearly separated for 
     * each type of input for the moment
     * 
     * @param void N/A
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        
        // filepath appears always empty?
        if (empty($filepath)) $filepath = sys::code() . $this->initialization_file;

        $options = array();
        if (!empty($this->initialization_function)) {
            @eval('$items = ' . $this->initialization_function .';');
            if (!isset($items) || !is_array($items)) $items = array();
            if (is_array(reset($items))) {
                foreach($items as $id => $name) {
                    $options[] = array('id' => $name['id'], 'name' => $name['name']);
                }
            } else {
                foreach ($items as $id => $name) {
                    $options[] = array('id' => $id, 'name' => $name);
                }
            }
            unset($items);
        } elseif (!empty($filepath) && file_exists($filepath) && is_file($filepath)) {
            $parts = pathinfo($filepath);
            if ($parts['extension'] =='xml'){
                $data = implode("", file($filepath));
                $parser = xml_parser_create( 'UTF-8' );
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
                xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
                xml_parse_into_struct($parser, $data, $value, $index);
                xml_parser_free($parser);
                $limit = count($index['id']);
                while (count($index['id'])) {
                    $options[] = array('id' => $value[array_shift($index['id'])]['value'], 'name' => $value[array_shift($index['name'])]['value']);
                }
            } else {
                $fileLines = file($filepath);
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

        /* Sample optimization when dealing with heavy getOptions() functions
        // Save options only when we're dealing with an object list
        if (!empty($this->_items)) {
            $this->options = $options;
        }
        */
        return $options;
    }

    /**
     * Gets the saved first line and returns it as a proper array
     * 
     * @param void N/A
     * @return array Array containing first line
     */
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
     * @return if check == false:<br/>
     *                - display value, if found, of an option whose store value is $this->value<br/>
     *                - $this->value, if not found<br/>
     *         if check == true:<br/>
     *                - true, if an option exists whose store value is $this->value<br/>
     *                - false, if no such option exists<br/>
     */
    function getOption($check = false)
    {
        if (!$this->transform) return $this->value;

        if (!isset($this->value)) {
             if ($check) return true;
             return null;
        }

        // we're interested in one of the known options (= default behaviour)
        if (count($this->options) > 0) {
            $options = $this->options;
        } else {
            $options = $this->getOptions();
        }
        // Add in a first line if it is defined
        if (!empty($this->initialization_firstline))  {
            $options = array_merge($this->getFirstline(), $options);
        }

        foreach ($options as $option) {
            // Make sure the array has the correct form
            if (!isset($option['id']) || !isset($option['name'])) continue;
            // Find the entry among the options
            if ($option['id'] == $this->value) {
                if ($check) return true;
                return $option['name'];
            }
        }
        
        // Nothing found and $check == true;
        if ($check) return false;

        // Nothing found and $check == false;
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

    /**
     * Alias for the getOption method
     * This make the property consistent with standard usage
     */
    function getValue()
    {
        return $this->getOption(false);
    }
    
// CHECKME: should we move this to properties/base.php, in case other "basic" property types want this ?
// CHECKME: should we move remove this? It's not being used

    /**
     * Check if the configuration is the same as last time, e.g. to return saved options in getOptions()
     * when we're dealing with an object list.
     *
     * Note: we typically only care about initialization here, since validation and display
     * configurations don't (or shouldn't) impact the result of the getOptions() function...
     *
     * @param $type string the type of configuration you want to check (typically only initialization)
     * @return boolean true if the configuration is the same as last time we checked, false otherwise
     */
    function isSameConfiguration($type = 'initialization')
    {
        if (empty($this->old_config)) {
            $this->old_config = array();
            // save the current configuration properties in the old_config
            $properties = $this->getPublicProperties();
            foreach ($this->configurationtypes as $configtype) {
                $this->old_config[$configtype] = array();
                $match = '/^' . $configtype . '_/';
                foreach ($properties as $key => $value) {
                    if (preg_match($match, $key)) {
                        $this->old_config[$configtype][$key] = $value;
                    }
                }
            }
            return false;
        }
        // compare the current initialization properties with the old_config
        $same = true;
        foreach (array_keys($this->old_config[$type]) as $key) {
            if ($this->$key != $this->old_config[$type][$key]) {
                $this->old_config[$type][$key] = $this->$key;
                $same = false;
            }
        }
        return $same;
    }
}

sys::import('modules.dynamicdata.class.properties.interfaces');

class DropdownPropertyInstall extends SelectProperty implements iDataPropertyInstall
{
    public function install(Array $data=array())
    {
        $dat_file = sys::code() . 'modules/base/xardata/dropdown_configurations-dat.xml';
        $data = array('file' => $dat_file);
        try {
            $objectid = xarMod::apiFunc('dynamicdata','util','import', $data);
        } catch (Exception $e) {
            //
        }
        return true;
    }
}
?>
