<?php
/**
 * Dynamic Data Module Itemtype Property
 *
 * A dropdown giving the extensions based on a given module
 * to use for displaying possible parents of an extension
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author Marc Lutolf <random@xaraya.com>
*/

// We base it on the object property
sys::import('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * Handle the module itemtype property
 *
 * @package dynamicdata
 */
class Dynamic_ModuleItemtype_Property extends Dynamic_Select_Property
{
    public $id         = 600;
    public $name       = 'moduleitemtype';
    public $desc       = 'Parent';
    public $reqmodules = array('dynamicdata');

    public $referencemoduleid = 182;

    function __construct($args)
    {
        parent::__construct($args);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        if (isset($args['modid'])) $this->referencemoduleid = $args['modid'];
    }

    function validateValue($value = null)
    {
        if (isset($value)) {
            $this->value = $value;
        }
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        $args['module'] = 'base';
        $args['template'] = 'dropdown';
        if (isset($modid)) $this->referencemoduleid = $modid;
        return parent::showInput($args);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!empty($modid)) $this->referencemoduleid = $modid;
        $this->options = $this->getOptions();
        if (isset($value)) {
            $this->value = $value;
        }
        if ($this->value < 1000) {
            $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $this->referencemoduleid));
            // we may still have a loose end in the module: no appropriate parent
            $name = isset($types[$this->value]) ? $types[$this->value]['label'] : xarML('base itemtype');
            $data['option'] = array('id' => $this->referencemoduleid,
                                    'name' => $name);
            if (empty($template)) {
                $template = 'dropdown';
            }
            return xarTplProperty('base', $template, 'showoutput', $data);
        } else {
            return parent::showOutput($args);
        }
    }

    // Return a list of array(id => value) for the possible options
    function getOptions()
    {
        $this->options = array();
        $types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $this->referencemoduleid));
        if ($types != array()) {
            foreach ($types as $key => $value) $this->options[] = array('id' => $key, 'name' => $value['label']);
        } else {
            $this->options[] = array('id' => 0, 'name' => xarML('no itemtypes defined'));
        }
        return $this->options;
    }
}
?>
