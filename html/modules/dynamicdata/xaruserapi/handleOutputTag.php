<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-output field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-output property="$property" /> with $property a Dynamic Property object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @return string the PHP code needed to invoke showoutput() in the BL template
 * @todo move this to some common place in Xaraya (base module ?)
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    
    if (isset($args['property'])) {
        $property  = $args['property'];
        unset($args['property']);
    }
    $out = '';
    
    if (count($args) > 1) {
        if (!isset($args['field'])) {
            $parts = array();
            foreach ($args as $key => $val) {
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            $pargs = 'array('.join(', ',$parts).')';
        } else {
            $pargs = $args['field'];
            unset($args['field']);
        }
        if (!isset($property)) {
            $out .= "sys::import('modules.dynamicdata.class.properties');";
            $out .=  '$property = & Dynamic_Property_Master::getProperty('.$pargs.'); ';
            $property = '$property';
        }
        $out .= 'echo '.$property.'->showOutput('.$pargs.'); ';
    } else {
        $out .=  'echo '.$property.'->showOutput(); ';
    }
    return $out;
}
?>
