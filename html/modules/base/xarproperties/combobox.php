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
/* include the parent class */
sys::import('modules.base.xarproperties.dropdown');
/**
 * Handle the combo property
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

        public function checkInput($name = '', $value = null)
        {
            $name = empty($name) ? 'dd_'.$this->id : $name;

            // First check for text in the text box
            $tbname  = $name.'_tb';
            if (!xarVarFetch($tbname, 'isset', $tbvalue,  NULL, XARVAR_DONT_SET)) {return;}

            // store the fieldname for configurations who need them (e.g. file uploads)
            $this->fieldname = $tbname;

            if( isset($tbvalue) && ($tbvalue != '') )
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
                return parent::checkInput($name, $value);
            }
        }

        public function showInput(Array $data = array())
        {
            if (empty($data['mode'])) $data['mode'] = $this->display_combo_mode;
            return parent::showInput($data);
        }

    }
?>
