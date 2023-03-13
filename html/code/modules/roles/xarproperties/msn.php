<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The Yahoo property is a basic wrapper for MSN Messenger functionality
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 * @author mikespub <mikespub@xaraya.com>
 * @todo: Remove?
 */
class MSNProperty extends TextBoxProperty
{
    public $id         = 30;
    public $name       = 'msn';
    public $desc       = 'MSN Messenger';
    public $reqmodules = array('roles');

    public $initialization_icon_url;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'msn';
        $this->filepath   = 'modules/roles/xarproperties';
        if (empty($this->initialization_icon_url)) {
            $this->initialization_icon_url = xarTpl::getImage('contact/msnm.png','module','roles');
        }
    }

	/**
	 * Validate the value of a textbox
	 *
	 * @return bool Returns true if the value passes all validation checks; otherwise returns false.
	 */
    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui'; // TODO: verify this !
            if (!preg_match($regexp,$value)) {
                $this->invalid = xarML('MSN Messenger: #(1)', $this->name);
                xarLog::message($this->invalid, xarLog::LEVEL_ERROR);
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

	/**
	 * Display a textbox for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = 'msnim:chat?contact='.xarVar::prepForDisplay($data['value']);
        }
        // $data['value'] is prepared for display by textbox
        return parent::showInput($data);
    }

	/**
	 * Display a textbox for output
	 * 
	 * @param  array data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = xarVar::prepForDisplay($data['value']);

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'msnim:chat?contact='.$data['value'];
        }
        if (empty($data['image'])) {
            $data['image'] = $this->initialization_icon_url;
        }
        return parent::showOutput($data);
    }
}
