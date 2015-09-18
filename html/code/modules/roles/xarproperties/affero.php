<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * include the base class
 */
sys::import('modules.base.xarproperties.textbox');

/**
 * Handle Affero property
 * @author mikespub <mikespub@xaraya.com>
 */
class AfferoProperty extends TextBoxProperty
{
    public $id         = 40;
    public $name       = 'affero';
    public $desc       = 'Affero Username';
    public $reqmodules = array('roles');

    public $initialization_icon_url;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'affero';
        $this->filepath   = 'modules/roles/xarproperties';
        if (empty($this->initialization_icon_url)) {
            $this->initialization_icon_url = xarTpl::getImage('contact/affero.png','module','roles');
        }
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!empty($value)) {
            if (!is_string($value)) {
                $this->invalid = xarML('Affero Name: #(1)', $this->name);
                xarLog::message($this->invalid, XARLOG_LEVEL_ERROR);
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    public function showInput(Array $data = array())
    {
        if(!isset($data['value'])) $data['value'] = $this->value;

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.xarVarPrepForDisplay($data['value']);
        }
        // $data['value'] is prepared for display by textbox
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        $data['value'] = xarVarPrepForDisplay($data['value']);

        $data['link'] = '';
        if (!empty($data['value'])) {
            $data['link'] = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$data['value'];
        }
        if (empty($data['image'])) {
            $data['image'] = $this->initialization_icon_url;
        }
        return parent::showOutput($data);
    }
}
?>
