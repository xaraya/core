<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/* Include the base class */
sys::import('modules.base.xarproperties.urlicon');
/**
 * Handle ICQ property
 * @author mikespub <mikespub@xaraya.com>
 */
class ICQProperty extends URLIconProperty
{
    public $id         = 28;
    public $name       = 'icq';
    public $desc       = 'ICQ Number';
    public $reqmodules = array('roles');

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'roles';
        $this->template = 'icq';
        $this->filepath   = 'modules/roles/xarproperties';
    }

    public function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_numeric($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('ICQ Number: #(1)', $this->name);
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

        $data['link'] ='';
        if(!empty($data['value'])) {
            $data['link'] = 'http://wwp.icq.com/scripts/search.dll?to=' . $data['value'];
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) $value = $this->value;

        // TODO: use redirect function here ?
        $link = '';
        if (!empty($value) && !empty($this->icon)) {
            // TODO: check this ICQ stuff
            // TODO: move this outa here
            //<jojodee> Passing the whole lot to the template !
            //The data is there for anyone that wants to use the vars themselves in the template.
            $link = '<script type="text/javascript"><!--
if ( navigator.userAgent.toLowerCase().indexOf(\'mozilla\') != -1 && navigator.userAgent.indexOf(\'5.\') == -1 )
    document.write(\' <a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" alt=""/></a>\');
else
    document.write(\'<a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" alt=""/></a><a href="http://wwp.icq.com/'.xarVarPrepForDisplay($value).'#pager"><img src="http://web.icq.com/whitepages/online?icq='.xarVarPrepForDisplay($value).'&amp;img=5" width="18" height="18" alt=""/></a>\');
//--></script><noscript><a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" border="0" /></a></noscript>';

        }

        $data['value'] = $value;
        $data['link'] = $link;
        return parent::showOutput($data);
    }
}
?>
