<?php
/**
 * Utilities
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
 /*
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.class.properties.master');

function dynamicdata_user_filtertag(Array $args=array())
{
    if (!xarVar::fetch('filter_submitted', 'int:0', $filter_submitted,  0, xarVar::NOT_REQUIRED)) {return;}

    if ($filter_submitted) {
        if (!xarVar::fetch('objectname', 'str',   $objectname,  '', xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('filtername', 'str',   $filtername,  '', xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('return_url', 'str',   $return_url,  '', xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('name',       'array', $names,  array(), xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('source',     'array', $source,  array(), xarVar::NOT_REQUIRED)) {return;}
        if (!xarVar::fetch('op',         'array', $op,  array(), xarVar::NOT_REQUIRED)) {return;}
    	
    	// Get an instance of the dataobject so that we can get at the dataproperties' checkInput() method
    	$object = DataObjectMaster::getObject(array('name' => $objectname));
    	
        sys::import('xaraya.structures.query');
        $q = new Query();
        foreach ($names as $name) {
            // Get the value of a property from the template
            $object->properties[$name]->checkInput("value_" . $name);
            $thisvalue = $object->properties[$name]->value;

            if (empty($op[$name])) continue;
            
            switch($op[$name]) {
                case 'eqempty' : 
                    $q->eq($source[$name], ''); break;
                case 'neempty' : 
                    $q->ne($source[$name], ''); break;
                case 'null' : 
                    $q->eq($source[$name], NULL); break;
                case 'notnull' : 
                    $q->ne($source[$name], NULL); break;
                case 'like' : 
                    $q->like($source[$name], '%' . $thisvalue . '%'); break;
                case 'notlike' : 
                    $q->notlike($source[$name], '%' . $thisvalue . '%'); break;
                case 'regex' : 
                	// Ignore empty an empty field here
                	if (empty($thisvalue)) break;
                    $q->regex($source[$name], $thisvalue); break;
                default:
                    $q->{$op[$name]}($source[$name], $thisvalue); break;
            }
        }

        // Save the conditions in a session var. Perhaps also in some cache?
        if (empty($filtername)) $filtername = $objectname;
        xarSession::setVar('DynamicData.Filter.' . $filtername, serialize($q));

        // Redirect to the next page
        xarController::redirect($return_url);
        return true;
        
    } else {
        // Make sure we have a dataobject
        if (!isset($args['object'])) {
        	if (isset($args['objectname'])) {
        		$args['object'] = DataObjectMaster:: getObject(array('name' => $args['objectname']));
        	} else {
        		throw new Exception('Missing $object for filter tag');
        	}
        }
        
        // Check if a fieldlist was passed
        if (isset($args['fieldlist']) && !empty($args['fieldlist'])) {
			// Support both strings and arrays for the fieldlist
			if (!is_array($args['fieldlist'])) $args['fieldlist'] = explode(',', $args['fieldlist']);
			// Remove any unwanted delimiters, spaces etc.
			foreach ($args['fieldlist'] as $k => $v) $args['fieldlist'][$k] = trim($v);
        } else {
        	$args['fieldlist'] = $args['object']->getFieldList();
        }
        $data['fieldlist'] = $args['fieldlist'];

        if (empty($args['filtername'])) $args['filtername'] = $args['object']->name;
        $filter = @unserialize(xarSession::getVar('DynamicData.Filter.' . $args['filtername']) ?? '');
        if (empty($filter)) $filter = array();
        $values = array();
        $ops    = array();
        if (is_object($filter)) {
            foreach ($filter->conditions as $condition) {
                $values[$condition['field1']] = trim($condition['field2'], "%");
                $ops[$condition['field1']]    = transform_operator($condition['op']);
            }
        }
        
        // Winnow the properties to be used according to the fieldlist, and add the information from any previous filter
        $data['properties'] = array();
        $data['valuelist']  = array();
        $data['oplist']     = array();
        $properties = $args['object']->getProperties();
        foreach ($properties as $name => $property) {
            if (!empty($args['fieldlist']) && !in_array($name,$args['fieldlist'])) continue;
            $property->value = $property->defaultvalue;
            $data['properties'][$name] =& $property;
            if (isset($values[$property->source]))
                $data['valuelist'][$name]  = $values[$property->source];
            if (isset($ops[$property->source]))
                $data['oplist'][$name]     = $ops[$property->source];
        }
    	// This is the URL we will redirect to when we have submitted
        if (!isset($args['return_url'])) $args['return_url'] = xarServer::getCurrentURL();
        // This is the label for the submit button in the template
        if (!isset($args['button'])) $args['button'] = xarML('Submit');
        
        $data['button'] = $args['button'];
        $data['return_url'] = $args['return_url'];
        $data['objectname'] = $args['object']->name;
        $data['object']     =& $args['object'];
        $data['filtername'] = $args['filtername'];
    }
    return $data;
}

function transform_operator($op)
{
	$oparray = array(
		'='        => 'eq',
		'!='       => 'ne',
		'>'        => 'gt',
		'>='       => 'ge',
		'<'        => 'lt',
		'<='       => 'le',
		'LIKE'     => 'like',
		'NOT LIKE' => 'notlike',
	);
	return $oparray[$op];
}
