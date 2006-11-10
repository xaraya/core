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
 * Handle <xar:data-getitem ...> getitem tags
 * Format : <xar:data-getitem name="$properties" module="123" itemtype="0" itemid="$id" fieldlist="$fieldlist" .../>
 *       or <xar:data-getitem name="$properties" object="$object" ... />
 *
 * @param $args array containing the module and item that you want to display, or fields
 * @return string the PHP code needed to invoke getitemtag() in the BL template and return an array of properties
 * @todo move this to some common place in Xaraya (base module ?)
 * @todo replace procedural getitem call
 */
function dynamicdata_userapi_handleGetItemTag($args)
{
    if (isset($args['object'])) {
        $object  = $args['object'];
        unset($args['object']);
    } else {
        $args['getobject']  = 1;
    }

    if (isset($args['name'])) {
        $name = $args['name'];
        unset($args['name']);
    }

    $out = '';
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args)) {
        $parts = array();
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $parts[] = "'$key' => ".$val;
            } else {
                $parts[] = "'$key' => '".$val."'";
            }
        }
        if (!isset($object)) {
            $out .=  '$object = xarModAPIFunc(\'dynamicdata\',\'user\',\'getitem\',array('.join(', ',$parts).'));';
            $object= '$object';
        }
        $out .= $object.'->getItem(array('.join(', ',$parts).')); ' .
                   $name . ' =& '.$object.'->getProperties(); ';
    } else {
        $out .= $object.'->getItem(); ' .
                   $name . ' =& '.$object.'->getProperties(); ';
    }
    return $out;
}
?>
