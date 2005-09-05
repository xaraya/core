<?php
/**
 * File: $Id$
 *
 * Handle form field tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-input field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-input property="$property" /> with $property a Dynamic Property object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_adminapi_handleInputTag($args)
{
    // we just invoke the showInput() method of the Dynamic Property here
    if (!empty($args['property'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'property' || $key == 'hidden') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            if (!empty($args['hidden'])) {
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