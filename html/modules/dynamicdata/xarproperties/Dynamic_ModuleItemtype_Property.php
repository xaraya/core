<?php
/**
 *
 * Dynamic Data Module Itemtype Property
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
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the module itemtype property
 *
 * @package dynamicdata
 */
class Dynamic_ModuleItemtype_Property extends Dynamic_Select_Property
{
    var $referencemoduleid = 182;

    function Dynamic_ModuleItemtype_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        extract($args);
        if (isset($modid)) $this->referencemoduleid = $modid;
		$this->options = $this->getOptions();
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
        if (isset($modid)) $this->referencemoduleid = $modid;
		$this->options = $this->getOptions();
        if (isset($value)) {
            $this->value = $value;
        }
        if ($this->value < 1000) {
			$types = xarModAPIFunc('dynamicdata','user','getmoduleitemtypes', array('moduleid' => $this->referencemoduleid));
			// we may still have a loose end in the module: no appropriate parent
			$name = isset($types[$this->value]) ? $types[$this->value]['label'] : xarML('not available');
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

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'             => 600,
                              'name'           => 'moduleitemtype',
                              'label'          => 'Parent',
                              'format'         => '600',
                              'validation'     => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'dynamicdata',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}
?>
