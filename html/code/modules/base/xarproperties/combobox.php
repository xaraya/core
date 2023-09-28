<?php
/* include the parent class */
sys::import('modules.base.xarproperties.dropdown');

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 *  This property displays a dropdown and/or textbox
 */
    class ComboProperty extends SelectProperty
    {
        public $id         = 506;
        public $name       = 'combobox';
        public $desc       = 'Combo Dropdown Box';

        public $display_combo_mode       = 3;
        public $validation_override      = true;

        function __construct(ObjectDescriptor $descriptor)
        {
            parent::__construct($descriptor);
            $this->template  = 'combobox';
        }
/**
 * Get the value of a textbox or dropdown from a web page<br/>
 *  
 * @param  string name The name of the dropdown to be selected
 * @param  string value The value of the on the basis of name if not available from property id
 * @return bool|void This method passes the value gotten to the validateValue method and returns its output.
 */	
        public function checkInput($name = '', $value = null)
        {
            $name = empty($name) ? $this->propertyprefix . $this->id : $name;

            // First check for text in the text box
            $tbname  = $name.'_tb';
            if (!xarVar::fetch($tbname, 'isset', $tbvalue,  NULL, xarVar::DONT_SET)) {return;}

            // store the fieldname for configurations who need them (e.g. file uploads)
            $this->fieldname = $tbname;

            if(isset($tbvalue) && ($tbvalue != ''))
            {
                // check as a textbox
                $value = $tbvalue;
                $textbox = DataPropertyMaster::getProperty(array('name' => 'textbox'));
                $isvalid = $textbox->checkInput($tbname, $tbvalue);
                if ($isvalid) {
                    $this->value = $textbox->value;
                } else {
                    $this->invalid = $textbox->invalid;
                }
                return $isvalid;
            } else {
                // check as a dropdown
                if (!xarVar::fetch($name, 'isset', $value,  NULL, xarVar::DONT_SET)) {return;}
                // Did we find a dropdown?
                if(!isset($value)) {
                    $this->invalid = xarML('No dropdown available for the combobox #(1)',$name);
                    return false;
                }                
                return parent::checkInput($name, $value);
            }
        }
/**
 * Display a textbox or dropdown for input
 * 
 * @param array<string, mixed> $data An array of input parameters
 * @return string     HTML markup to display the property for input on a web page
 */
        public function showInput(Array $data = array())
        {
            if (empty($data['mode'])) $data['mode'] = $this->display_combo_mode;
            return parent::showInput($data);
        }

    }
