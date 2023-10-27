<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The Yahoo property is a basic wrapper for Yahoo Messenger functionality
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 * @author mikespub <mikespub@xaraya.com>
 * @deprecated 2.4.0 gone
 */
class YahooProperty extends TextBoxProperty
{
    public $id         = 31;
    public $name       = 'yahoo';
    public $desc       = 'Yahoo Messenger';
    public $reqmodules = array('roles');

    public $initialization_icon_url;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'yahoo';
        $this->filepath   = 'modules/roles/xarproperties';
        if (empty($this->initialization_icon_url)) {
            $this->initialization_icon_url = xarTpl::getImage('contact/yahoo.png','module','roles');
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
            if (preg_match('/^[a-z0-9_-]+$/i',$value)) { // TODO: refine this !?
                $this->value = $value;
            } else {
                $this->invalid = xarML('Yahoo Messenger: #(1)', $this->name);
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
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'http://edit.yahoo.com/config/send_webmesg?.target='.xarVar::prepForDisplay($data['value']).'&.src=pg';
        }
        // $data['value'] is prepared for display by textbox
        return parent::showInput($data);
    }

	/**
	 * Display a textbox for output
	 * 
	 * @param array<string, mixed> $data An array of input parameters
	 * @return string     HTML markup to display the property for output on a web page
	 */
    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = xarVar::prepForDisplay($data['value']);

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'http://edit.yahoo.com/config/send_webmesg?.target='.$data['value'].'&.src=pg';
        }
        if (empty($data['image'])) {
            $data['image'] = $this->initialization_icon_url;
        }
        return parent::showOutput($data);
    }
}
