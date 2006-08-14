<?php
/**
 * Handle form tags
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-input field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-input property="$property" /> with $property a Dynamic Property object
 *
 * Special attributes :
 *     hidden="yes" to show a hidden field regardless of the original property type
 *     preset="yes" this can typically be used in admin-new.xd templates for individual
 *                  properties you'd like to automatically preset via GET or POST parameters
 * Note: don't use this if you already check the input for the whole object or in the code
 * See also preview="yes", which can be used on the object level to preview the whole object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_adminapi_handleInputTag($args)
{
    // remove BL handler stuff
    if (isset($args['handler_type'])) {
        unset($args['handler_type']);
    }
/* cfr. bug 4017
    // fix id containing [] in forms
    if (!empty($args['name']) && empty($args['id']) && strpos($args['name'],'[')) {
         $args['id'] = strtr($args['name'], array('[' => '_', ']' => ''));
    }
*/
    // we just invoke the showInput() method of the Dynamic Property here
    if (!empty($args['property'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'property' || $key == 'hidden' || $key == 'preset') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            if (!empty($args['preset']) && empty($args['value'])) {
                return 'echo '.$args['property'].'->_showPreset(array('.join(', ',$parts).')); ';
            } elseif (!empty($args['hidden'])) {
                return 'echo '.$args['property'].'->showHidden(array('.join(', ',$parts).')); ';
            } else {
                return 'echo '.$args['property'].'->showInput(array('.join(', ',$parts).')); ';
            }
        } else {
            return 'echo '.$args['property'].'->showInput(); ';
        }
    }
    // we'll call a function to do it for us
    $out = "echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showinput',\n";
    if (isset($args['field'])) {
        $out .= '                   '.$args['field']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}
?>
