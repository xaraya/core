<?php
/* Include the base class */
sys::import('modules.base.xarproperties.textbox');

/**
 * The ICQ property is a basic wrapper for ICQ messaging functionality
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
class ICQProperty extends TextBoxProperty
{
    public $id         = 28;
    public $name       = 'icq';
    public $desc       = 'ICQ Number';
    public $reqmodules = array('roles');

    public $initialization_icon_url;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'icq';
        $this->filepath   = 'modules/roles/xarproperties';
        if (empty($this->initialization_icon_url)) {
            $this->initialization_icon_url = xarTpl::getImage('contact/icq.png','module','roles');
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
            if (is_numeric($value)) {
            } else {
                $this->invalid = xarML('ICQ Number: #(1)', $this->name);
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

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = 'http://wwp.icq.com/scripts/search.dll?to='.xarVar::prepForDisplay($data['value']);
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
        extract($data);
        if (!isset($value)) $value = $this->value;

        if (empty($data['image'])) {
            $data['image'] = $this->initialization_icon_url;
        }

        // TODO: use redirect function here ?
        $link = '';
        if (!empty($value) && !empty($data['image'])) {
            // TODO: check this ICQ stuff
            // TODO: move this outa here
            //<jojodee> Passing the whole lot to the template !
            //The data is there for anyone that wants to use the vars themselves in the template.
            $link = '<script type="text/javascript"><!--
if ( navigator.userAgent.toLowerCase().indexOf(\'mozilla\') != -1 && navigator.userAgent.indexOf(\'5.\') == -1 )
    document.write(\' <a href="http://wwp.icq.com/scripts/search.dll?to='.xarVar::prepForDisplay($value).'"><img src="'.xarVar::prepForDisplay($data['image']).'" alt="ICQ Number" title="ICQ Number" alt=""/></a>\');
else
    document.write(\'<a href="http://wwp.icq.com/scripts/search.dll?to='.xarVar::prepForDisplay($value).'"><img src="'.xarVar::prepForDisplay($data['image']).'" alt="ICQ Number" title="ICQ Number" alt=""/></a><a href="http://wwp.icq.com/'.xarVar::prepForDisplay($value).'#pager"><img src="http://web.icq.com/whitepages/online?icq='.xarVar::prepForDisplay($value).'&amp;img=5" width="18" height="18" alt=""/></a>\');
//--></script><noscript><a href="http://wwp.icq.com/scripts/search.dll?to='.xarVar::prepForDisplay($value).'"><img src="'.xarVar::prepForDisplay($data['image']).'" alt="ICQ Number" title="ICQ Number" border="0"/></a></noscript>';

        }

        $data['value'] = $value;
        $data['link'] = $link;
        return parent::showOutput($data);
    }
}
