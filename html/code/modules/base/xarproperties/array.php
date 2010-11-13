<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
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

    public $display_minimum_rows = 2;                              // The table displays at least this many rows
    public $display_maximum_rows = 10;                             // The table cannot display more than this many rows
    public $initialization_addremove = 0;                          // 0: no adding/deleting of rows, 1: adding only, 2: adding and deleting    
    public $display_column_titles = array("Key","Value");          // default labels for columns
    public $display_column_types = array("textbox","textbox");     // default types for columns
    public $initialization_associative_array = 0;                  // flag to store the value as associative array
    public $default_suffixlabel = "Row";                           // suffix for the Add/Remove Button
    public $initialization_fixed_keys = 0;                         // allow editing keys on input

    public $display_column_definition = array(array("Key","Value"),array("textbox","textbox"));  

    // Configuration setting to ignore
    public $initialization_other_rule_ignore    = true;
    public $initialization_transform_ignore     = true;
    public $validation_allowempty_ignore        = true;
    public $validation_equals_ignore            = true;
    public $validation_notequals_ignore         = true;

    
    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'array';
        $this->filepath   = 'modules/base/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            // Get the number of columns and rows
            $columncount = count($this->display_column_definition['value'][0]);
            if (!xarVarFetch($name . '["value"]',    'array', $elements, 'array', XARVAR_NOT_REQUIRED)) return;
// Ignore the last row for now. It's the one for adding rows
//            array_pop($elements);
            // Get the number of rows we are saving
            $rows = count($elements);
            for ($k=1;$k<=$columncount;$k++) {
                // Get the property type for this column and get the value from the template
                $property = DataPropertyMaster::getProperty(array('type' => $this->display_column_definition['value'][1][$k-1]));
                $i=0;
                foreach ($elements as $row) {
                    // Ignore rows where the delete checkbox was checked
                    if (isset($row['delete'])) continue;
                    // $i is the row index we will save with, ensuring saved data has no holes in the index
                    $i++;
                    // $index is the current index of the row. May have holes if rows have been deleted
                    $index = $row[0]-1;
                    // Get the field name of the element we are looking at
                    $fieldname = $name . '["value"][' . $index . '][' . $k . ']';
                    // Get its data
                    $valid = $property->checkInput($fieldname);
                    // Move the found data to the array we will save
                    $value[$k-1][$i-1] = $property->value;
                }
            }

            //Set value to the initialization_associative_array  
            if (!xarVarFetch($name . '["associative_array"]', 'int', $associative_array, 0, XARVAR_NOT_REQUIRED)) return;
            $this->initialization_associative_array = $associative_array;
        }
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!is_array($value)) {
            $this->value = null;
            return false;
        }
        $this->setValue($value);
        return true;
    }

    function setValue($value=null)
    {
        // If passing an string we assume it is already a serialzed array of the correct type
        if (empty($value)) $value = array();
        if (!empty($value) && is_array($value)) {
            //this code is added to store the values as value1,value2 in the DB for non-associative storage
            if(!$this->initialization_associative_array) {
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
                $temp = array();                
                foreach ($value as $key => $row) {
                    array_unshift($row,$key);
                    $temp[] = $row;
                }
                $value = $temp;
            }
            $temp = array();                
            foreach($value as $i => $column) {
                foreach ($column as $k => $row) {
                    $temp[$k][$i] = $value[$i][$k];
                }
            }
            $value = $temp;
        }
        $this->value = serialize($value);
    }

    public function getValue()
    {
        try {
            $value = unserialize($this->value);
            $temp = array();                
            foreach($value as $i => $row) {
                foreach ($row as $k => $column) {
                    $temp[$k][$i] = $value[$i][$k];
                }
            }
            if(!$this->initialization_associative_array) {
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
                $temp1 = array();                
                foreach ($temp as $row) {
                    $newkey = $row[0];
                    unset($row[0]);
                    $temp1[$newkey] = $row;
                }
                $value = $temp1;
            } else {
                $value = $temp;
            }
        } catch(Exception $e) {
            $value = null;
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        // If this is a column definition, load its configuration up front
        // A bound array property contains itself an arry property as part of its configuration
        // The recursed parameter signals we are displaying the configuration property
        if (isset($data["configuration"])) {
            $configuration = unserialize($data["configuration"]);
            if (isset($configuration['display_column_definition']['configuration']) && 
                !empty($configuration['display_column_definition']['recursed'])) {
                
                // Unset the recursed parameter so as not to repeat this
                unset($configuration['display_column_definition']['recursed']);
                
                // Load the configuration data
                $this->parseConfiguration($configuration['display_column_definition']['configuration']);
                
                // Get the values for titles and column types
                if (!isset($data['column_definition'])) $data['column_definition'] = $this->display_column_definition;
                $titles = $data['column_definition'][0];
                $types = $data['column_definition'][1];
                
                // CHECKME: Get the value array. This is a bit odd, but not sure we can do better
                if (isset($data['value']['value'])) $data['value'] = $data['value']['value'];
                // Remove any empty rows, i.e. those where there is no title
                $temp = array();
                foreach ($data['value'][0] as $k => $v) {
                    if (!empty($v)) {
                        $temp[0][] = $v;
                        $temp[1][] = $data['value'][1][$k];
                    }
                }
                $data['value'] = $temp;
                $data['rows'] = count($data['value'][0]);
                $data['layout'] = 'configuration';
            }
        } else {
            try {
                // New way for configs
                $titles = $this->display_column_definition['value'][0];
                $types = $this->display_column_definition['value'][1];
            } catch (Exception $e) {
                // Legacy way for configs
                $titles = $this->display_column_definition[0];
                $types = $this->display_column_definition[1];
            }
            $data['layout'] = 'table';
        }
        
        if (!isset($data['column_titles'])) $data['column_titles'] = $titles;
        if (!isset($data['column_types']))  $data['column_types'] = $types;

        // If titles or types were passed directly through the tag, they may be lists we need to turn into arrays
        if (!is_array($data['column_titles'])) $data['column_titles'] = explode(',', $data['column_titles']);
        if (!is_array($data['column_types'])) $data['column_types'] = explode(',', $data['column_types']);
        
        // Now arrange the values contained in this array to the size we need
        // Number of columns is defined by count($data['column_titles'])
        // Number of rows is defined by $data['rows']
        if (!isset($data['value'])) $value = unserialize($this->value);
        else $value = $data['value'];
        
        try {
            if (!isset($data['rows']))          $data['rows'] = count($value[0]);
        } catch(Exception $e) {
            $data['rows'] = $this->display_minimum_rows;
        }

        // First make sure the number of titles and column types is the same
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

        if (!isset($data['fixedkeys'])) $data['fixedkeys'] = $this->initialization_fixed_keys;

        if (isset($data['allowinput']))        $this->initialization_addremove = $data['allowinput'];
        if (isset($data['associative_array'])) $this->initialization_associative_array = $data['associative_array'];
        if (isset($data['addremove']))         $this->initialization_addremove =  $data['addremove'];
        if (!isset($data['layout']))           $data['layout'] = 'table';
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = unserialize($this->value);
        $data['column_titles'] = $this->display_column_definition['value'][0];
        $data['column_types'] = $this->display_column_definition['value'][1];
        $data['rows'] = count($data['value'][0]);
        return parent::showOutput($data);
    }
    
    public function updateConfiguration(Array $data = array())
    {
        // Remove any empty rows, i.e. those where there is no title
        $temp = array();
        foreach ($data['configuration']['display_column_definition']['value'][0] as $k => $v) {
            if (!empty($v)) {
                $temp[0][] = $v;
                $temp[1][] = $data['configuration']['display_column_definition']['value'][1][$k];
                $temp[2][] = $data['configuration']['display_column_definition']['value'][2][$k];
            }
        }
        $data['configuration']['display_column_definition']['value'] = $temp;

        return parent::updateConfiguration($data);
    }
}
?>
