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
        if (!xarVar::fetch('value',      'array', $value,  array(), xarVar::NOT_REQUIRED)) {return;}
        
        sys::import('xaraya.structures.query');
        $q = new Query();
        foreach ($names as $name) {
            if (empty($value[$name]) && !in_array($op[$name], array('eqempty','neempty','null','notnull'))) continue;
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
                    $q->like($source[$name], '%'.$value[$name].'%'); break;
                case 'notlike' : 
                    $q->notlike($source[$name], '%'.$value[$name].'%'); break;
                default:
                    $q->$op[$name]($source[$name], $value[$name]); break;
            }
        }

        // Save the conditions in a session var. Perhaps also in some cache?
        if (empty($filtername)) $filtername = $objectname;
        xarSession::setVar('DynamicData.Filter.' . $filtername, serialize($q));
        xarController::redirect($return_url);
        return true;
        
    } else {
        if (!isset($args['return_url'])) $args['return_url'] = xarServer::getCurrentURL();
        if (!isset($args['button'])) $args['button'] = xarML('Submit');
        $fields = '';
        if (isset($args['fieldlist'])) $fields = $args['fieldlist'];
        $args['fieldlist'] = explode(',', $fields);
        if (!is_array($fields)) $args['fieldlist'] = explode(',', $fields);
        if (!isset($args['object'])) throw new Exception('Missing $object for filter tag');
        $properties = $args['object']->getProperties();
        
        if (empty($args['filtername'])) $args['filtername'] = $args['object']->name;
        $filter = @unserialize(xarSession::getVar('DynamicData.Filter.' . $args['filtername']));
        if (empty($filter)) $filter = array();
        $values = array();
        $ops    = array();
        if (is_object($filter)) {
            foreach ($filter->conditions as $condition) {
                $values[$condition['field1']] = trim($condition['field2'], "%");
                $ops[$condition['field1']]    = $condition['op'];
            }
        }
        
        $data['properties'] = array();
        $data['valuelist']  = array();
        $data['oplist']     = array();
        foreach ($properties as $name => $property) {
            if (!empty($args['fieldlist']) && !in_array($name,$args['fieldlist'])) continue;
            $property->value = $property->defaultvalue;
            $data['properties'][$name] =& $property;
            if (isset($values[$property->source]))
                $data['valuelist'][$name]  = $values[$property->source];
            if (isset($ops[$property->source]))
                $data['oplist'][$name]     = $ops[$property->source];
        }
        $data['button'] = $args['button'];
        $data['return_url'] = $args['return_url'];
        $data['objectname'] = $args['object']->name;
        $data['object']     =& $args['object'];
        $data['filtername'] = $args['filtername'];
        $data['fieldlist']  =& $args['fieldlist'];
    }
    return $data;
}
?>