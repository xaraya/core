<?php
/**
 * Utilities
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/182.html
 */
 /*
 * @author Marc Lutolf <mfl@netspan.ch>
 */
function dynamicdata_user_filtertag(Array $args=array())
{
    if (!xarVarFetch('filter_submitted', 'int:0', $filter_submitted,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    
    if ($filter_submitted) {
        if (!xarVarFetch('objectname', 'str', $objectname,  '', XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('return_url', 'str', $return_url,  '', XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('name', 'array', $names,  array(), XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('source', 'array', $source,  array(), XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('op', 'array', $op,  array(), XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('value', 'array', $value,  array(), XARVAR_NOT_REQUIRED)) {return;}
        
        sys::import('xaraya.structures.query');
        $q = new Query();
        foreach ($names as $name) {
            if (empty($value[$name]) && !in_array($op[$name], array('eqempty','neempty','null','notnull'))) continue;
            switch($op[$name]) {
                case 'eqempty' : 
                    $q->eq($source[$name],''); break;
                case 'neempty' : 
                    $q->ne($source[$name],''); break;
                case 'null' : 
                    $q->eq($source[$name],NULL); break;
                case 'notnull' : 
                    $q->ne($source[$name],NULL); break;
                case 'like' : 
                    $q->like($source[$name],'%'.$value[$name].'%'); break;
                case 'notlike' : 
                    $q->notlike($source[$name],'%'.$value[$name].'%'); break;
                default:
                    $q->$op[$name]($source[$name],$value[$name]); break;
            }
        }

        // Save the conditions in a session var. Perhaps also in some cache?
        xarSession::setVar('DynamicData.Filter.' . $objectname, serialize($q));
        xarController::redirect($return_url);
        return true;
        
    } else {
        if (!isset($args['return_url'])) $args['return_url'] = xarServer::getCurrentURL();
        if (!isset($args['button'])) $args['button'] = xarML('Submit');
        if (!isset($args['fields'])) $args['fields'] = array();
        if (!is_array($args['fields'])) $args['fields'] = explode(',',$args['fields']);
        if (!isset($args['object'])) throw new Exception('Missing $object for filter tag');
        $properties = $args['object']->getProperties();
        $data['properties'] = array();
        foreach ($properties as $name => $property) {
            if (!empty($args['fields']) && !in_array($name,$args['fields'])) continue;
            $data['properties'][$name] = $property;
        }
        $data['button'] = $args['button'];
        $data['return_url'] = $args['return_url'];
        $data['objectname'] = $args['object']->name;
    }
    return $data;
}
?>
