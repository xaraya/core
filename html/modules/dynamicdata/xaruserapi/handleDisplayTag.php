<?php
/**
 * Handle dynamic data display tags
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-display ...> display tags
 * Format : <xar:data-display module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-display fields="$fields" ... />
 *       or <xar:data-display object="$object" ... />
 *
 * @param $args array containing the item that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showdisplay() in the BL template
 */
function dynamicdata_userapi_handleDisplayTag($args)
{
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return 'echo '.$args['object'].'->showDisplay(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showDisplay(); ';
        }
    }

    // since no object available we must have a moduleid
    $out = "echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showdisplay',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
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
