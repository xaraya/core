<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Notes
 *
 * The value array is of the form $value[column][row]
 * This is done so that we can easily access the set of values of a given column, 
 * which are all of the same property type
 * 
 * Column numbers start at 0
 * Row numbers in non associative arrays start at 1 (more readable)
 * In non associative arrays the value in value[0][row] is always the row number, starting with 1
 * In all cases (?) the number of rows is the count of valuue[0]
 *
 * The column definition array is made up of 4 elements, each of which is an array
 * - title
 * - property type
 * - default value
 * - configuration
 * The count of each element array must be the same
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

    public $display_minimum_rows = 1;                // The table displays at least this many rows
    public $display_maximum_rows = 10;               // The table cannot display more than this many rows
    public $initialization_addremove = 0;            // 0: no adding/deleting of rows, 1: adding only, 2: adding and deleting    
    public $validation_associative_array = 0;        // flag to display the value as an associative array
    public $validation_associative_array_invalid;    // Holds an error msg for the validation above
    public $default_suffixlabel = "Row";             // suffix for the Add/Remove Button
    public $initialization_fixed_keys = 0;           // allow editing keys on input

    // The columns the table displays
    public $default_column_definition = array(array("Key",2,"",""),array("Value",2,"",""));  
    public $display_column_definition = array(array("Key",2,"",""),array("Value",2,"",""));  

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
            $columncount = isset($displayconfig) ? count($displayconfig) : 0;
            if (!xarVarFetch($name,    'array', $elements, array(), XARVAR_NOT_REQUIRED)) return;
            // Get the number of rows we are saving
            $rows = count($elements);

            $value = array();
            for ($k=0;$k<$columncount;$k++) {
                // Get the property type for this column and get the value from the template
                $property = DataPropertyMaster::getProperty(array('type' => $displayconfig[$k][1]));
                $property->parseConfiguration($displayconfig[$k][3]);
                $i=0;
                foreach ($elements as $row) {
                    // Ignore rows where the delete checkbox was checked

                    if (isset($row['delete'])) continue;

                    // $index is the current index of the row. May have holes if rows have been deleted
                    $index = $row[1000000]-1;
                    
                    // Get the field name of the element we are looking at
                    $fieldname = $name . '[' . $index . '][' . $k . ']';

                    // Get its data
                    $valid = $property->checkInput($fieldname);
                    // Move the found data to the array we will save
                    $value[$i][$k] = $property->value;

                    // $i is the row index we will save with, ensuring saved data has no holes in the index
                    $i++;
                }
            }
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
//        if (!parent::validateValue($value)) return false;

        // Check if we have an array. We don't really have an error message here
        if (!is_array($value)) {
            $this->invalid = xarML('The value of this property is not an array');
            xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
            $this->value = null;
            return false;
        }
        
        // Empty arrays are OK
        if (empty($value)) {
            $this->setValue($value);
            return true;
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
        if (empty($value)) $value = array();
        if (!empty($value) && is_array($value)) {

            $temp = array();
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
                // Non associative array
                // CHECKME: the 100000 column should already be gone here. In that case we can remove the foreach loop
                foreach($value as $i => $column) {
                    foreach ($column as $k => $row) {
                        if ($k == 1000000) continue;
                        $temp[$i][$k] = $value[$i][$k];
                    }
                }
            } else {
                // Associative array
                foreach($value as $i => $column) {
                    if (empty($column[0])) break;
                    foreach ($column as $key => $item) {
                        if ($key == 0) continue;
                        $temp[$column[0]][] = $item;
                    }
                }
            }
            $value = $temp;
        }
        $this->value = serialize($value);
        return true;
    }

    public function getValue()
    {
        // If passing a string we assume it is already a serialzed array of the correct type
        try {
            $value = unserialize($this->value);
        } catch(Exception $e) {
            $value = array();
        }
            
        if(!$this->validation_associative_array) {
            return $value;
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
        } else {
            $temp = array();
            foreach($value as $key => $row) {
                $newrow[] = $key;
                foreach ($row as $item) $newrow[] = $item;
                $temp[] = $newrow;
            }
            $value = $temp;
            return $value;
        }
    }

    public function showInput(Array $data = array())
    {
        // If this is an array definition, load its configuration up front
        // A bound array property contains itself an array property as part of its configuration
        // We need to check if 
        // - we are a bound property and 
        // - are configuring
        if (!empty($data["configuration"]) && ($this->type == 999)) {

            // We are entering data into a configuration of an array property
            // CHECKME: or an unbound property?
            $displayconfig = $this->display_column_definition;

            // Remove this line once legacy code no longer needed
            if (isset($displayconfig['value'])) $displayconfig = $displayconfig['value'];

            // Load the configuration data and get the exploded fields
            $configfields = $this->parseConfiguration($data["configuration"]);

            $titles         = $displayconfig[0];
            $types          = $displayconfig[1];
            $defaults       = $displayconfig[2];
            $configurations = $displayconfig[3];
                
            if (isset($configfields['value'])) $data['value'] = $configfields['value'];
            $data['display_page_type'] = 'configuration';

            if (empty($data['value'])) $data['value'] = $this->default_column_definition;
            $data['rows'] = count($data['value']);

        } else {
            // We are adding data to an item
            try {
                if (isset($data['column_configuration'])) $this->display_column_definition = unserialize($data['column_configuration']);
                $displayconfig = $this->display_column_definition;

                // Remove this line once legacy code no longer needed
                if (isset($displayconfig['value'])) $displayconfig = $displayconfig['value'];

                // New way for configs
                $titles         = array();
                $types          = array();
                $defaults       = array();
                $configurations = array();
                foreach ($displayconfig as $row) {
                    $titles[]         = $row[0];
                    $types[]          = $row[1];
                    $defaults[]       = $row[2];
                    $configurations[] = $row[3];
                }
            } catch (Exception $e) {
                // Legacy way for configs
                $titles         = $displayconfig[0];
                $types          = $displayconfig[1];
                $defaults       = $displayconfig[2];
                $configurations = $displayconfig[3];
            }
            $data['display_page_type'] = 'dataentry';
        }

        if (!isset($data['column_titles']))          $data['column_titles']         = $titles;
        if (!isset($data['column_types']))           $data['column_types']          = $types;
        if (!isset($data['column_defaults']))        $data['column_defaults']       = $defaults;
        if (!isset($data['column_configurations']))  $data['column_configurations'] = $configurations;

        // If titles or types were passed directly through the tag, they may be lists we need to turn into arrays
        if (!is_array($data['column_titles'])) $data['column_titles'] = explode(',', $data['column_titles']);
        if (!is_array($data['column_types']))  $data['column_types'] = explode(',', $data['column_types']);

        // Now arrange the values contained in this array to the size we need
        // Number of columns is defined by count($data['column_titles'])
        // Number of rows is defined by $data['rows']

        if (!isset($data['value'])) $value = $this->getValue();
        else $value = $data['value'];

        // Remove this line once legacy  code no longer needed
        if (isset($value['value'])) $value = $value['value'];

        // We always show one line at minimum on the form
        if (empty($value)) foreach ($data['column_titles'] as $column) $value[] = "";
        
        // ------------------------------------------------------------------
        // Adjust the number of rows and columns and the appropriate values
        if (!isset($data['rows'])) {
            $data_rows = empty($value) ? 0 : count($value);
            $data['rows'] = max($data_rows, $this->display_minimum_rows);
        }
        
        /*
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
        */
        $data['value'] = $value;

        // ------------------------------------------------------------------
        // Add some values we want to pass to the template
        if (!isset($data['fixedkeys']))        $data['fixedkeys'] = $this->initialization_fixed_keys;
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

        try {
            $displayconfig = $this->display_column_definition;

            // Remove this line once legacy code no longer needed
            if (isset($displayconfig['value'])) $displayconfig = $displayconfig['value'];

            // New way for configs
            $titles         = array();
            $types          = array();
            $defaults       = array();
            $configurations = array();
            foreach ($displayconfig as $row) {
                $titles[]         = isset($row[0]) ? $row[0] : '';
                $types[]          = isset($row[1]) ? $row[1] : 1;
                $defaults[]       = isset($row[2]) ? $row[2] : '';
                $configurations[] = isset($row[3]) ? $row[3] : '';
            }
        } catch (Exception $e) {
            // Legacy way for configs
            $titles         = array();
            $types          = array();
            $defaults       = array();
            $configurations = array();
            foreach ($default_column_definition as $row) {
                $titles[]         = $row[0];
                $types[]          = $row[1];
                $defaults[]       = $row[2];
                $configurations[] = $row[0];
            }
        }            
        $data['column_titles'] = $titles;
        $data['rows'] = isset($data['value'][0]) ? count($data['value'][0]) : 0;
        
        // We initialize the required properties here, for reuse in the template
        $data['column_types'] =array();
        sys::import('modules.dynamicdata.class.properties.master');
        foreach($types as $key => $thistype) {
            $data['column_types'][$key] = DataPropertyMaster::getProperty(array('type' => $thistype));
        }
        return parent::showOutput($data);
    }
    
    public function updateConfiguration(Array $data = array())
    {
        if ($this->type == 999) {
            foreach ($data['configuration']['display_column_definition'] as $row => $columns) {
                // Ignore/remove any empty rows, i.e. those where there is no title
                if (empty($columns[0])) unset($data['configuration']['display_column_definition'][$row]);
            }
        }//var_dump($data['configuration']['display_column_definition']);exit;
        return parent::updateConfiguration($data);
    }
}
?>