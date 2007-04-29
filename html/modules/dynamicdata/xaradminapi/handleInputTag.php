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
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-input field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-input property="$property" /> with $property a DataProperty object
 *
 * Special attributes :
 *     hidden="yes" to show a hidden field regardless of the original property type
 *     preset="yes" this can typically be used in admin-new.xd templates for individual
 *                  properties you'd like to automatically preset via GET or POST parameters
 * Note: don't use this if you already check the input for the whole object or in the code
 * See also preview="yes", which can be used on the object level to preview the whole object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @return string the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_adminapi_handleInputTag($args)
{
    // remove BL handler stuff
    if (isset($args['handler_type'])) {
        unset($args['handler_type']);
    }
    $out = '';
    if (isset($args['property'])) {
        $property  = $args['property'];
        unset($args['property']);
    }

/* cfr. bug 4017
    // fix id containing [] in forms
    if (!empty($args['name']) && empty($args['id']) && strpos($args['name'],'[')) {
         $args['id'] = strtr($args['name'], array('[' => '_', ']' => ''));
    }
*/
    if (!empty($args)) {
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
            unset ($args['field']);
        }
        if (!isset($property)) {
            $out .= "sys::import('modules.dynamicdata.class.properties');";
            $out .=  '$property = & DataPropertyMaster::getProperty('.$pargs.'); ';
            $property = '$property';
        }
        $out .= 'echo '.$property.'->showInput('.$pargs.'); ';
    } else {
        $out = 'echo '.$property.'->showInput(); ';
    }
    return $out;
}
?>
