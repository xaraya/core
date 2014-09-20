<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Notes
 *
 * The value array is of the form $value[column][row]
 * This is done so that we can easily access the set of values of a given column, 
 * which are all of the same property type
 * 
 * The value in value[0][row] is always the row number, starting with 1
 */

/* include the base class */
sys::import('modules.dynamicdata.class.properties.base');
/**
 * Handle Array Property
 */
class ArrayProperty extends DataProperty
{
    public $id         = 999;
    public $name       = 'array';
    public $desc       = 'Array';
    public $reqmodules = array('base');

    public $fields = array();

    public $display_minimum_rows = 1;                              // The table displays at least this many rows
    public $display_maximum_rows = 10;                             // The table cannot display more than this many rows
    public $initialization_addremove = 0;                          // 0: no adding/deleting of rows, 1: adding only, 2: adding and deleting    
    public $validation_associative_array = 0;                      // flag to display the value as an associative array
    public $validation_associative_array_invalid;                  // Holds an error msg for the validation above
    public $default_suffixlabel = "Row";                           // suffix for the Add/Remove Button
    public $initialization_fixed_keys = 0;                         // allow editing keys on input

    public $display_column_definition = array(array("Key","Value"),array(2,2),array("",""),array("",""));  

    // Configuration setting to ignore
    public $initialization_other_rule_ignore    = true;
    public $initialization_transform_ignore     = true;
    public $validation_allowempty_ignore        = true;
    public $validation_equals_ignore            = true;
    public $validation_notequals_ignore         = true;

    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule      = 'base';
        $this->template       = 'array';
        $this->filepath       = 'modules/base/xarproperties';
        $this->prepostprocess = 2;
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? $this->propertyprefix . $this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            // Get the number of columns and rows
            if (isset($this->display_column_definition['value'])) {
                $displayconfig = $this->display_column_definition['value'];
            } else {
                $displayconfig = $this->display_column_definition;
            }
            $columncount = count($displayconfig[0]);
            if (!xarVarFetch($name . '["value"]',    'array', $elements, array(), XARVAR_NOT_REQUIRED)) return;
            // Get the number of rows we are saving
            $rows = count($elements);

            for ($k=1;$k<=$columncount;$k++) {
                // Get the property type for this column and get the value from the template
                $property = DataPropertyMaster::getProperty(array('type' => $displayconfig[1][$k-1]));
                $property->parseConfiguration($displayconfig[3][$k-1]);
                $i=0;
                foreach ($elements as $row) {
                    // Ignore rows where the delete checkbox was checked
                    if (isset($row['delete'])) continue;

                    // $index is the current index of the row. May have holes if rows have been deleted
                    $index = $row[0];
                    
                    // Get the field name of the element we are looking at
                    $fieldname = $name . '["value"][' . $index . '][' . $k . ']';

                    // $i is the row index we will save with, ensuring saved data has no holes in the index
                    $i++;
                    
                    // Get its data
                    $valid = $property->checkInput($fieldname);
                    // Move the found data to the array we will save
                    $value[$k-1][$i] = $property->value;
                }
            }

            // Set value to the validation_associative_array  
//            if (!xarVarFetch($name . '["settings"]["associative_array"]', 'int', $associative_array, 0, XARVAR_NOT_REQUIRED)) return;
//            $this->validation_associative_array = $associative_array;
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // Check if we have an array. We don't really have an error message here
        if (!is_array($value)) {
            $this->invalid = xarML('The value of this property is not an array');
            xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
            $this->value = null;
            return false;
        }
        
        // If this is an associative array, check if the keys are unique
        if ($this->validation_associative_array) {
            $initial_count = count($value[0]);
            $keycol = $value[0];
            $temp = array();
            foreach($keycol as $keyvalue) $temp[$keyvalue] = 1;
            
            if (count($temp) != $initial_count) {
                if (!empty($this->validation_associative_array_invalid)) {
                    $this->invalid = xarML($this->validation_associative_array_invalid);
                } else {
                    $this->invalid = xarML('The key values of the array are not unique');
                }
// This results in the "bad data" (but only the last row of the same key) being displayed
// Can we do better?
//                $this->value = null;
                xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                return false;
            }
        }
        $this->setValue($value);
        return true;
    }

    function setValue($value=null)
    {
        // If passing a string we assume it is already a serialzed array of the correct type
        if (empty($value)) $value = array();
        if (!empty($value) && is_array($value)) {
            //this code is added to store the values as value1,value2 in the DB for non-associative storage
            if(!$this->validation_associative_array) {
            /*
                //Legacy format. remove?
                $elements = "";
                foreach ($value as $element) {
                    if (is_array($element)) {
                        $subelements = "";
                        foreach($element as $subelement){
                            $subelements .= $subelement."%@$#";
                        }
                        $elements .= $subelements.";";
                    } else {
                        $elements .= $element.";";
                    }
                }
                $this->value = $elements;
            */
                /*$temp = array();                
                foreach ($value as $key => $row) {
                    array_unshift($row,$key);
                    $temp[] = $row;
                }
                $value = $temp;
                */
                // Non associative array
                $temp = array();
                foreach($value as $i => $column) {
                    foreach ($column as $k => $row) {
                        if ($k == 0) continue;
                        $temp[$k-1][$i] = $value[$i][$k];
                    }
                }
            } else {
                // Associative array
                $temp = array();
                $keys = array_shift($value);
                foreach($value as $i => $column) {
                    foreach ($column as $k => $row) {
                        if ($k == 0) continue;
                        $temp[$keys[$k]][$i] = $value[$i][$k];
                    }
                }
            }
            $value = $temp;
            $this->value = serialize($value);
        } else {
            $this->value = $value;
        }
    }

    public function getValue()
    {
         try {
            $value = unserialize($this->value);
            $temp = array();
            
            if(!$this->validation_associative_array) {
                foreach($value as $i => $row) {
                    foreach ($row as $k => $column) {
                        $temp[$k][$i+1] = $value[$i][$k];
                    }
                }
            /*
                //Legacy format. remove?
                $outer = explode(';',$this->value);
                $value =array();
                foreach ($outer as $element) {
                    $inner = explode('%@$#',$element);
                    if (count($inner)>1) $value[] = $inner;
                    else $value[] = $element;
                }
            */
            /*
                $temp1 = array();                
                foreach ($temp as $row) {
                    $newkey = $row[1];
                    unset($row[1]);
                    $temp1[$newkey] = $row;
                }
                */
            } else {
                $keys = array_keys($value);
                $index = 1;
                foreach ($keys as $key) {$temp[0][$index] = $key; $index++;}
                $index = 1;
                foreach($value as $i => $row) {
                    foreach ($row as $k => $column) {
                        $temp[$k+1][$index] = $value[$i][$k];
                    }
                    $index++;
                }
            }
            $value = $temp;
        } catch(Exception $e) {
            $value = null;
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        // If this is a column definition, load its configuration up front
        // A bound array property contains itself an array property as part of its configuration
        if (!empty($data["configuration"])) {
        
                // Load the configuration data and get the exploded fields
                $configfields = $this->parseConfiguration($data["configuration"]);

                $displayconfig = $this->display_column_definition;
                
                // Remove this line once legacy  code no longer needed
                if (isset($displayconfig['value'])) $displayconfig = $displayconfig['value'];

                $titles = $displayconfig[0];
                $types = $displayconfig[1];
                $defaults = $displayconfig[2];
                $configurations = $displayconfig[3];

                if (isset($configfields['value'])) $data['value'] = $configfields['value'];

        } else {
            try {
                // New way for configs
                if (isset($this->display_column_definition['value'])) {
                    $displayconfig = $this->display_column_definition['value'];
                    $titles = $displayconfig[0];
                    $types = $displayconfig[1];
                    $defaults = $displayconfig[2];
                    $configurations = $displayconfig[3];
                } else {
                    $titles = array();
                    $types = array();
                    $defaults = array();
                    $configurations = array();
                }
            } catch (Exception $e) {
                // Legacy way for configs
                $titles = $this->display_column_definition[0];
                $types = $this->display_column_definition[1];
                $defaults = $this->display_column_definition[2];
                $configurations = $this->display_column_definition[3];
            }
            
        }
        
        if (!isset($data['column_titles'])) $data['column_titles'] = $titles;
        if (!isset($data['column_types']))  $data['column_types'] = $types;
        if (!isset($data['column_defaults']))  $data['column_defaults'] = $defaults;
        if (!isset($data['column_configurations']))  $data['column_configurations'] = $configurations;

        // If titles or types were passed directly through the tag, they may be lists we need to turn into arrays
        if (!is_array($data['column_titles'])) $data['column_titles'] = explode(',', $data['column_titles']);
        if (!is_array($data['column_types'])) $data['column_types'] = explode(',', $data['column_types']);
        
        // Now arrange the values contained in this array to the size we need
        // Number of columns is defined by count($data['column_titles'])
        // Number of rows is defined by $data['rows']
        if (!isset($data['value'])) $value = $this->getValue();
        else $value = $data['value'];

        // Remove this line once legacy  code no longer needed
        if (isset($value['value'])) $value = $value['value'];
        
        // ------------------------------------------------------------------
        // Adjust the number of rows and columns and the appropriate values
        // Make sure we try for at least the configured minimum number of rows
        try {
            if (!isset($data['rows']))
                $data['rows'] = count($value[0]);
        } catch(Exception $e) {
            $data['rows'] = $this->display_minimum_rows;
        }
        $data['rows'] = max($data['rows'], $this->display_minimum_rows);
        
        // Make sure the number of titles and column types is the same
        $titlescount = count($data['column_titles']);
        $typescount = count($data['column_types']);
        if ($titlescount > $typescount) {
            $lastprop = $data['column_types'][$typescount-1];
            for ($i=$typescount;$i<$titlescount;$i++) $types[] = $lastprop;
        }
        // Now add any missing value rows or columns        
        for ($i=0;$i<$data['rows'];$i++) {
            for ($j=0;$j<$titlescount;$j++) {
                $property = DataPropertyMaster::getProperty(array('type' => $data['column_types'][$j]));
                if (!isset($value[$j][$i])) $value[$j][$i] = $property->defaultvalue;
            }
        }
        $data['value'] = $value;

        // ------------------------------------------------------------------
        // Add some values we want to pass to the template
        if (!isset($data['fixedkeys'])) $data['fixedkeys'] = $this->initialization_fixed_keys;
        if (isset($data['allowinput']))        $this->initialization_addremove = $data['allowinput'];
        if (isset($data['associative_array'])) $this->validation_associative_array = $data['associative_array'];
        if (isset($data['addremove']))         $this->initialization_addremove =  $data['addremove'];
        if (!isset($data['layout']))           $data['layout'] = 'table';

        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (isset($data['value'])) $this->value = $data['value'];
        $data['value'] = $this->getValue();
        $displayconfig = $this->display_column_definition['value'];
        $data['column_titles'] = $displayconfig[0];
        $data['column_types'] = $displayconfig[1];
        $data['rows'] = isset($data['value'][0]) ? count($data['value'][0]) : 0;
        return parent::showOutput($data);
    }
    
    public function updateConfiguration(Array $data = array())
    {
        $temp = array();
        foreach ($data['configuration']['display_column_definition']['value'][0] as $k => $v) {
            // Ignore/remove any empty rows, i.e. those where there is no title
            if (empty($v)) continue;
            $temp[0][] = $v;
            $temp[1][] = $data['configuration']['display_column_definition']['value'][1][$k];
            $temp[2][] = $data['configuration']['display_column_definition']['value'][2][$k];
            $temp[3][] = $data['configuration']['display_column_definition']['value'][3][$k];
        }
        $data['configuration']['display_column_definition'] = $temp;
        return parent::updateConfiguration($data);
    }
}
?>