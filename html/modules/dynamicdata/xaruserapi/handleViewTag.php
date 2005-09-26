<?php
/**
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
 * Handle <xar:data-view ...> view tags
 * Format : <xar:data-view module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-view items="$items" labels="$labels" ... />
 *       or <xar:data-view object="$object" ... />
 *
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showview() in the BL template
 */
function dynamicdata_userapi_handleViewTag($args)
{
    // if we already have an object, we simply invoke its showView() method
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
            return 'echo '.$args['object'].'->showView(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showView(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = "echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showview',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= "                         '$key' => $val,\n";
        } else {
            $out .= "                         '$key' => '$val',\n";
        }
    }
    $out .= "                         ));";
    return $out;
}

?>